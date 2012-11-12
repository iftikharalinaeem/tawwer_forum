<?php if (!defined('APPLICATION')) exit;

class ModListModule extends Gdn_Module {
   /// Methods ///
   
   public function GetData() {
      $Category = Gdn::Controller()->Data('Category');
      if (!$Category) {
         $Category = Gdn::Controller()->Data('CategoryID');
         if ($Category)
            $Category = CategoryModel::Categories($Category);
      }
      if (!$Category)
         return;
      
      // Grab the moderator list.
      $Moderators = Gdn::SQL()->GetWhere('CategoryModerator', array('CategoryID' => GetValue('PermissionCategoryID', $Category)))->ResultArray();
      
      Gdn::UserModel()->JoinUsers($Moderators, array('UserID'));
      $this->SetData('Moderators', $Moderators);
      
   }
   
   public function FetchViewLocation($View = '', $ApplicationFolder = '') {
      return dirname(__FILE__).'/views/modlist.php';
   }
   
   public function ToString() {
      $this->GetData();
      
      if ($this->Data('Moderators', NULL) === NULL)
         return '';
      
      return parent::ToString();
   }
}