<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class AdvancedSearchModule extends Gdn_Module {
   /**
    *
    * @var Gdn_Form 
    */
   public $Form;
   
   public $DateWithinOptions;
   
   public $IncludeTags = NULL;
   
   public $Results = FALSE;
   
   public function __construct($Sender = '', $ApplicationFolder = FALSE) {
      $this->_ApplicationFolder = 'plugins/AdvancedSearch';
      
      $this->DateWithinOptions = array(
         '1 day' => Plural(1, '%s day', '%s days'),
         '3 days' => Plural(3, '%s day', '%s days'),
         '1 week' => Plural(1, '%s week', '%s weeks'),
         '2 weeks' => Plural(2, '%s week', '%s weeks'),
         '1 month' => Plural(1, '%s month', '%s months'),
         '2 months' => Plural(2, '%s month', '%s months'),
         '6 months' => Plural(6, '%s month', '%s months'),
         '1 year' => Plural(1, '%s year', '%s years')
      );
   }
   
   public static function AddAssets() {
      Gdn::Controller()->AddJsFile('jquery.tokeninput.js');
      Gdn::Controller()->AddJsFile('jquery-ui.js');
      Gdn::Controller()->AddJsFile('advanced-search.js', 'plugins/AdvancedSearch');
      Gdn::Controller()->AddDefinition('TagHint', "Start to type...");
      Gdn::Controller()->AddDefinition('TagSearching', "Searching...");
   }
   
   public function ToString() {
      if ($this->IncludeTags === NULL) {
         $this->IncludeTags = Gdn::PluginManager()->IsEnabled('Tagging');
      }
      
      // We want the advanced search form to populate from the get and have lowercase fields.
      $Form = $this->Form = new Gdn_Form();
      $Form->Method = 'get';
      if ($this->Results) {
         $Get = array_change_key_case(Gdn::Request()->Get());
         $Form->FormValues($Get);
      }
      
      return parent::ToString();
   }
}