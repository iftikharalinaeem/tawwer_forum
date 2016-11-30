<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */


class AnalyticsToolbarModule extends Gdn_Module {

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

    private function getData() {
        // Get the data for the 1st level of categories.
        $categories = CategoryModel::categories();
        $categoryData = [];
        foreach($categories as $category) {
            if (val('Depth', $category) == 1) {
                $categoryData[val('CategoryID', $category)] = val('Name', $category);
            }
        }
        $this->setData('cat01', $categoryData);

        // Translate the interval titles
        foreach($this->intervals as &$interval) {
            $interval['text'] = t($interval['text']);
        }

        // Set the data for the intervals.
        $this->setData('Intervals', $this->intervals);
    }

    public function toString() {
        $this->getData();
        return parent::toString();
    }
}
