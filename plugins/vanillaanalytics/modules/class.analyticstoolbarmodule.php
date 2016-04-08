<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */


class AnalyticsToolbarModule extends Gdn_Module {

    private function getData() {
        // Get the data for the 1st level of categories.
        $categories = CategoryModel::$Categories;
        $categoryData = [];
        foreach($categories as $category) {
            if (val('Depth', $category) == 1) {
                $categoryData[val('CategoryID', $category)] = val('Name', $category);
            }
        }
        $this->setData('cat01', $categoryData);

        // Get the data for the intervals.
        $this->setData('Intervals', [
            'hourly' => t('Hourly'),
            'daily' => t('Daily'),
            'weekly' => t('Weekly'),
            'monthly' => t('Monthly')
        ]);
    }

    public function toString() {
        $this->getData();
        return parent::toString();
    }
}
