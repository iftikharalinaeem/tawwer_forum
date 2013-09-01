<?php if (!defined('APPLICATION')) exit();

class UserWarningModule extends Gdn_Module {
   
   public $UserID;
   
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      $this->_ApplicationFolder = 'plugins/Warnings';
   }
   
   public function ToString() {
      if (!$this->UserID) {
         $this->UserID = Gdn::Controller()->Data('Profile.UserID');
      }
      
      if ($this->UserID != Gdn::Session()->UserID && !Gdn::Session()->CheckPermission(array('Garden.PersonalInfo.View', 'Moderation.Warnings.View'), FALSE)) {
         return '';
      }
      
      // Grab the data.
      $UserAlertModel = new UserAlertModel();
      $Alert = $UserAlertModel->GetID($this->UserID, DATASET_TYPE_ARRAY);
      $this->Data = $Alert;
      if (!$this->Data('WarningLevel'))
         return '';
      
      $User = Gdn::UserModel()->GetID($this->UserID);
      $this->SetData('Punished', GetValue('Punished', $User));
      $this->SetData('Banned', GetValue('Banned', $User));
      $this->SetData('Name', GetValue('Name', $User));
      
      return parent::ToString();
   }
}