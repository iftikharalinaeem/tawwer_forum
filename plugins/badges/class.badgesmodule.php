<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Renders a list of badges given to a particular user.
 */
class BadgesModule extends Gdn_Module {

    /**
     *
     *
     * @param string $Sender
     */
    public function __construct($Sender = '') {
        // Default to current user if none is set
        $this->User = Gdn::controller()->data('Profile', Gdn::session()->User);

        if (!$this->User) {
            return;
        }

        // Get badge list
        $UserBadgeModel = new UserBadgeModel();
        $this->Badges = $UserBadgeModel->getBadges(GetValue('UserID', $this->User))->resultArray();

        // Optionally only show highest badge in each class
        if (C('Reputation.Badges.FilterModuleByClass')) {
            $this->Badges = BadgeModel::filterByClass($this->Badges);
        }


        parent::__construct($Sender, 'plugins/badges');
    }

    /**
     *
     *
     * @return mixed
     */
    public function assetTarget() {
        return C('Badges.BadgesModule.Target', 'Panel');
    }

    /**
     *
     *
     * @return string|void
     */
    public function toString() {
        if (!$this->User) {
            return;
        }

        return parent::toString();
    }
}
