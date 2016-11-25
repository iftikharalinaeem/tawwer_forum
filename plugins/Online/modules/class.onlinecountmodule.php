<?php

/**
 * Online Plugin - OnlineCountModule
 *
 * This module displays a count of users who are currently online.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */
class OnlineCountModule extends Gdn_Module {

    public $showGuests = true;
    public $selector = null;
    public $selectorID = null;
    public $selectorField = null;

    public function __construct($sender = null) {
        parent::__construct($sender);

        $this->selector = 'auto';
    }

    public function __set($name, $value) {
        switch ($name) {
            case 'CategoryID':
                $this->selector = 'category';
                $this->selectorID = $value;
                $this->selectorField = 'CategoryID';
                break;

            case 'DiscussionID':
                $this->selector = 'discussion';
                $this->selectorID = $value;
                $this->selectorField = 'DiscussionID';
                break;
        }
    }

    public function getData() {

        if ($this->selector == 'auto') {

            $location = OnlinePlugin::whereAmI(Gdn::controller()->ResolvedPath, Gdn::controller()->ReflectArgs);

            switch ($location) {
                case 'category':
                case 'discussion':
                    $this->showGuests = false;
                    $this->selector = 'category';
                    $this->selectorField = 'CategoryID';

                    if ($location == 'discussion') {
                        $this->selectorID = Gdn::controller()->data('Discussion.CategoryID');
                    } else {
                        $this->selectorID = Gdn::controller()->data('Category.CategoryID');
                    }

                    break;

                case 'limbo':
                case 'all':
                    $this->showGuests = true;
                    $this->selector = 'all';
                    $this->selectorID = null;
                    $this->selectorField = null;
                    break;
            }
        }

        $count = OnlinePlugin::instance()->onlineCount($this->selector, $this->selectorID, $this->selectorField);
        $guestCount = OnlinePlugin::guests();
        if (!$guestCount) {
            $guestCount = 0;
        }

        return [$count, $guestCount];
    }

    public function ToString() {
        list($count, $guestCount) = $this->getData();
        $combinedCount = $count + $guestCount;

        $trackedCount = $this->showGuests ? $combinedCount : $count;
        $formattedCount = Gdn_Format::bigNumber($trackedCount, 'html');

        $outputString = '';
        ob_start();
        ?>
        <div class="OnlineCount"><?php echo sprintf(T("%s viewing"), $formattedCount); ?></div>
        <?php
        $outputString = ob_get_contents();
        @ob_end_clean();

        return $outputString;
    }

}
