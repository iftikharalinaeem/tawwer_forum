<?php

/**
 * Renders the "Request This Badge" button.
 */
class RequestBadgeModule extends Gdn_Module {

    public function __construct($Sender = '') {
        parent::__construct($Sender, 'plugins/badges');
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        $HasPermission = Gdn::Session()->CheckPermission('Reputation.Badges.Request');
        if ($HasPermission) {
            return parent::ToString();
        }

        return '';
    }
}
