<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */

// We can't rely on our autoloader in a plugin.
require_once(dirname(__FILE__).'/class.badgesappcontroller.php');

/**
 * Public badges lists.
 *
 * @since 1.0.0
 * @package Reputation
 */
class BadgesController extends BadgesAppController {

    /**
     * View badges.
     *
     * @since 1.0.0
     * @access public
     */
    public function index() {
        $this->permission('Reputation.Badges.View');
        $this->setData('Badges', $this->BadgeModel->getFilteredList(Gdn::session()->UserID));

        $Module = new LeaderBoardModule();
        $Module->SlotType = 'a';
        $Module->getData(c('Reputation.Badges.LeaderboardLimit', 25));
        $this->addModule($Module);

        $this->MasterView = 'default';
        $this->render();
    }

    /**
     *
     */
    public function all() {
        $this->Permission('Reputation.Badges.View');
        $Badges = $this->BadgeModel->getList()->resultArray();

        if ($Badges) {
            $this->BadgeModel->calculate($Badges);
        }
        $this->setData('Badges', $Badges);
        unset($Badges);

        $Module = new LeaderBoardModule();
        $Module->SlotType = 'a';
        $Module->getData(c('Reputation.Badges.LeaderboardLimit', 25));
        $this->addModule($Module);

        $this->MasterView = 'default';
        $this->render('index');
    }

    /**
     * Endpoint for hub sync.
     */
    public function syncNode() {
        if (!class_exists('SiteNodePlugin')) {
            return;
        }

        /* @var SiteNodePlugin $nodePlugin */
        $nodePlugin = SiteNodePlugin::instance();

        $result = $nodePlugin->hubApi('/badges/all.json', 'GET', array(), true);

        $badges = $result['Badges'];
        foreach ($badges as $badge) {
            $set = arrayTranslate($badge, array('Slug', 'Name', 'Photo', 'Body', 'Points', 'Active', 'Visible', 'Class', 'Threshold', 'Level'));
            $this->BadgeModel->save($set);
            $this->BadgeModel->Validation->results(true);
        }
    }

    /**
     *
     */
    public function user() {
        $this->permission('Reputation.Badges.View');

        $UserID = Gdn::request()->getValue('UserID', false);
        $this->setData('Badges', $this->BadgeModel->getFilteredList($UserID, true));

        $Module = new LeaderBoardModule();
        $Module->SlotType = 'a';
        $Module->getData(c('Reputation.Badges.LeaderboardLimit', 25));
        $this->addModule($Module);

        $this->MasterView = 'default';
        $this->render('index');
    }
}
