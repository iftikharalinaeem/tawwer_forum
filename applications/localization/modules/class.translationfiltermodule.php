<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2012 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Renders the "Clear Conversation History" button.
 */
class TranslationFilterModule extends Gdn_Module {

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function GetData() {
      $Filter = Gdn::Session()->GetPreference('Localization.Filter');
      if (!is_array($Filter))
         $Filter = array();
      
      TouchValue('Core', $Filter, TRUE);
      TouchValue('Admin', $Filter, TRUE);
      TouchValue('Addon', $Filter, TRUE);
      
      $this->Form = new Gdn_Form();
      $this->Form->SetData($Filter);
   }
   
   public function ToString() {
      if (!Gdn::Session()->User)
         return '';
      
      $this->GetData();
      return parent::ToString();
   }
}