<?php

class GroupsHooks extends Gdn_Plugin {
      /**
    * Run structure & default badges.
    */
   public function Setup() {
      include(dirname(__FILE__).'/structure.php');
   }
   
   /** 
    * Add the "Groups" link to the main menu.
    */
   public function Base_Render_Before($Sender) {
      if (is_object($Menu = GetValue('Menu', $Sender))) {
         $Menu->AddLink('Groups', T('Groups'), '/groups/', FALSE, array('class' => 'Groups'));
      }
   }
   
   /**
    * Configure Groups/Events notification preferences
    * 
    * @param type $Sender
    */
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      $Sender->Preferences['Notifications']['Email.Groups'] = T('PreferenceGroupsEmail', 'Notify me when there is Group activity.');
      $Sender->Preferences['Notifications']['Popup.Groups'] = T('PreferenceGroupsPopup', 'Notify me when there is Group activity.');
      
      $Sender->Preferences['Notifications']['Email.Events'] = T('PreferenceEventsEmail', 'Notify me when there is Event activity.');
      $Sender->Preferences['Notifications']['Popup.Events'] = T('PreferenceEventsPopup', 'Notify me when there is Event activity.');
   }
}