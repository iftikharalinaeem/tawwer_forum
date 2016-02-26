<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */


class AnalyticsToolbarModule extends Gdn_Module {

    private function getData() {
        // Get the data for the 1st level of categories.
        // This is hardcoded for the demo so that we can query against Icrontic.
        $categories = [
            38	=> 'Leaders',
            19	=> 'Gaming',
            11	=> 'Hardware',
            10	=> 'Science & Tech',
            22	=> 'Internet & Media',
            57	=> 'Spyware & Virus Removal',
            120	=> 'Life',
            20	=> 'Community'
        ];
        $this->setData('cat01', $categories);


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
