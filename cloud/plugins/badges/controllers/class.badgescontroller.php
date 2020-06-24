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

        $module = new LeaderBoardModule();
        $module->SlotType = 'a';
        $module->getData(c('Reputation.Badges.LeaderboardLimit', 25));
        $this->addModule($module);

        $this->MasterView = 'default';
        $this->render();
    }

    /**
     * Show a list of all badges.
     */
    public function all() {
        $this->permission('Reputation.Badges.View');
        $badges = $this->BadgeModel->getList()->resultArray();

        if ($badges) {
            $this->BadgeModel->calculate($badges);
        }
        $this->setData('Badges', $badges);
        unset($badges);

        $module = new LeaderBoardModule();
        $module->SlotType = 'a';
        $module->getData(c('Reputation.Badges.LeaderboardLimit', 25));
        $this->addModule($module);

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

        $result = $nodePlugin->hubApi('/badges/all.json', 'GET', [], true);

        $badges = $result['Badges'];
        foreach ($badges as $badge) {
            if ($badge['HubSync'] === 1) {
                $set = arrayTranslate($badge, ['Slug', 'Name', 'Photo', 'Body', 'Points', 'Active', 'Visible', 'Class', 'Threshold', 'Level']);
                $this->BadgeModel->save($set);
                $this->BadgeModel->Validation->results(true);
            }
        }
    }

    /**
     *
     */
    public function user() {
        $this->permission('Reputation.Badges.View');

        $userID = Gdn::request()->getValue('UserID', false);
        $this->setData('Badges', $this->BadgeModel->getFilteredList($userID, true));

        $module = new LeaderBoardModule();
        $module->SlotType = 'a';
        $module->getData(c('Reputation.Badges.LeaderboardLimit', 25));
        $this->addModule($module);

        $this->MasterView = 'default';
        $this->render('index');
    }
}
