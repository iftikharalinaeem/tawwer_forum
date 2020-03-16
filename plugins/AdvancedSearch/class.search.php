<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * A utility class to help with searches.
 */

use Garden\EventManager;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Contracts\Search\SearchRecordTypeInterface;

class Search {
    /// Properties ///
    protected static $types;


    /// Methods ///

    /**
     * Massage an advanced style search query for correctness.
     *
     * @param array $search
     * @param bool $api Whether the data comes from the UI or the API.
     * @return array
     */
    public static function cleanSearch($search = [], $api = false) {
        $search = array_change_key_case($search);
        $search = array_map(function ($v) {
            return is_string($v) ? trim($v) : $v;
        }, $search);
        $search = array_filter($search, function ($v) {
            return $v !== '';
        });
        $doSearch = false;

        $enableAllTypes = (count($search) === 1 && isset($search['search']));

        /// Author ///
        if (isset($search['author'])) {
            $usernames = explode(',', $search['author']);
            $usernames = array_map('trim', $usernames);
            $usernames = array_filter($usernames);

            $users = Gdn::sql()->select('UserID, Name')->from('User')->where('Name', $usernames)->get()->resultArray();
            if (count($usernames) == 1 && empty($users)) {
                // Searching for one author that doesn't exist.
                $search['dosearch'] = false;
            }

            if (!empty($users)) {
                $search['users'] = $users;
                $doSearch = true;
            }
        }

        /// Category ///
        $categoryFilter = [];
        $archived = $search['archived'] ?? 0;
        $followedCats = $search['followedcats'] ?? 0;
        $categoryID = $search['cat'] ?? null;
        if ('all' === $categoryID) {
            $categoryID = null;
        }

        if (!array_key_exists('subcats', $search)) {
            $search['subcats'] = null;
        }

        if (!array_key_exists('cat', $search)) {
            $search['cat'] = null;
        }

        $args = [
            'categoryID' => $categoryID,
            'search' =>
                [
                    'subcats' => $search['subcats'],
                    'cat' => $search['cat'],
                ],
        ];

        /** @var \Garden\EventManager $eventManager */
        $eventManager = Gdn::getContainer()->get(\Garden\EventManager::class);
        $args = $eventManager->fireFilter('advancedSearchPlugin_beforeSearch', $args);

        $categoryID = $args['categoryID'] ?? $categoryID;
        $search['subcats'] = $args['search']['subcats'] ?? $search['subcats'];

        if (!$categoryID) {
            switch ($archived) {
                case 1:
                    // Include both, do nothing.
                    break;
                case 2:
                    // Only archive.
                    $categoryFilter['Archived'] = 1;
                    break;
                case 0:
                default:
                    // Not archived.
                    $categoryFilter['Archived'] = 0;
            }
        }
        $categories = CategoryModel::getByPermission('Discussions.View', null, $categoryFilter);
        $categories[0] = true; // allow uncategorized too.
        $categoryIDs = array_keys($categories);

        Gdn::pluginManager()->fireAs('Search')->fireEvent('AllowedCategories', ['CategoriesID' => &$categoryIDs]);

        $categories = array_intersect_key($categories, array_flip($categoryIDs));

        if ($followedCats) {
            $categories = array_filter($categories, function($category) {
                if ($category === true) {
                    return true;
                }
                return $category['Followed'];
            });
            $categoryIDs = array_keys($categories);
        }

        if ($categoryID) {
            touchValue('subcats', $search, 0);
            if ($search['subcats']) {
                $categoryID = array_column(CategoryModel::getSubtree($categoryID), 'CategoryID');
                trace($categoryID, 'cats');
            }

            $categoryID = array_intersect((array)$categoryID, $categoryIDs);

            if (empty($categoryID)) {
                $search['cat'] = false;
            } elseif (empty($search['title']) && empty($search['author']) && empty($search['tags']) && empty($search['date'])) {
                $doSearch = false;
            } else {
                $search['cat'] = $categoryID;
                $doSearch = true;
            }
        } else {
            $search['cat'] = $categoryIDs;
            unset($search['subcategories']);
        }

        /// Date ///
        if (isset($search['date'])) {
            // Try setting the date.
            $timestamp = strtotime($search['date']);
            if ($timestamp) {
                $search['houroffset'] = Gdn::session()->hourOffset();
                $timestamp += -Gdn::session()->hourOffset() * 3600;
                $search['date'] = Gdn_Format::toDateTime($timestamp);

                if (isset($search['within'])) {
                    $search['timestamp-from'] = strtotime('-'.$search['within'], $timestamp);
                    $search['timestamp-to'] = strtotime('+'.$search['within'], $timestamp);
                } else {
                    $search['timestamp-from'] = $search['date'];
                    $search['timestamp-to'] = strtotime('+1 day', $timestamp);
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
            $tags = explode(',', $search['tags']);
            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);

            $tagData = Gdn::sql()->select('TagID, Name')->from('Tag')->where('Name', $tags)->get()->resultArray();
            if (count($tags) == 1 && empty($tagData)) {
                // Searching for one tag that doesn't exist.
                $doSearch = false;
                unset($search['tags']);
            }

            if (getValue('tags-op', $tags) === 'and' && count($tags) > count($tagData)) {
                // We are searching for all tags, but some of the tags don't exist.
                $doSearch = false;
            }

            if (!empty($tagData)) {
                $search['tags'] = $tagData;
                touchValue('tags-op', $search, 'or');
            } else {
                unset($search['tags'], $search['tags-op']);
            }
        }

        if (!$api) {
            // Types //

            $types = [];
            /** @var SearchRecordTypeInterface $recordType */
            foreach (self::types() as $recordType) {
                $key = $recordType->getCheckBoxId();
                if ($search[$key] ?? false) {
                    $types[] = $recordType;
                }
            }
            if (empty($types)) {
                $search['types'] = self::types();
            } else {
                $search['types'] = $types;
            }
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
            if ($api) {
                unset($search['types']['discussion']);
            }
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
        $result = [];
        if (preg_match_all('/<[^>]+src="([^"]+)"[^>]*?>/', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                // Skip emojis
                if (preg_match('/class="[^"]*?\bemoji\b[^"]*"/', $match[0])) {
                    continue;
                }
                $src = $match[1];
                $row = [
                    'type' => 'img',
                    'src' => $src,
                    'href' => $src,
                    'preview' => img($src)
                ];

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
            /** @var SearchRecordTypeProviderInterface $recordTypeProvider */
            $recordTypeProvider = Gdn::getContainer()->get(\Vanilla\Contracts\Search\SearchRecordTypeProviderInterface::class);
            self::$types = $recordTypeProvider->getAll();
        }

        return self::$types;
    }
}
