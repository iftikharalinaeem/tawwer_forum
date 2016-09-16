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
     * Create our instance of the module.
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        parent::__construct($Sender, 'plugins/badges');
    }

    /**
     * Where the module will render by default.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Render the module.
     *
     * @return string
     */
    public function toString() {
        if (checkPermission('Reputation.Badges.Request')) {
            return parent::toString();
        }

        return '';
    }
}
