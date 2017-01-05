<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */


class AnalyticsToolbarModule extends Gdn_Module {

    /**
     * @var bool Whether to show the category filter.
     */
    private $showCategoryFilter = true;

    /**
     * AnalyticsToolbarModule constructor.
     * @param bool $showCategoryFilter Whether to show the category filter.
     */
    public function __construct($showCategoryFilter = true) {
        $this->showCategoryFilter = $showCategoryFilter;
        parent::__construct();
    }

    private $intervals = [
        'hourly' => [
            'text' => 'Hourly',
            'data-seconds' => 3600
        ],
        'daily' => [
            'text' => 'Daily',
            'data-seconds' => 86400
        ],
        'weekly' => [
            'text' => 'Weekly',
            'data-seconds' => 604800
        ],
        'monthly' => [
            'text' => 'Monthly',
            'data-seconds' => 2620800

        ]
    ];

    /**
     * Gets the data for the category filter.
     *
     * @throws Exception
     */
    private function getCategoryFilter() {
        // Get the data for the 1st level of categories.
        $categories = CategoryModel::getChildren(-1);
        $categoryData = [];

        foreach($categories as $category) {
            $categoryData[val('CategoryID', $category)] = val('Name', $category);
        }
        $attr = ['IncludeNull' => t('All Categories')];
        $heading = t('Category');

        $this->EventArguments['Attributes'] = &$attr;
        $this->EventArguments['Heading'] = &$heading;
        $this->EventArguments['Categories'] = &$categoryData;
        $this->fireAs('AnalyticsController');
        $this->fireEvent('AnalyticsCategoryFilter');

        $this->setData('catAttr', $attr);
        $this->setData('cat01', $categoryData);
        $this->setData('heading', $heading);
    }

    private function getData() {

        $this->setData('showCategoryFilter', $this->showCategoryFilter);
        if ($this->showCategoryFilter) {
            $this->getCategoryFilter();
        }

        // Translate the interval titles
        foreach($this->intervals as &$interval) {
            $interval['text'] = t($interval['text']);
        }

        // Set the data for the intervals.
        $this->setData('Intervals', $this->intervals);
    }

    public function toString() {
        $this->getData();
        include_once $this->fetchViewLocation('analyticsmodules_helper_functions', 'plugins/vanillaanalytics');
        return parent::toString();
    }
}
