<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Renders the "Request This Badge" button.
 */
class RequestBadgeModule extends Gdn_Module {

    /**
     *
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        parent::__construct($Sender, 'plugins/badges');
    }

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        $HasPermission = Gdn::session()->checkPermission('Reputation.Badges.Request');
        if ($HasPermission) {
            return parent::toString();
        }

        return '';
    }
}
