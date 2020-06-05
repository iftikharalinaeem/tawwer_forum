<?php

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Psr\Container\ContainerInterface;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\AdvancedSearch\Models\SearchRecordTypeDiscussion;
use Vanilla\AdvancedSearch\Models\SearchRecordTypeComment;
use Vanilla\AdvancedSearch\Models\SearchRecordTypeProvider;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Contracts\Search\SearchRecordTypeInterface;
use Garden\Container\Container;
use Vanilla\Formatting\FormatService;

/**
 * Class AdvancedSearchPlugin
 */
class AdvancedSearchPlugin extends Gdn_Plugin {
    /// Properties ///

    /**
     * @var array
     * @deprecated
     */
    //public static $Types = [];

    /**
     * @var AddonManager
     */
    private $addonManager;

    /** @var ContainerInterface */
    private $container;

    /**
     * @var SearchModel
     */
    private $searchModel;

    /** @var FormatService */
    private $formatService;

    /// Methods ///

    /**
     * Construct the advanced search plugin.
     *
     * @param AddonManager $addonManager The addon manager dependency.
     * @param ContainerInterface $container
     *
     * @param FormatService $formatService
     */
    public function __construct(AddonManager $addonManager, ContainerInterface $container, FormatService $formatService) {
        parent::__construct();

        $this->addonManager = $addonManager;
        $this->container = $container;
        $this->formatService = $formatService;

        $this->fireEvent('Init');
    }

    public function container_init(Container $dic) {
        $dic
            ->rule(SearchRecordTypeProviderInterface::class)
            ->setClass(SearchRecordTypeProvider::class)
            ->addCall('setType', [new SearchRecordTypeDiscussion()])
            ->addCall('setType', [new SearchRecordTypeComment()])
            ->addCall('addProviderGroup', [SearchRecordTypeDiscussion::PROVIDER_GROUP])
            ->addAlias('SearchRecordTypeProvider')
            ->setShared(true)

        ;
    }

    /**
     * Get the SearchModel.
     * We lazy load this so that other plugins can update the container rules with the container_init event.
     *
     * @return SearchModel
     */
    private function getSearchModel() {
        if (!isset($this->searchModel)) {
            $this->searchModel = $this->container->get(SearchModel::class);
        }

        return $this->searchModel;
    }

    public function quickSearch($title, $get = []) {
        if (Gdn::themeFeatures()->useDataDrivenTheme()) {
            return;
        }
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
            ' '.$form->textBox('search', ['placeholder' => $title, 'class' => 'js-search']).
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

            if (!$this->addonManager->isEnabled('Sphinx', \Vanilla\Addon::TYPE_ADDON)) {
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
        $searchModel = $this->getSearchModel();

        if (!$searchModel instanceof SphinxSearchModel) {
            throw new \Gdn_UserException("This functionality requires Sphinx Search.", 500);
        }

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
        $searchModel = $this->getSearchModel();

        if (!$searchModel instanceof SphinxSearchModel) {
            throw new \Gdn_UserException("This functionality requires Sphinx Search.", 500);
        }

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
     * @param string $search
     * @param string $page
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
        $searchModel = $this->getSearchModel();
        $sender->setData('SearchResults', []);
        $searchTerms = Gdn_Format::text($search);

        if ($searchModel instanceof SphinxSearchModel) {
            $results = $searchModel->advancedSearch($sender->Request->get(), $offset, $limit);
            $sender->setData($results);
            $searchTerms = $results['SearchTerms'];
            // Grab the discussion if we are searching it.
            if (isset($results['CalculatedSearch']['discussionid'])) {
                $discussionModel = new DiscussionModel();
                $discussion = $discussionModel->getID($results['CalculatedSearch']['discussionid']);
                if ($discussion) {
                    $cat = CategoryModel::categories(getValue('CategoryID', $discussion));
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

        if (debug() && $this->addonManager->isEnabled('Sphinx', Addon::TYPE_ADDON)) {
            $calc = function ($r, $t) {
                return SphinxSearchModel::addNotes($r, $t);
            };
        } else {
            $calc = function ($r) {
                return null;
            };
        }

        $UseCategories = c('Vanilla.Categories.Use');
        $Breadcrumbs = [];

        foreach ($Data as &$Row) {
            $Row['Title'] = htmlspecialchars($Row['Title']);
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
            $Row['Summary'] = $Row['Summary'] ?? '';

            $images = $this->formatService->parseImages($Row['Summary'], $Row['Format']);
            $Row['ImageUrls'] = $images["ImageUrls"];
            $Row['ImageAttrs'] = $images["ImageAttrs"] ?? [];

            $Row['Summary'] = searchExcerpt($this->formatService->renderPlainText($Row['Summary'], $Row['Format']), $SearchTerms, $Length);

            // Left behind for compatibility with existing view overrides.
            // They won't see the media previews unless they are updated, but at least they won't break.
            $Row['Media'] = [];
            $Row['Summary'] = Emoji::instance()->translateToHtml($Row['Summary']);

            $Row['Format'] = 'Html';
            $Row['DateHtml'] = Gdn_Format::date($Row['DateInserted'], 'html');
            $Row['Notes'] = $calc($Row, $SearchTerms);

            $Row['Type'] = $Row['Type'] ?? SearchRecordTypeDiscussion::API_TYPE_KEY;

            if (!($Row['Type'] !== SearchRecordTypeDiscussion::API_TYPE_KEY && is_array($Row['Breadcrumbs']))) {
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
    }

    /**
     * Search through the database and return results matching the supplied $search input.
     *
     * @param $searchModel
     * @param $search
     * @param $offset
     * @param $limit
     * @param bool|string $clean Whether to clean $search or not. Can be set to "api" which changes the process a bit.
     * @return array
     */
    static function devancedSearch($searchModel, $search, $offset, $limit, $clean = true) {
        $isAPI = $clean === 'api';
        $search = Search::cleanSearch($search, $isAPI);

        $pdo = Gdn::database()->connection();

        $csearch = true;
        $dsearch = true;

        $cwhere = [];
        $dwhere = [];

        $dfields = ['d.Name', 'd.Body'];
        $cfields = 'c.Body';

        /// Search query ///

        $terms = val('search', $search);
        if ($terms) {
            $terms = $pdo->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $terms).'%');
        }

        // Only search if we have term, user, date, or title to search
        $hasDateRange = !$isAPI && (isset($search['date-from']) || isset($search['date-to']));
        $hasDateFilter = ($isAPI && isset($search['date-filters']));
        if (!$terms && !isset($search['users']) && !$hasDateRange && !$hasDateFilter && !isset($search['title'])) {
            return [];
        }

        /// Title ///

        if (isset($search['title'])) {
            $csearch = false;
            $dwhere['d.Name like'] = $pdo->quote('%'.str_replace(['%', '_'], ['\%', '\_'], $search['title']).'%');

            // In case comments search are enabled late on.
            $cwhere['d.Name like'] = $dwhere['d.Name like'];
        }

        /// Author ///
        if (isset($search['users'])) {
            $author = array_column($search['users'], 'UserID');

            $cwhere['c.InsertUserID'] = $author;
            $dwhere['d.InsertUserID'] = $author;
        }

        /// Discussion ///
        if (isset($search['discussionid'])) {
            $cwhere['d.DiscussionID'] = (int)$search['discussionid'];
        }

        /// Category ///
        if (isset($search['cat'])) {
            $cats = (array)$search['cat'];

            $cwhere['CategoryID'] = $cats;
            $dwhere['CategoryID'] = $cats;
        }

        /// Type ///
        if (!empty($search['types'])) {
            $disableComments = true;
            $disableDiscussions = true;
            /** @var SearchRecordTypeInterface $recordType */
            foreach ($search['types'] as $recordType) {
                if ($recordType instanceof SearchRecordTypeDiscussion) {
                    $disableDiscussions = false;
                } elseif ($recordType instanceof SearchRecordTypeComment) {
                    $disableComments = false;
                }
            }

            $dsearch = !$disableDiscussions;
            $csearch = !$disableComments;
        }
        /// Date ///
        if (!$isAPI) {
            if (isset($search['date-from'])) {
                $dwhere['d.DateInserted >='] = $pdo->quote($search['date-from']);
                $cwhere['c.DateInserted >='] = $pdo->quote($search['date-from']);
            }

            if (isset($search['date-to'])) {
                $dwhere['d.DateInserted <='] = $pdo->quote($search['date-to']);
                $cwhere['c.DateInserted <='] = $pdo->quote($search['date-to']);
            }
        } elseif (isset($search['date-filters'])) {
            $dtZone = new DateTimeZone('UTC');
            foreach($search['date-filters'] as $field => $value) {
                $dt = new DateTime('@'.$value->getTimestamp());
                $dt->setTimezone($dtZone);
                $value = $pdo->quote($dt->format(MYSQL_DATE_FORMAT));

                $dwhere['d.'.$field] = $value;
                $cwhere['c.'.$field] = $value;
            }
        }


        // Now that we have the wheres, lets do the search.
        $vanillaSearch = new VanillaSearchModel();
        $searchModel->EventArguments['Limit'] = $limit;
        $searchModel->EventArguments['Offset'] = $offset;
        $searches = [];

        if ($dsearch) {
            $sql = $vanillaSearch->discussionSql($searchModel, false);
            $sql->select('d.Type');

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
            $sql->select('null as Type');

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
            ->from('_TBL_ s', false)
            ->orderBy('s.DateInserted', 'desc')
            ->limit($limit, $offset)
            ->getSelect();
        Gdn::sql()->reset();

        $union = '';
        foreach ($searches as $subQuery) {
            $union .= empty($union) ? '' : ' union all ';
            $union .= ' ( '.$subQuery.' ) ';
        }
        $Sql = str_replace(Gdn::database()->DatabasePrefix.'_TBL_', "(\n".$union."\n)", $Sql);

        $Result = Gdn::database()->query($Sql)->resultArray();

        foreach ($Result as &$row) {
            if ($row['RecordType'] === 'Comment') {
                $row['Title'] = sprintft('Re: %s', $row['Title']);
            }
        }

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
