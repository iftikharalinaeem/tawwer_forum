<?php
/**
 * Badges Controller.
 *
 * @package Reputation
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
   public function Index() {
      $this->Permission('Reputation.Badges.View');
      $this->SetData('Badges', $this->BadgeModel->GetFilteredList(Gdn::Session()->UserID));

      $Module = new LeaderBoardModule();
      $Module->SlotType = 'a';
      $Module->GetData(C('Reputation.Badges.LeaderboardLimit', 25));
      $this->AddModule($Module);

      $this->MasterView = 'default';
      $this->Render();
   }

   public function All() {
      $this->Permission('Reputation.Badges.View');
      $Badges = $this->BadgeModel->GetList()->ResultArray();

      if ($Badges) {
         $this->BadgeModel->Calculate($Badges);
      }
      $this->SetData('Badges', $Badges);
      unset($Badges);

      $Module = new LeaderBoardModule();
      $Module->SlotType = 'a';
      $Module->GetData(C('Reputation.Badges.LeaderboardLimit', 25));
      $this->AddModule($Module);

      $this->MasterView = 'default';
      $this->Render('index');
   }

   public function SyncNode() {
      if (!class_exists('SiteNodePlugin')) {
         return;
      }

      /* @var SiteNodePlugin $nodePlugin */
      $nodePlugin = SiteNodePlugin::Instance();

      $result = $nodePlugin->hubApi('/badges/all.json', 'GET', array(), true);

      $badges = $result['Badges'];
      foreach ($badges as $badge) {
         $set = ArrayTranslate($badge, array('Slug', 'Name', 'Photo', 'Body', 'Points', 'Active', 'Visible', 'Class', 'Threshold', 'Level'));
         $this->BadgeModel->Save($set);
         $this->BadgeModel->Validation->Results(true);
      }
   }

   public function User() {
      $this->Permission('Reputation.Badges.View');

      $UserID = Gdn::Request()->GetValue('UserID', FALSE);
      $this->SetData('Badges', $this->BadgeModel->GetFilteredList($UserID, TRUE));

      $Module = new LeaderBoardModule();
      $Module->SlotType = 'a';
      $Module->GetData(C('Reputation.Badges.LeaderboardLimit', 25));
      $this->AddModule($Module);

      $this->MasterView = 'default';
      $this->Render('index');
   }

}
