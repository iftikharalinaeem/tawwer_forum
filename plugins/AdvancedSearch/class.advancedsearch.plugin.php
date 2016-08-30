<?php if (!defined('APPLICATION')) { exit(); }
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AdvancedSearch'] = array(
    'Name' => 'Advanced Search',
    'Description' => 'Enables advanced search on sites.',
    'Version' => '1.0.7',
    'MobileFriendly' => true,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com',
    'Icon' => 'advanced-search.png'
);

/**
 * Class AdvancedSearchPlugin
 */
class AdvancedSearchPlugin extends Gdn_Plugin {
    /// Properties ///

    public static $Types;

    /// Methods ///

    public function __construct() {
        parent::__construct();

        self::$Types = array(
            'discussion' => array('d' => 'discussions'),
            'comment' => array('c' => 'comments')
        );

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

    public function quickSearch($title, $get = array()) {
        $Form = new Gdn_Form();
        $Form->Method = 'get';

        foreach ($get as $key => $value) {
            $Form->addHidden($key, $value);
        }

        $result = ' <div class="QuickSearch">'.
            anchor(sprite('SpSearch'), '#', 'QuickSearchButton').
            '<div class="QuickSearchWrap MenuItems">';

        $result .= $Form->open(array('action' => url('/search'))).
//         $Form->Label('@'.$title, 'search').
            ' '.$Form->textBox('search', array('placeholder' => $title)).
            ' <div class="bwrap"><button type="submit" class="Button" title="'.t('Search').'">'.t('Go').'</button></div>'.
            $Form->close();

        $result .= '</div></div>';

        return $result;
    }

    /// Event Handlers ///

    /**
     * @param AssetModel $Sender
     */
    public function assetModel_styleCSS_handler($Sender) {
        $Sender->addCssFile('advanced-search.css', 'plugins/AdvancedSearch');
    }

    /**
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        if (!inSection('Dashboard')) {
            AdvancedSearchModule::addAssets();

            if (!Gdn::addonManager()->isEnabled('Sphinx', \Vanilla\Addon::TYPE_ADDON)) {
                $Sender->addDefinition('searchAutocomplete', '0');
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
        $args = array();

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

    public function categoriesController_pagerInit_handler($Sender, $Args) {
        $categoryid = $Sender->data('Category.CategoryID');

        if ($categoryid) {
            $name = Gdn_Format::text($Sender->data('Category.Name'));
            if (mb_strwidth($name) > 20) {
                $name = t('category');
            }

            $quickserch = $this->quickSearch(sprintf(t('Search %s'), $name), array('cat' => $categoryid, 'adv' => 1));

            $Pager = $Args['Pager'];
            $Pager->HtmlAfter = $quickserch;
        }
    }

    public function discussionController_pagerInit_handler($Sender, $Args) {
        $quickserch = $this->quickSearch(sprintf(t('Search %s'), t('discussion')), array('discussionid' => $Sender->data('Discussion.DiscussionID')));

        $Pager = $Args['Pager'];
        $Pager->HtmlAfter = $quickserch;
    }

    /**
     * @param Smarty $Sender
     * @param type $Args
     */
    public function gdn_smarty_init_handler($Sender, $Args) {
        $smartyVersion = defined('Smarty::SMARTY_VERSION') ? Smarty::SMARTY_VERSION : $Sender->_version;

        // registerPlugin, introduced in Smart v3, is accessed via __call, so it cannot be detected with method_exists.
        if (version_compare($smartyVersion, '3.0.0', '>=')) {
            $Sender->registerPlugin(
                'function',
                'searchbox_advanced',
                'searchBoxAdvanced'
            );
        } else {
            $Sender->register_function('searchbox_advanced', 'searchBoxAdvanced');
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
            Gdn::userModel()->joinUsers($results['SearchResults'], array('UserID'));
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
     * @param SearchController $Sender
     * @param type $Search
     * @param type $Page
     */
    public function searchController_index_create($Sender, $Search = '', $Page = false) {
        Gdn_Theme::section('SearchResults');

        $this->Sender = $Sender;
        if ($Sender->Head) {
            // Don't index search results pages.
            $Sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex'));
            $Sender->Head->addTag('meta', array('name' => 'googlebot', 'content' => 'noindex'));
        }

        list($Offset, $Limit) = offsetLimit($Page, c('Garden.Search.PerPage', 10));
        $Sender->setData('_Offset', $Offset);
        $Sender->setData('_Limit', $Limit);

        // Do the search.
        $SearchModel = new SearchModel();
        $Sender->setData('SearchResults', array());
        $SearchTerms = Gdn_Format::text($Search);

        if (method_exists($SearchModel, 'advancedSearch')) {
            $Results = $SearchModel->advancedSearch($Sender->Request->get(), $Offset, $Limit);
            $Sender->setData($Results);
            $SearchTerms = $Results['SearchTerms'];


            // Grab the discussion if we are searching it.
            if (isset($Results['CalculatedSearch']['discussionid'])) {
                $DiscussionModel = new DiscussionModel();
                $Discussion = $DiscussionModel->getID($Results['CalculatedSearch']['discussionid']);
                if ($Discussion) {
                    $Cat = CategoryModel::categories(getValue('CategoryID', $Discussion));
//               if (GetValue('PermsDiscussionView', $Cat))
                    $Sender->setData('Discussion', $Discussion);
                }
            }
        } else {
            $Results = $this->devancedSearch($SearchModel, $Sender->Request->get(), $Offset, $Limit);
            $Sender->setData('SearchResults', $Results, true);
            if ($SearchTerms) {
                $SearchTerms = explode(' ', $SearchTerms);
            } else {
                $SearchTerms = array();
            }
        }
        Gdn::userModel()->joinUsers($Sender->Data['SearchResults'], array('UserID'));
        $this->calculateResults($Sender->Data['SearchResults'], $SearchTerms, !$Sender->Request->get('nomark'));

        if (isset($Sender->Data['ChildResults'])) {
            // Join the results.
            $ChildResults = $Sender->Data['ChildResults'];
            unset($Sender->Data['ChildResults']);
            $this->joinResults($Sender->Data['SearchResults'], $ChildResults, $SearchTerms);
        }

        $Sender->setData('SearchTerm', implode(' ', $SearchTerms), true);
        $Sender->setData('SearchTerms', $SearchTerms, true);
        $Sender->setData('From', $Offset + 1);
        $Sender->setData('To', $Offset + count($Sender->Data['SearchResults']));

        // Set the title from the search terms.
        $Sender->title(t('Search'));

        $Sender->CssClass = 'NoPanel';

        // Make a url for related search.
        $get = array_change_key_case($Sender->Request->get());
        unset($get['page']);
        $get['adv'] = 1;
        $url = '/search?'.http_build_query($get);
        $Sender->setData('SearchUrl', $url);


        $this->render('Search');
    }

    protected function joinResults(&$parentResults, $childResults, $searchTerms) {
        // Calculate the results.
        Gdn::userModel()->joinUsers($childResults, array('UserID'));
        $this->calculateResults($childResults, $searchTerms, !Gdn::request()->get('nomark'));
        $childResults = Gdn_DataSet::index($childResults, array('DiscussionID'), array('Unique' => false));
        foreach ($parentResults as &$row) {
            $row['Children'] = getValue($row['DiscussionID'], $childResults, array());
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
        $Breadcrumbs = array();

        foreach ($Data as &$Row) {
            $Row['Title'] = markString($SearchTerms, Gdn_Format::text($Row['Title'], false));
            $Row['Url'] = url($Row['Url'], true);
            $Row['Score'] = (int)$Row['Score'];
//         $Row['Body'] = $Row['Summary'];

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
                    $R = array();
                    foreach ($Categories as $Cat) {
                        $R[] = array(
                            'Name' => $Cat['Name'],
                            'Url' => categoryUrl($Cat)
                        );
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

        $cwhere = array();
        $dwhere = array();

        $dfields = array('d.Name', 'd.Body');
        $cfields = 'c.Body';

        /// Search query ///

        $terms = getValue('search', $search);
        if ($terms) {
            $terms = $pdo->quote('%'.str_replace(array('%', '_'), array('\%', '\_'), $terms).'%');
        }

        // Only search if we have term, user, date, or title to search
        if (!$terms && !isset($search['users']) && !isset($search['date-from']) && !isset($search['date-to']) && !isset($search['title'])) {
            return array();
        }

        /// Title ///

        if (isset($search['title'])) {
            $csearch = false;
            $dwhere['d.Name like'] = $pdo->quote('%'.str_replace(array('%', '_'), array('\%', '\_'), $search['title']).'%');
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
        $searches = array();

        if ($dsearch) {
            $sql = $vanillaSearch->discussionSql($searchModel, false);

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
        $Sql = Gdn::SQL()
            ->select()
            ->from('_TBL_ s')
            ->orderBy('s.DateInserted', 'desc')
            ->limit($limit, $offset)
            ->getSelect();
        Gdn::SQL()->reset();

        $Sql = str_replace(Gdn::database()->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $searches)."\n)", $Sql);
        trace(array($Sql), 'SearchSQL');
        $Result = Gdn::database()->query($Sql)->resultArray();

        return $Result;
    }
}

if (!function_exists('searchBoxAdvanced')):

    function searchBoxAdvanced($options = array()) {
        $options = array_merge(array(
            'placeholder' => t('SearchBoxPlaceHolder', 'Search'),
            'value' => null,
        ), $options);

        echo Gdn_Theme::module('AdvancedSearchModule', array('value' => $options['value']));
    }

endif;
