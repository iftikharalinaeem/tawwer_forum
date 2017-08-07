<?php if (!defined('APPLICATION')) { exit(); }
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class AdvancedSearchPlugin
 */
class AdvancedSearchPlugin extends Gdn_Plugin {
    /// Properties ///

    public static $Types;

    /// Methods ///

    public function __construct() {
        parent::__construct();

        self::$Types = [
            'discussion' => ['d' => 'discussions'],
            'comment' => ['c' => 'comments']
        ];

        if (Gdn::addonManager()->isEnabled('Sphinx', \Vanilla\Addon::TYPE_ADDON)) {
            if (Gdn::addonManager()->isEnabled('QnA', \Vanilla\Addon::TYPE_ADDON)) {
                self::$Types['discussion']['question'] = 'questions';
                self::$Types['comment']['answer'] = 'answers';
            }

            if (Gdn::addonManager()->isEnabled('Polls', \Vanilla\Addon::TYPE_ADDON)) {
                self::$Types['discussion']['poll'] = 'polls';
            }

            if (Gdn::addonManager()->isEnabled('Pages', \Vanilla\Addon::TYPE_ADDON)) {
                self::$Types['page']['p'] = 'docs';
            }

            if (Gdn::applicationManager()->checkApplication('Groups')) {
                self::$Types['group']['group'] = 'groups';
            }
        }

        $this->fireEvent('Init');
    }

    public function quickSearch($title, $get = []) {
        $form = new Gdn_Form();
        $form->Method = 'get';

        foreach ($get as $key => $value) {
            $form->addHidden($key, $value);
        }

        $result = ' <div class="QuickSearch">'.
            anchor(sprite('SpSearch'), '#', 'QuickSearchButton').
            '<div class="QuickSearchWrap MenuItems">';

        $result .= $form->open(['action' => url('/search')]).
//         $Form->label('@'.$title, 'search').
            ' '.$form->textBox('search', ['placeholder' => $title]).
            ' <div class="bwrap"><button type="submit" class="Button" title="'.t('Search').'">'.t('Go').'</button></div>'.
            $form->close();

        $result .= '</div></div>';

        return $result;
    }

    /// Event Handlers ///

    /**
     * @param AssetModel $sender
     */
    public function assetModel_styleCSS_handler($sender) {
        $sender->addCssFile('advanced-search.css', 'plugins/AdvancedSearch');
    }

    /**
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        if (!inSection('Dashboard')) {
            AdvancedSearchModule::addAssets();

            if (!Gdn::addonManager()->isEnabled('Sphinx', \Vanilla\Addon::TYPE_ADDON)) {
                $sender->addDefinition('searchAutocomplete', '0');
            }
        }
    }

    /**
     * Add the quick search the discussions list.
     * @param DiscussionsController $Sender
     * @param array $Args
     */
    public function discussionsController_pagerInit_handler($Sender, $Args) {
        $name = t('SearchBoxPlaceHolder', 'Search');
        $args = [];

        // See if there are any tags on the page.
        $tags = $Sender->data('Tags');
        if (is_array($tags) && class_exists('TagModel')) {
            $tags = TagModel::instance()->unpivot($tags);
            $tags = array_column($tags, 'Name');
            $args['tags'] = implode(',', $tags);
            $args['tags-op'] = 'and';
            $args['adv'] = 1;

            $name = sprintf(t('Search %s'), $Sender->title());
        }

        $quickserch = $this->quickSearch($name, $args);
        $Pager = $Args['Pager'];
        $Pager->HtmlAfter = $quickserch;
    }

    public function categoriesController_pagerInit_handler($sender, $args) {
        $categoryid = $sender->data('Category.CategoryID');

        if ($categoryid) {
            $name = Gdn_Format::text($sender->data('Category.Name'));
            if (mb_strwidth($name) > 20) {
                $name = t('category');
            }

            $quickserch = $this->quickSearch(sprintf(t('Search %s'), $name), ['cat' => $categoryid, 'adv' => 1]);

            $pager = $args['Pager'];
            $pager->HtmlAfter = $quickserch;
        }
    }

    public function discussionController_pagerInit_handler($sender, $args) {
        $quickserch = $this->quickSearch(sprintf(t('Search %s'), t('discussion')), ['discussionid' => $sender->data('Discussion.DiscussionID')]);

        $pager = $args['Pager'];
        $pager->HtmlAfter = $quickserch;
    }

    /**
     * @param Smarty $sender
     * @param type $args
     */
    public function gdn_smarty_init_handler($sender, $args) {
        $smartyVersion = defined('Smarty::SMARTY_VERSION') ? Smarty::SMARTY_VERSION : $sender->_version;

        // registerPlugin, introduced in Smart v3, is accessed via __call, so it cannot be detected with method_exists.
        if (version_compare($smartyVersion, '3.0.0', '>=')) {
            $sender->registerPlugin(
                'function',
                'searchbox_advanced',
                'searchBoxAdvanced'
            );
        } else {
            $sender->register_function('searchbox_advanced', 'searchBoxAdvanced');
        }
    }

    public function searchController_autoComplete_create($sender, $term, $limit = 5) {
        $searchModel = new SearchModel();
        $get = $sender->Request->get();
        $get['search'] = $term;
        $results = $searchModel->autoComplete($get, $limit);
        $this->calculateResults($results['SearchResults'], $results['SearchTerms'], !$sender->Request->get('nomark'), 100);

        if (isset($get['discussionid'])) {
            // This is searching a discussion so make the user the title.
            Gdn::userModel()->joinUsers($results['SearchResults'], ['UserID']);
            foreach ($results['SearchResults'] as &$row) {
                $row['Title'] = htmlspecialchars($row['Name']);
            }
        }

        header('Content-Type: application/json; charset=utf8');
        die(json_encode($results['SearchResults']));
    }

    public function searchController_groupAutoComplete_create($sender, $term, $limit = 5) {
        $searchModel = new SearchModel();
        $get = $sender->Request->get();
        $get['search'] = $term;
        $results = $searchModel->groupAutoComplete($get, $limit);
        $this->calculateResults($results['SearchResults'], $results['SearchTerms'], !$sender->Request->get('nomark'), 100);

        header('Content-Type: application/json; charset=utf8');
        die(json_encode($results['SearchResults']));
    }

    /**
     *
     * @param SearchController $sender
     * @param type $search
     * @param type $page
     */
    public function searchController_index_create($sender, $search = '', $page = false) {
        Gdn_Theme::section('SearchResults');

        $this->Sender = $sender;
        if ($sender->Head) {
            // Don't index search results pages.
            $sender->Head->addTag('meta', ['name' => 'robots', 'content' => 'noindex']);
            $sender->Head->addTag('meta', ['name' => 'googlebot', 'content' => 'noindex']);
        }

        list($offset, $limit) = offsetLimit($page, c('Garden.Search.PerPage', 10));
        $sender->setData('_Offset', $offset);
        $sender->setData('_Limit', $limit);

        // Do the search.
        $searchModel = new SearchModel();
        $sender->setData('SearchResults', []);
        $searchTerms = Gdn_Format::text($search);

        if (method_exists($searchModel, 'advancedSearch')) {
            $results = $searchModel->advancedSearch($sender->Request->get(), $offset, $limit);
            $sender->setData($results);
            $searchTerms = $results['SearchTerms'];


            // Grab the discussion if we are searching it.
            if (isset($results['CalculatedSearch']['discussionid'])) {
                $discussionModel = new DiscussionModel();
                $discussion = $discussionModel->getID($results['CalculatedSearch']['discussionid']);
                if ($discussion) {
                    $cat = CategoryModel::categories(getValue('CategoryID', $discussion));
//               if (getValue('PermsDiscussionView', $Cat))
                    $sender->setData('Discussion', $discussion);
                }
            }
        } else {
            $results = $this->devancedSearch($searchModel, $sender->Request->get(), $offset, $limit);
            $sender->setData('SearchResults', $results, true);
            if ($searchTerms) {
                $searchTerms = explode(' ', $searchTerms);
            } else {
                $searchTerms = [];
            }
        }
        Gdn::userModel()->joinUsers($sender->Data['SearchResults'], ['UserID']);
        $this->calculateResults($sender->Data['SearchResults'], $searchTerms, !$sender->Request->get('nomark'));

        if (isset($sender->Data['ChildResults'])) {
            // Join the results.
            $childResults = $sender->Data['ChildResults'];
            unset($sender->Data['ChildResults']);
            $this->joinResults($sender->Data['SearchResults'], $childResults, $searchTerms);
        }

        $sender->setData('SearchTerm', implode(' ', $searchTerms), true);
        $sender->setData('SearchTerms', $searchTerms, true);
        $sender->setData('From', $offset + 1);
        $sender->setData('To', $offset + count($sender->Data['SearchResults']));

        // Set the title from the search terms.
        $sender->title(t('Search'));

        $sender->CssClass = 'NoPanel';

        // Make a url for related search.
        $get = array_change_key_case($sender->Request->get());
        unset($get['page']);
        $get['adv'] = 1;
        $url = '/search?'.http_build_query($get);
        $sender->setData('SearchUrl', $url);


        $this->render('Search');
    }

    protected function joinResults(&$parentResults, $childResults, $searchTerms) {
        // Calculate the results.
        Gdn::userModel()->joinUsers($childResults, ['UserID']);
        $this->calculateResults($childResults, $searchTerms, !Gdn::request()->get('nomark'));
        $childResults = Gdn_DataSet::index($childResults, ['DiscussionID'], ['Unique' => false]);
        foreach ($parentResults as &$row) {
            $row['Children'] = getValue($row['DiscussionID'], $childResults, []);
        }
    }

    protected function calculateResults(&$Data, $SearchTerms, $Mark = true, $Length = 200) {
        if ($SearchTerms && !is_array($SearchTerms)) {
            $SearchTerms = explode(' ', $SearchTerms);
            $SearchTerms = array_filter($SearchTerms, function ($v) {
                return trim($v);
            });
        }

        if (debug() && method_exists('SearchModel', 'addNotes')) {
            $calc = function ($r, $t) {
                return SearchModel::addNotes($r, $t);
            };
        } else {
            $calc = function ($r) {
                return null;
            };
        }

        $UseCategories = c('Vanilla.Categories.Use');
        $Breadcrumbs = [];

        foreach ($Data as &$Row) {
            $Row['Title'] = markString($SearchTerms, Gdn_Format::text($Row['Title'], false));
            $Row['Score'] = (int)$Row['Score'];

            // Generate record URLs based on their type.
            switch (val('RecordType', $Row)) {
                case 'Comment':
                    $comment = arrayTranslate($Row, ['PrimaryID' => 'CommentID', 'CategoryID']);
                    $Row['Url'] = commentUrl($comment);
                    break;
                case 'Discussion':
                    $discussion = arrayTranslate($Row, ['PrimaryID' => 'DiscussionID', 'Title' => 'Name', 'CategoryID']);
                    $Row['Url'] = discussionUrl($discussion);
                    break;
                default:
                    $Row['Url'] = url($Row['Url'], true);
            }
            unset($record);

            $Summary = Gdn_Format::to($Row['Summary'], $Row['Format']);
            $media = Search::extractMedia($Summary);
            $Row['Media'] = $media;

            $Row['Summary'] = searchExcerpt(htmlspecialchars(Gdn_Format::plainText($Summary, 'Raw')), $SearchTerms, $Length);
            $Row['Summary'] = Emoji::instance()->translateToHtml($Row['Summary']);

            $Row['Format'] = 'Html';
            $Row['DateHtml'] = Gdn_Format::date($Row['DateInserted'], 'html');
            $Row['Notes'] = $calc($Row, $SearchTerms);

            $Type = strtolower(getValue('Type', $Row));
            if (isset($Row['CommentID'])) {
                if ($Type == 'question') {
                    $Type = 'answer';
                } else {
                    $Type = 'comment';
                }
            } elseif (isset($Row['PageID'])) {
                $Type = 'doc';
            } else {
                if (!$Type) {
                    $Type = 'discussion';
                } elseif ($Type == 'page' && isset($Row['DiscussionID'])) {
                    $Type = 'link';
                }
            }
            $Row['Type'] = $Type;

            // Add breadcrumbs for discussions.
            if ($UseCategories && isset($Row['CategoryID'])) {
                $CategoryID = $Row['CategoryID'];
                if (isset($Breadcrumbs[$CategoryID])) {
                    $Row['Breadcrumbs'] = $Breadcrumbs[$CategoryID];
                } else {
                    $Categories = CategoryModel::getAncestors($CategoryID);
                    $R = [];
                    foreach ($Categories as $Cat) {
                        $R[] = [
                            'Name' => $Cat['Name'],
                            'Url' => categoryUrl($Cat)
                        ];
                    }
                    $Row['Breadcrumbs'] = $R;
                    $Breadcrumbs[$CategoryID] = $R;
                }
            }
        }
    }

    function devancedSearch($searchModel, $search, $offset, $limit, $clean = true) {
        $search = Search::cleanSearch($search);
        $pdo = Gdn::database()->connection();

        $csearch = true;
        $dsearch = true;

        $cwhere = [];
        $dwhere = [];

        $dfields = ['d.Name', 'd.Body'];
        $cfields = 'c.Body';

        /// Search query ///

        $terms = getValue('search', $search);
        if ($terms) {
            $terms = $pdo->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $terms).'%');
        }

        // Only search if we have term, user, date, or title to search
        if (!$terms && !isset($search['users']) && !isset($search['date-from']) && !isset($search['date-to']) && !isset($search['title'])) {
            return [];
        }

        /// Title ///

        if (isset($search['title'])) {
            $csearch = false;
            $dwhere['d.Name like'] = $pdo->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $search['title']).'%');
        }

        /// Author ///
        if (isset($search['users'])) {
            $author = array_column($search['users'], 'UserID');

            $cwhere['c.InsertUserID'] = $author;
            $dwhere['d.InsertUserID'] = $author;
        }

        /// Category ///
        if (isset($search['cat'])) {
            $cats = (array)$search['cat'];

            $cwhere['CategoryID'] = $cats;
            $dwhere['CategoryID'] = $cats;
        }

        /// Type ///
        if (isset($search['types'])) {
            $dsearch = isset($search['types']['discussion']);
            $csearch = isset($search['types']['comment']);
        }

        /// Date ///
        if (isset($search['date-from'])) {
            $dwhere['d.DateInserted >='] = $pdo->quote($search['date-from']);
            $cwhere['c.DateInserted >='] = $pdo->quote($search['date-from']);
        }

        if (isset($search['date-to'])) {
            $dwhere['d.DateInserted <='] = $pdo->quote($search['date-to']);
            $cwhere['c.DateInserted <='] = $pdo->quote($search['date-to']);
        }


        // Now that we have the wheres, lets do the search.
        $vanillaSearch = new VanillaSearchModel();
        $searches = [];

        if ($dsearch) {
            $sql = $vanillaSearch->discussionSql($searchModel, false);
            $sql->select('Type');

            if ($terms) {
                $sql->beginWhereGroup();
                foreach ((array)$dfields as $field) {
                    $sql->orWhere("$field like", $terms, false, false);
                }
                $sql->endWhereGroup();
            }

            foreach ($dwhere as $field => $value) {
                if (is_array($value)) {
                    $sql->whereIn($field, $value);
                } else {
                    $sql->where($field, $value, false, false);
                }
            }

            $searches[] = $sql->getSelect();
            $sql->reset();
        }

        if ($csearch) {
            $sql = $vanillaSearch->commentSql($searchModel, false);
            $sql->select('Type as null');

            if ($terms) {
                foreach ((array)$cfields as $field) {
                    $sql->orWhere("$field like", $terms, false, false);
                }
            }

            foreach ($cwhere as $field => $value) {
                if (is_array($value)) {
                    $sql->whereIn($field, $value);
                } else {
                    $sql->where($field, $value, false, false);
                }
            }

            $searches[] = $sql->getSelect();
            $sql->reset();
        }

        // Perform the search by unioning all of the sql together.
        $Sql = Gdn::sql()
            ->select()
            ->from('_TBL_ s')
            ->orderBy('s.DateInserted', 'desc')
            ->limit($limit, $offset)
            ->getSelect();
        Gdn::sql()->reset();

        $Sql = str_replace(Gdn::database()->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $searches)."\n)", $Sql);
        trace([$Sql], 'SearchSQL');
        $Result = Gdn::database()->query($Sql)->resultArray();

        return $Result;
    }
}

if (!function_exists('searchBoxAdvanced')):

    function searchBoxAdvanced($options = []) {
        $options = array_merge([
            'placeholder' => t('SearchBoxPlaceHolder', 'Search'),
            'value' => null,
        ], $options);

        echo Gdn_Theme::module('AdvancedSearchModule', ['value' => $options['value']]);
    }

endif;
