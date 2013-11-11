<?php

class NewAnnouncementModule extends Gdn_Module {
   /// Properties ///
   public $GroupID;
   
   
   /// Methods ///
   
   public function ToString() {
      if (!$this->GroupID) {
         $GroupID = Gdn::Controller()->Data('Group.GroupID');
      }
      
      if (GroupPermission('Moderate', $GroupID)) {
         return ' '.Anchor(T('New Announcement'), GroupUrl($this->Data('Group'), 'announcement'), 'Button').' ';
      }
      return '';
   }
}

