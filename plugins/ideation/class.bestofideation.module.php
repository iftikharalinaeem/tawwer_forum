<?php
/**
 * BestOfIdeation Module
 * A module that shows a category's customizable "leaderboard of ideas"
 *
 * @author    David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2008-2020 Vanilla Forums, Inc.
 * @license Proprietary
 * @since     4.0
 */

use Vanilla\Web\TwigRenderTrait;

class BestOfIdeationModule extends Gdn_Module {
    use TwigRenderTrait;

    /**
     * The field upon which Ideas will be sorted.
     */
    private const SORTING_FIELD = 'Score';
    /**
     * The default amount of top ideas.
     */
    public const DEFAULT_AMOUNT = 3;
    /**
     * The maximum amount of top ideas.
     */
    public const MAX_AMOUNT = 100;
    /**
     * The duration(in seconds) the top ideas will be kept in cache.
     */
    private const CACHE_DURATION = 15 * 60;
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
    private $dates = [];
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

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var BestOfIdeationModel */
    private $bestOfIdeationModel;

    /** @var Gdn_Locale */
    private $locale;

    /** @var Gdn_Cache */
    private $cache;

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
     */
    public function __construct(int $categoryID) {
        try {
            $this->discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
            $this->bestOfIdeationModel = Gdn::getContainer()->get(BestOfIdeationModel::class);
            $this->cache = Gdn::getContainer()->get(Gdn_Cache::class);
            $this->locale = Gdn::getContainer()->get(Gdn_Locale::class);
        } catch (Exception $exception) {
            echo 'Exception: ' . $exception->getMessage();
            die();
        }

        $this->categoryID = $categoryID;
        $this->cacheKey = get_class($this) . '_' . $this->categoryID;

        $this->loadSettings();
        if ($this->isEnabled) {
            if (!$this->loadCachedIdeas()) {
                $this->loadIdeas();
                $this->setCachedIdeas();
            }
        }

        parent::__construct();
    }

    /**
     * Loads the BestOfIdeation settings for the current category
     *
     */
    private function loadSettings() {
        $catBOISettings = $this->bestOfIdeationModel->loadConfiguration($this->categoryID);

        if (!empty($catBOISettings)) {
            $this->isEnabled = $catBOISettings['IsEnabled'];
            $this->dates = $catBOISettings['Dates'];
            $this->limit = $catBOISettings['Limit'];
        }
    }

    /**
     * Saves the BestOfIdeation settings for the current category
     * @param array $settings
     */
    public function saveSettings(array $settings) {
        $this->bestOfIdeationModel->saveConfiguration($this->categoryID, $settings);
    }

    /**
     * @return array
     */
    public function getSettings() {
        return [
            'IsEnabled' => $this->isEnabled,
            'Dates' => $this->dates,
            'Limit' => $this->limit
        ];
    }

    /**
     * Loads every idea from the current category & subcategories for the current category.
     */
    private function loadIdeas() {
        $categoryBranchIDs = $this->getCategoryBranchIDs();

        $lookupParameters = [
            'CategoryID' => $categoryBranchIDs,
            'Type' => 'Idea'
        ];

        if (isset($this->dates['From'])) {
            $lookupParameters["DateInserted >="] = $this->dates['From'];
        }
        if (isset($this->dates['To'])) {
            $lookupParameters["DateInserted <="] = $this->dates['To'];
        }

        $ideasDatas = $this->discussionModel->getWhere(
            $lookupParameters,
            BestOfIdeationModule::SORTING_FIELD,
            'desc',
            $this->limit
        )->resultObject();

        $this->ideasDatas = $ideasDatas;
    }

    /**
     * Will return an array of every sub-categories to the current(inclusive) category ID.
     *
     * @return array of categoryIDs
     */
    private function getCategoryBranchIDs(): array {
        $branchCategories = CategoryModel::getSubtree($this->categoryID, true);

        return array_keys($branchCategories);
    }

    /**
     * Looks for pre-existing/cached bestOfIdeation Ideas. If it exists, use it.
     *
     * @return bool (true or false) upon existing cached data found.
     */
    private function loadCachedIdeas(): bool {
        $cachedCategoryData = $this->cache->get($this->cacheKey);

        if ($cachedCategoryData) {
            $this->ideasDatas = $cachedCategoryData;
            return true;
        }

        return false;
    }

    /**
     * Puts the currently loaded bestOfIdeation Ideas in cache.
     *
     * @return bool true on success or false on failure.
     */
    private function setCachedIdeas() {
        return $this->cache->store(
            $this->cacheKey,
            $this->ideasDatas,
            [Gdn_Cache::FEATURE_EXPIRY => BestOfIdeationModule::CACHE_DURATION]
        );
    }

    /**
     * Renders the frontend part of the bestOfIdeation module.
     *
     * @return string (html) of the bestOfIdeation for the current category.
     */
    public function toString(): string {
        $componentHtml = '';

        if ($this->isEnabled) {
            $ideaListHtml = '';

            ob_start();
            foreach ($this->ideasDatas as $ideaDatas) {
                writeDiscussion($ideaDatas, $this, Gdn::session());
            }
            $ideaListHtml .= ob_get_contents();
            ob_end_clean();

            $componentHtml = $this->renderTwig('plugins/ideation/views/bestofideation.twig', [
                'SectionTitle' => $this->locale->translate('Top Ideas'),
                'IdeasDatas' => $this->ideasDatas,
                'IdeaListHtml' => $ideaListHtml,
            ]);
        }
        return $componentHtml;
    }
}
