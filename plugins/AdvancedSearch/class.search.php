<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * A utility class to help with searches.
 */
class Search {
    /// Properties ///
    protected static $types;


    /// Methods ///

    /**
     * Massage an advanced style search query for correctness.
     *
     * @param array $search
     * @return array
     */
    public static function cleanSearch($search) {
        $search = array_change_key_case($search);
        $search = array_map(function ($v) {
            return is_string($v) ? trim($v) : $v;
        }, $search);
        $search = array_filter($search, function ($v) {
            return $v !== '';
        });
        $doSearch = false;

        /// Author ///
        if (isset($search['author'])) {
            $Usernames = explode(',', $search['author']);
            $Usernames = array_map('trim', $Usernames);
            $Usernames = array_filter($Usernames);

            $Users = Gdn::SQL()->select('UserID, Name')->from('User')->where('Name', $Usernames)->get()->resultArray();
            if (count($Usernames) == 1 && empty($Users)) {
                // Searching for one author that doesn't exist.
                $search['dosearch'] = false;
            }

            if (!empty($Users)) {
                $search['users'] = $Users;
                $doSearch = true;
            }
        }

        /// Category ///
        $CategoryFilter = array();
        $Archived = getValue('archived', $search, 0);
        $CategoryID = getValue('cat', $search);
        if (strcasecmp($CategoryID, 'all') === 0) {
            $CategoryID = null;
        }

        if (!$CategoryID) {
            switch ($Archived) {
                case 1:
                    // Include both, do nothing.
                    break;
                case 2:
                    // Only archive.
                    $CategoryFilter['Archived'] = 1;
                    break;
                case 0:
                default:
                    // Not archived.
                    $CategoryFilter['Archived'] = 0;
            }
        }
        $Categories = CategoryModel::getByPermission('Discussions.View', null, $CategoryFilter);
        $Categories[0] = true; // allow uncategorized too.

        // Make sure that group categories are searchable. Results are filtered later on in GroupHooks::SearchController_Render_Before.
        if (Gdn::addonManager()->isEnabled('Groups', \Vanilla\Addon::TYPE_ADDON)) {
            $Categories += array_flip(GroupModel::getGroupCategoryIDs());

        }
        $Categories = array_keys($Categories);
        //      Trace($Categories, 'allowed cats');

        if ($CategoryID) {
            touchValue('subcats', $search, 0);
            if ($search['subcats']) {
                $CategoryID = array_column(CategoryModel::getSubtree($CategoryID), 'CategoryID');
                trace($CategoryID, 'cats');
            }

            $CategoryID = array_intersect((array)$CategoryID, $Categories);

            if (empty($CategoryID)) {
                $search['cat'] = false;
            } else {
                $search['cat'] = $CategoryID;
                $doSearch = true;
            }
        } else {
            $search['cat'] = $Categories;
            unset($search['subcategories']);
        }

        /// Date ///
        if (isset($search['date'])) {
            // Try setting the date.
            $Timestamp = strtotime($search['date']);
            if ($Timestamp) {
                $search['houroffset'] = Gdn::session()->hourOffset();
                $Timestamp += -Gdn::session()->hourOffset() * 3600;
                $search['date'] = Gdn_Format::toDateTime($Timestamp);

                if (isset($search['within'])) {
                    $search['timestamp-from'] = strtotime('-'.$search['within'], $Timestamp);
                    $search['timestamp-to'] = strtotime('+'.$search['within'], $Timestamp);
                } else {
                    $search['timestamp-from'] = $search['date'];
                    $search['timestamp-to'] = strtotime('+1 day', $Timestamp);
                }
                $search['date-from'] = Gdn_Format::toDateTime($search['timestamp-from']);
                $search['date-to'] = Gdn_Format::toDateTime($search['timestamp-to']);
            } else {
                unset($search['date']);
            }
        } else {
            unset($search['within']);
        }

        /// Tags ///
        if (isset($search['tags'])) {
            $doSearch = true;
            $Tags = explode(',', $search['tags']);
            $Tags = array_map('trim', $Tags);
            $Tags = array_filter($Tags);

            $TagData = Gdn::SQL()->select('TagID, Name')->from('Tag')->where('Name', $Tags)->get()->resultArray();
            if (count($Tags) == 1 && empty($TagData)) {
                // Searching for one tag that doesn't exist.
                $doSearch = false;
                unset($search['tags']);
            }

            if (getValue('tags-op', $Tags) === 'and' && count($Tags) > count($TagData)) {
                // We are searching for all tags, but some of the tags don't exist.
                $doSearch = false;
            }

            if (!empty($TagData)) {
                $search['tags'] = $TagData;
                touchValue('tags-op', $search, 'or');
            } else {
                unset($search['tags'], $search['tags-op']);
            }
        }

        /// Types ///
        $types = array();
        $typecount = 0;
        $selectedcount = 0;

        foreach (self::types() as $table => $type) {
            $allselected = true;

            foreach ($type as $name => $label) {
                $typecount++;
                $key = $table.'_'.$name;

                if (getValue($key, $search)) {
                    $selectedcount++;
                    $types[$table][] = $name;
                } else {
                    $allselected = false;
                }
                unset($search[$key]);
            }
            // If all of the types are selected then don't filter.
            if ($allselected) {
                unset($type[$table]);
            }
        }

        // At least one type has to be selected to filter.
        if ($selectedcount > 0 && $selectedcount < $typecount) {
            $search['types'] = $types;
        } else {
            unset($search['types']);
        }


        /// Group ///
        if (!isset($search['group']) || $search['group']) {
            $group = true;

            // Check to see if we should group.
            if (isset($search['discussionid'])) {
                $group = false; // searching within a discussion
            } elseif (isset($search['types']) && !isset($search['types']['comment'])) {
                $group = false; // not search comments
            }

            $search['group'] = $group;
        } else {
            $search['group'] = false;
        }

        if (!empty($search['search']) || !empty($search['title'])) {
            $doSearch = true;
        }

        if (isset($search['discussionid'])) {
            $doSearch = true;
            unset($search['title']);
        }

        $search['dosearch'] = $doSearch;

        trace($search, 'calc search');
        return $search;
    }

    public static function youtube($id) {
        return <<<EOT
<span class="Video YouTube" id="youtube-$id"><span class="VideoPreview"><a href="https://www.youtube.com/watch?v=$id"><img src="https://img.youtube.com/vi/$id/0.jpg" /></a></span><span class="VideoPlayer"></span></span>
EOT;
    }

    public static function vimeo($id) {
        // width="500" height="281"
        return <<<EOT
<iframe src="http://player.vimeo.com/video/$id?badge=0" frameborder="0" class="Video Vimeo" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
EOT;
    }

    public static function extractMedia($html) {
        $result = array();
        if (preg_match_all('`src="([^"]+)"`', $html, $matches)) {
            foreach ($matches[1] as $src) {
                $row = array(
                    'type' => 'img',
                    'src' => $src,
                    'href' => $src,
                    'preview' => img($src)
                );

                $parts = parse_url($src);

                if (isset($parts['host'])) {
                    switch ($parts['host']) {
                        case 'img.youtube.com':
                            if (preg_match('`/vi/([^/]+)/\d+.jpg`i', $src, $m)) {
                                $row['type'] = 'video';
                                $row['subtype'] = 'youtube';
                                $id = urlencode($m[1]);
                                $row['href'] = 'https://www.youtube.com/watch?v='.$id;
                                $row['preview'] = self::youtube($id);
                            }
                            break;
                        case 'www.youtube.com':
                            if (preg_match('`/embed/([a-z0-9])`i', $src, $m)) {
                                $row['type'] = 'video';
                                $row['subtype'] = 'youtube';
                                $id = urlencode($m[1]);
                                $row['href'] = 'https://www.youtube.com/watch?v='.$id;
                                $row['preview'] = self::youtube($id);
                            }
                            break;
                        case 'vimeo.com':
                            $id = false;
                            // Try the querystring.
                            trace($parts);
                            if (isset($parts['query'])) {
                                parse_str($parts['query'], $get);
                                trace($get);
                                if (isset($get['clip_id'])) {
                                    $id = $get['clip_id'];
                                }
                            }

                            if ($id) {
                                $row['type'] = 'video';
                                $row['subtype'] = 'vimeo';
                                $row['href'] = 'http://vimeo.com/'.$id;
                                $row['preview'] = self::vimeo($id);
                            }
                            break;
                    }
                }


                $result[] = $row;
            }
        }
        return $result;
    }

    /**
     * Whether or not groups can be searched.
     *
     * @return bool Returns true if groups can be searched or false otherwise.
     */
    public static function searchGroups() {
        return method_exists('SearchModel', 'searchGroups') && SearchModel::searchGroups();
    }

    /**
     * Return an array of all of the valid search types.
     */
    public static function types() {
        if (!isset(self::$types)) {
            $types = array(
                'discussion' => array('d' => 'discussions'),
                'comment' => array('c' => 'comments')
            );

            if (Gdn::addonManager()->isEnabled('QnA', \Vanilla\Addon::TYPE_ADDON)) {
                $types['discussion']['question'] = 'questions';
                $types['comment']['answer'] = 'answers';
            }

            if (Gdn::addonManager()->isEnabled('Polls', \Vanilla\Addon::TYPE_ADDON)) {
                $types['discussion']['poll'] = 'polls';
            }

            if (Gdn::addonManager()->isEnabled('Pages', \Vanilla\Addon::TYPE_ADDON)) {
                $types['page']['p'] = 'docs';
            }

            if (Gdn::addonManager()->isEnabled('Groups', \Vanilla\Addon::TYPE_ADDON)) {
                $types['group']['group'] = 'group';
            }

            self::$types = $types;
        }

        return self::$types;
    }
}
