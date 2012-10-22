<?php

class CompetitionModule extends Gdn_Module {
//   public function  __construct($Sender = '') {
//      $this->Path(__FILE__);
//      parent::__construct($Sender, FALSE);
//   }

   public function ToString() {
      $Discussion = $this->_Sender->Data('Discussion');
      if (!$Discussion)
         return;
      $this->Discussion = $Discussion;
      $this->CountDesigners = PrizeDrivenPlugin::DesignerCount(GetValue('DiscussionID', $Discussion));

      $this->InsertUser = Gdn::SQL()->GetWhere('User', array('UserID' => GetValue('InsertUserID', $Discussion)))->FirstRow(DATASET_TYPE_ARRAY);

      include dirname(__FILE__).'/views/modules/competition.php';
   }
}
