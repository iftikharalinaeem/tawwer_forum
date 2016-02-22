<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AdvancedSearch'] = array(
    'Name' => 'Advanced Search',
    'Description' => 'Enables advanced search on sites.',
    'Version' => '1.0.6',
    'MobileFriendly' => true,
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

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

        if (Gdn::PluginManager()->IsEnabled('Sphinx')) {
            if (Gdn::PluginManager()->IsEnabled('QnA')) {
                self::$Types['discussion']['question'] = 'questions';
                self::$Types['comment']['answer'] = 'answers';
            }

            if (Gdn::PluginManager()->IsEnabled('Polls')) {
                self::$Types['discussion']['poll'] = 'polls';
            }

            if (Gdn::ApplicationManager()->CheckApplication('Pages')) {
                self::$Types['page']['p'] = 'docs';
            }

            if (Gdn::ApplicationManager()->CheckApplication('Groups')) {
                self::$Types['group']['group'] = 'groups';
            }
        }

        $this->FireEvent('Init');
    }

    public function quickSearch($title, $get = array()) {
        $Form = new Gdn_Form();
        $Form->Method = 'get';

        foreach ($get as $key => $value) {
            $Form->AddHidden($key, $value);
        }

        $result = ' <div class="QuickSearch">'.
            Anchor(Sprite('SpSearch'), '#', 'QuickSearchButton').
            '<div class="QuickSearchWrap MenuItems">';

        $result .= $Form->Open(array('action' => Url('/search'))).
//         $Form->Label('@'.$title, 'search').
            ' '.$Form->TextBox('search', array('placeholder' => $title)).
            ' <div class="bwrap"><button type="submit" class="Button" title="'.T('Search').'">'.T('Go').'</button></div>'.
            $Form->Close();

        $result .= '</div></div>';

        return $result;
    }

    /// Event Handlers ///

    /**
     * @param AssetModel $Sender
     */
    public function assetModel_styleCSS_handler($Sender) {
        $Sender->AddCssFile('advanced-search.css', 'plugins/AdvancedSearch');
    }

    /**
     * @param Gdn_Controller $Sender
     */
    public function base_render_before($Sender) {
        if (!InSection('Dashboard')) {
            AdvancedSearchModule::addAssets();

            if (!Gdn::PluginManager()->IsEnabled('Sphinx')) {
                $Sender->AddDefinition('searchAutocomplete', '0');
            }
        }
    }

    /**
     * Add the quick search the discussions list.
     * @param DiscussionsController $Sender
     * @param array $Args
     */
    public function discussionsController_pagerInit_handler($Sender, $Args) {
        $name = T('SearchBoxPlaceHolder', 'Search');
        $args = array();

        // See if there are any tags on the page.
        $tags = $Sender->Data('Tags');
        if (is_array($tags) && class_exists('TagModel')) {
            $tags = TagModel::instance()->unpivot($tags);
            $tags = array_column($tags, 'Name');
            $args['tags'] = implode(',', $tags);
            $args['tags-op'] = 'and';
            $args['adv'] = 1;

            $name = sprintf(T('Search %s'), $Sender->Title());
        }

        $quickserch = $this->quickSearch($name, $args);
        $Pager = $Args['Pager'];
        $Pager->HtmlAfter = $quickserch;
    }

    public function categoriesController_pagerInit_handler($Sender, $Args) {
        $categoryid = $Sender->Data('Category.CategoryID');

        if ($categoryid) {
            $name = Gdn_Format::Text($Sender->Data('Category.Name'));
            if (mb_strwidth($name) > 20) {
                $name = T('category');
            }

            $quickserch = $this->quickSearch(sprintf(T('Search %s'), $name), array('cat' => $categoryid, 'adv' => 1));

            $Pager = $Args['Pager'];
            $Pager->HtmlAfter = $quickserch;
        }
    }

    public function discussionController_pagerInit_handler($Sender, $Args) {
        $quickserch = $this->quickSearch(sprintf(T('Search %s'), T('discussion')), array('discussionid' => $Sender->Data('Discussion.DiscussionID')));

        $Pager = $Args['Pager'];
        $Pager->HtmlAfter = $quickserch;
    }

    /**
     * @param Smarty $Sender
     * @param type $Args
     */
    public function gdn_smarty_init_handler($Sender, $Args) {
        $Sender->register_function('searchbox_advanced', 'searchBoxAdvanced');
    }

    public function searchController_autoComplete_create($sender, $term, $limit = 5) {
        $searchModel = new SearchModel();
        $get = $sender->Request->Get();
        $get['search'] = $term;
        $results = $searchModel->autoComplete($get, $limit);
        $this->CalculateResults($results['SearchResults'], $results['SearchTerms'], !$sender->Request->Get('nomark'), 100);

        if (isset($get['discussionid'])) {
            // This is searching a discussion so make the user the title.
            Gdn::UserModel()->JoinUsers($results['SearchResults'], array('UserID'));
            foreach ($results['SearchResults'] as &$row) {
                $row['Title'] = htmlspecialchars($row['Name']);
            }
        }

        header('Content-Type: application/json; charset=utf8');
        die(json_encode($results['SearchResults']));
    }

    public function searchController_groupAutoComplete_create($sender, $term, $limit = 5) {
        $searchModel = new SearchModel();
        $get = $sender->Request->Get();
        $get['search'] = $term;
        $results = $searchModel->groupAutoComplete($get, $limit);
        $this->CalculateResults($results['SearchResults'], $results['SearchTerms'], !$sender->Request->Get('nomark'), 100);

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
        Gdn_Theme::Section('SearchResults');

        $this->Sender = $Sender;
        if ($Sender->Head) {
            // Don't index search results pages.
            $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex'));
            $Sender->Head->AddTag('meta', array('name' => 'googlebot', 'content' => 'noindex'));
        }

        list($Offset, $Limit) = OffsetLimit($Page, C('Garden.Search.PerPage', 10));
        $Sender->SetData('_Offset', $Offset);
        $Sender->SetData('_Limit', $Limit);

        // Do the search.
        $SearchModel = new SearchModel();
        $Sender->SetData('SearchResults', array());
        $SearchTerms = Gdn_Format::Text($Search);

        if (method_exists($SearchModel, 'advancedSearch')) {
            $Results = $SearchModel->AdvancedSearch($Sender->Request->Get(), $Offset, $Limit);
            $Sender->SetData($Results);
            $SearchTerms = $Results['SearchTerms'];


            // Grab the discussion if we are searching it.
            if (isset($Results['CalculatedSearch']['discussionid'])) {
                $DiscussionModel = new DiscussionModel();
                $Discussion = $DiscussionModel->GetID($Results['CalculatedSearch']['discussionid']);
                if ($Discussion) {
                    $Cat = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
//               if (GetValue('PermsDiscussionView', $Cat))
                    $Sender->SetData('Discussion', $Discussion);
                }
            }

        } else {
            $Results = $this->devancedSearch($SearchModel, $Sender->Request->Get(), $Offset, $Limit);
            $Sender->SetData('SearchResults', $Results, true);
            if ($SearchTerms)
                $SearchTerms = explode(' ', $SearchTerms);
            else
                $SearchTerms = array();
        }
        Gdn::UserModel()->JoinUsers($Sender->Data['SearchResults'], array('UserID'));
        $this->CalculateResults($Sender->Data['SearchResults'], $SearchTerms, !$Sender->Request->Get('nomark'));

        if (isset($Sender->Data['ChildResults'])) {
            // Join the results.
            $ChildResults = $Sender->Data['ChildResults'];
            unset($Sender->Data['ChildResults']);
            $this->joinResults($Sender->Data['SearchResults'], $ChildResults, $SearchTerms);
        }

        $Sender->SetData('SearchTerm', implode(' ', $SearchTerms), true);
        $Sender->SetData('SearchTerms', $SearchTerms, true);
        $Sender->SetData('From', $Offset + 1);
        $Sender->SetData('To', $Offset + count($Sender->Data['SearchResults']));

        // Set the title from the search terms.
        $Sender->Title(T('Search'));

        $Sender->CssClass = 'NoPanel';

        // Make a url for related search.
        $get = array_change_key_case($Sender->Request->Get());
        unset($get['page']);
        $get['adv'] = 1;
        $url = '/search?'.http_build_query($get);
        $Sender->SetData('SearchUrl', $url);


        $this->Render('Search');
    }

    protected function joinResults(&$parentResults, $childResults, $searchTerms) {
        // Calculate the results.
        Gdn::UserModel()->JoinUsers($childResults, array('UserID'));
        $this->CalculateResults($childResults, $searchTerms, !Gdn::Request()->Get('nomark'));
        $childResults = Gdn_DataSet::Index($childResults, array('DiscussionID'), array('Unique' => false));
        foreach ($parentResults as &$row) {
            $row['Children'] = GetValue($row['DiscussionID'], $childResults, array());
        }
    }

    protected function calculateResults(&$Data, $SearchTerms, $Mark = true, $Length = 200) {
        if ($SearchTerms && !is_array($SearchTerms)) {
            $SearchTerms = explode(' ', $SearchTerms);
            $SearchTerms = array_filter($SearchTerms, function ($v) {
                return trim($v);
            });
        }

        if (Debug() && method_exists('SearchModel', 'addNotes')) {
            $calc = function ($r, $t) {
                return SearchModel::addNotes($r, $t);
            };
        } else {
            $calc = function ($r) {
                return null;
            };
        }

        $UseCategories = C('Vanilla.Categories.Use');
        $Breadcrumbs = array();

        foreach ($Data as &$Row) {
            $Row['Title'] = MarkString($SearchTerms, Gdn_Format::Text($Row['Title'], false));
            $Row['Url'] = Url($Row['Url'], true);
            $Row['Score'] = (int)$Row['Score'];
//         $Row['Body'] = $Row['Summary'];

            $Summary = Gdn_Format::To($Row['Summary'], $Row['Format']);
            $media = Search::extractMedia($Summary);
            $Row['Media'] = $media;

            $Row['Summary'] = SearchExcerpt(htmlspecialchars(Gdn_Format::PlainText($Summary, 'Raw')), $SearchTerms, $Length);
            $Row['Summary'] = Emoji::instance()->translateToHtml($Row['Summary']);

            $Row['Format'] = 'Html';
            $Row['DateHtml'] = Gdn_Format::Date($Row['DateInserted'], 'html');
            $Row['Notes'] = $calc($Row, $SearchTerms);

            $Type = strtolower(GetValue('Type', $Row));
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
                    $Categories = CategoryModel::GetAncestors($CategoryID);
                    $R = array();
                    foreach ($Categories as $Cat) {
                        $R[] = array(
                            'Name' => $Cat['Name'],
                            'Url' => CategoryUrl($Cat)
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
        $pdo = Gdn::Database()->Connection();

        $csearch = true;
        $dsearch = true;

        $cwhere = array();
        $dwhere = array();

        $dfields = array('d.Name', 'd.Body');
        $cfields = 'c.Body';

        /// Search query ///

        $terms = GetValue('search', $search);
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
            $sql = $vanillaSearch->DiscussionSql($searchModel, false);

            if ($terms) {
                $sql->BeginWhereGroup();
                foreach ((array)$dfields as $field) {
                    $sql->OrWhere("$field like", $terms, false, false);
                }
                $sql->EndWhereGroup();
            }

            foreach ($dwhere as $field => $value) {
                if (is_array($value)) {
                    $sql->WhereIn($field, $value);
                } else {
                    $sql->Where($field, $value, false, false);
                }
            }

            $searches[] = $sql->GetSelect();
            $sql->Reset();
        }

        if ($csearch) {
            $sql = $vanillaSearch->CommentSql($searchModel, false);

            if ($terms) {
                foreach ((array)$cfields as $field) {
                    $sql->OrWhere("$field like", $terms, false, false);
                }
            }

            foreach ($cwhere as $field => $value) {
                if (is_array($value)) {
                    $sql->WhereIn($field, $value);
                } else {
                    $sql->Where($field, $value, false, false);
                }
            }

            $searches[] = $sql->GetSelect();
            $sql->Reset();
        }

        // Perform the search by unioning all of the sql together.
        $Sql = Gdn::SQL()
            ->Select()
            ->From('_TBL_ s')
            ->OrderBy('s.DateInserted', 'desc')
            ->Limit($limit, $offset)
            ->GetSelect();
        Gdn::SQL()->Reset();

        $Sql = str_replace(Gdn::Database()->DatabasePrefix.'_TBL_', "(\n".implode("\nunion all\n", $searches)."\n)", $Sql);
        Trace(array($Sql), 'SearchSQL');
        $Result = Gdn::Database()->Query($Sql)->ResultArray();

        return $Result;
    }
}

if (!function_exists('searchBoxAdvanced')):

    function searchBoxAdvanced($options = array()) {
        $options = array_merge(array(
            'placeholder' => T('SearchBoxPlaceHolder', 'Search'),
            'value' => null,
        ), $options);

        echo Gdn_Theme::Module('AdvancedSearchModule', array('value' => $options['value']));
    }

endif;
