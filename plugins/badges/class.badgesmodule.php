<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Renders a list of badges given to a particular user.
 */
class BadgesModule extends Gdn_Module {

    public $Limit = false;

    /**
     * Create the module instance.
     *
     * @param string $sender
     */
    public function __construct($sender = '', $limit = false) {
        // Default to current user if none is set
        $this->User = Gdn::controller()->data('Profile', Gdn::session()->User);

        $this->Limit = $limit;

        if (!$this->User) {
            return;
        }

        // Get badge list
        $userBadgeModel = new UserBadgeModel();
        $this->Badges = $userBadgeModel->getBadges(val('UserID', $this->User), $this->Limit)->resultArray();

        // Optionally only show highest badge in each class
        if (c('Reputation.Badges.FilterModuleByClass')) {
            $this->Badges = BadgeModel::filterByClass($this->Badges);
        }


        parent::__construct($sender, 'plugins/badges');
    }

    /**
     * Where the module will render by default.
     *
     * @return mixed
     */
    public function assetTarget() {
        return c('Badges.BadgesModule.Target', 'Panel');
    }

    /**
     * Render the module.
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
