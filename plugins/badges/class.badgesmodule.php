<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

/**
 * Renders a list of badges given to a particular user.
 */
class BadgesModule extends Gdn_Module {

    /** @var int Max number of badges to retrieve. */
    /* public for the sake of backwards compatibility with Smarty. */
    public $Limit;

    /**
     * Create the module instance.
     *
     * @param string $sender
     * @param int $limit
     */
    public function __construct($sender = '', $aplicationFolder = "", int $limit = null) {
        // Default to current user if none is set
        $this->User = Gdn::controller()->data('Profile', Gdn::session()->User);

        if (!$this->User) {
            return;
        }

        $this->Limit = $limit;

        parent::__construct($sender, 'plugins/badges');
    }

    /**
     * Set Limit
     *
     * @return boolean
     */
    public function prepare() {
        // Get badge list
        $userBadgeModel = new UserBadgeModel();
        $this->Badges = $userBadgeModel->getBadges(val("UserID", $this->User), $this->Limit)->resultArray();

        // Optionally only show highest badge in each class
        if (c('Reputation.Badges.FilterModuleByClass')) {
            $this->Badges = BadgeModel::filterByClass($this->Badges);
        }

        return true;
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
     * @return bool|string HTML view
     */
    public function toString() {
        if (!$this->User) {
            return;
        }

        return parent::toString();
    }
}
