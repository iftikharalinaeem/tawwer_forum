<?php
/**
 * A plugin that shows a category's customizable "leaderboard of ideas"
 *
 * @copyright 2008-2020 Vanilla Forums, Inc.
 * @license GNU GPLv2
 */

/**
 * BestOfIdeation Module
 *
 * @author    David Barbier <david.barbier@vanillaforums.com>
 * @license   Proprietary
 * @since     4.0
 */

use Vanilla\Web\TwigRenderTrait;

class BestOfIdeationModule extends Gdn_Module {
    use TwigRenderTrait;

    /**
     * The field upon which Ideas will be sorted.
     */
    const SORTING_FIELD = 'Score';
    /**
     * The default amount of top ideas.
     */
    const DEFAULT_AMOUNT = 3;
    /**
     * The maximum amount of top ideas.
     */
    const MAX_AMOUNT = 100;
    /**
     * The duration(in seconds) the top ideas will be kept in cache.
     */
    const CACHE_DURATION = 15 * 60;
    /**
     * The database column where a category's settings for BestOfIdeation module will be saved.
     */
    const SETTINGS_COL_NAME = 'BestOfIdeationSettings';
    /**
     * The current category's ID.
     */
    private $categoryID = null;
    /**
     * A boolean trigger used to determine if the bestOfIdeation module is used for the current category.
     */
    private $isEnabled = false;
    /**
     * An array of inclusive delimitation creation dates so an idea can be considered.
     */
    private $dates = ['From'=>"0001-01-01 00:00:00", 'To'=>"9999-12-31 23:59:59"];
    /**
     * The amount of ideas to display.
     */
    private $limit = BestOfIdeationModule::DEFAULT_AMOUNT;
    /**
     * An array containing the ideas data
     */
    private $ideasDatas = [];  //Will contain the discussions
    /**
     * The cache key for the current category's data
     */
    private $cacheKey = '';

    /** @var CategoryModel */
    private $categoryModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var Gdn_Cache */
    private $cache;

    /**
     * @var BestOfIdeation An instance of this object.
     */
    protected static $instance;

    /**
     * BestOfIdeationModule constructor.
     *
     * This does a few things:
     * -Initializes a few members
     * -Checks for previously cached data
     * --If available, load in member $ideasDatas
     * --If unavailable...
     * ---Load every ideas in the current category branch.
     * ---Sort the loaded ideas.
     * ---Keep only the needed amount in member $ideasDatas.
     * ---Put the result in cache.
     *
     * @param int $categoryID
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function __construct(int $categoryID) {
        $this->categoryModel = Gdn::getContainer()->get(CategoryModel::class);
        $this->discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $this->cache = Gdn::getContainer()->get(Gdn_Cache::class);

        $this->categoryID = $categoryID;
        $this->cacheKey = get_class($this).'_'.$this->categoryID;

        $this->loadSettings();
        if($this->isEnabled){
            if(!$this->getCachedIdeas()){
                $this->loadIdeas();
                $this->sortIdeas();
                $this->limitIdeas();
                $this->setCachedIdeas();
            }
        }

        parent::__construct();
    }

    /**
     *
     * Loads the BestOfIdeation settings for the current?(or specified) category
     *
     * @param int|null $categoryID
     */
    private function loadSettings(int $categoryID = null) {
        if(is_null($categoryID)){
            $categoryID = $this->categoryID;
        }

        $categoryData = $this->categoryModel->getWhere(['CategoryID'=>$categoryID])->resultArray();
        if(count($categoryData)==1) {
            $categoryData = reset($categoryData);

            if (isset($categoryData[BestOfIdeationModule::SETTINGS_COL_NAME])) {
                $categoryData[BestOfIdeationModule::SETTINGS_COL_NAME] = dbdecode($categoryData[BestOfIdeationModule::SETTINGS_COL_NAME]);

                if (count($categoryData[BestOfIdeationModule::SETTINGS_COL_NAME]) > 0) {
                    $this->isEnabled = true;
                    $this->dates = $categoryData[BestOfIdeationModule::SETTINGS_COL_NAME]['Dates'];
                    $this->limit = $categoryData[BestOfIdeationModule::SETTINGS_COL_NAME]['Limit'];
                }
            }
        }
    }

    /**
     *
     * Loads the every idea from the current category & subcategories for the current?(or specified) category.
     *
     * @param int|null $categoryID
     */
    private function loadIdeas(int $categoryID = null) {
        if(is_null($categoryID)){
            $categoryID = $this->categoryID;
        }

        $categoryBranchIDs = $this->getCategoryBranchIDs($categoryID);

        $lookupParameters = [
            'CategoryID'=>$categoryBranchIDs,
            'Type'=>'Idea'
        ];

        if(isset($this->dates['From'])){ $lookupParameters["DateInserted >="] = $this->dates['From']; }
        if(isset($this->dates['To'])){ $lookupParameters["DateInserted <="] = $this->dates['To']; }

        $ideasDatas = $this->discussionModel->getWhere($lookupParameters)->resultObject();

        $this->ideasDatas = $ideasDatas;
    }

    /**
     *
     * Call for an external array sorting method so loaded ideas will be sorted from most popular to least popular.
     *
     * @param array $ideasDatas
     */
    private function sortIdeas(array &$ideasDatas = []){
        if(count($ideasDatas)==0){
            $ideasDatas = &$this->ideasDatas;
        }
        usort($ideasDatas, ['BestOfIdeationModule','uSortDescBySortingField']);
    }

    /**
     *
     * Limit the amount of ideas kept in memory to the exact amount needed.
     *
     * @param array $ideasDatas
     */
    private function limitIdeas(array &$ideasDatas = []){
        if(count($ideasDatas)==0){
            $ideasDatas = &$this->ideasDatas;
        }
        $ideasDatas = array_slice($ideasDatas, 0, $this->limit);
    }

    /**
     *
     * External function used by usort(see sortIdeas()).
     *
     * @param $a
     * @param $b
     * @return int
     */
    private static function uSortDescBySortingField($a, $b){
        return ($a->{BestOfIdeationModule::SORTING_FIELD} > $b->{BestOfIdeationModule::SORTING_FIELD})
            ? -1
            : (($a->{BestOfIdeationModule::SORTING_FIELD} < $b->{BestOfIdeationModule::SORTING_FIELD})
                ? 1
                : 0);
    }

    /**
     *
     * Will return an array of every sub-categories to the specified(inclusive) category ID.
     *
     * @param int|null $categoryID
     * @return array of categoryIDs
     */
    private function getCategoryBranchIDs(int $categoryID = null): array {
        $categoryIDs = [];
        if(is_null($categoryID)){
            $categoryID = $this->categoryID;
        }

        $branchCategories = $this->categoryModel->getSubtree($categoryID, true);

        foreach($branchCategories as $branchCategoryID=>$branchCategory){
            $categoryIDs[] = $branchCategoryID;
        }

        return $categoryIDs;
    }

    /**
     *
     * Looks for pre-existing/cached bestOfIdeation Ideas. If it exists, use it.
     *
     * @return bool (true or false) upon existing cached data found.
     */
    private function getCachedIdeas(): bool {
        $cachedCategoryData = $this->cache->get($this->cacheKey);

        if($cachedCategoryData){
            $this->ideasDatas = $cachedCategoryData;
            return true;
        }

        return false;
    }

    /**
     *
     * Puts the currently loaded bestOfIdeation Ideas in cache.
     *
     * @return bool true on success or false on failure.
     */
    private function setCachedIdeas() {
        return $this->cache->store(
            $this->cacheKey, $this->ideasDatas,
            [Gdn_Cache::FEATURE_EXPIRY => BestOfIdeationModule::CACHE_DURATION]
        );
    }

    /**
     * Return the singleton instance of this class. Should be used instead of instantiating a new BestOfIdeationModule
     * for each discussion.
     *
     * @return BestOfIdeationModule
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new BestOfIdeationModule();
        }
        return self::$instance;
    }

    /**
     *
     * Renders the frontend part of the bestOfIdeation module.
     *
     * @return string (html) of the bestOfIdeation for the current category.
     */
    public function toString(): string {
        $ideaListHtml = '';
        if($this->isEnabled){
            ob_start();
            foreach($this->ideasDatas as $ideaDatas){
                writeDiscussion($ideaDatas, $this, Gdn::session());
            }
            $ideaListHtml.= ob_get_contents();
            ob_end_clean();

            return $this->renderTwig('plugins/ideation/views/bestofideation.twig', [
                'SectionTitle' => Gdn::translate('Top Ideas'),
                'IdeasDatas' => $this->ideasDatas,
                'IdeaListHtml' => $ideaListHtml,
            ]);
        }else{
            return "";
        }
    }
}