<?php if (!defined('APPLICATION')) exit();

$PluginInfo['CustomHead'] = array(
   'Name' => 'Custom Head',
   'Description' => 'Adds custom elements to the head through configuration.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class CustomHeadPlugin implements Gdn_IPlugin {

   public function Base_Render_Before(&$Sender) {
      $Javascripts = Gdn::Config('Plugins.CustomHead.Javascript');
      if (is_array($Javascripts)) {
         foreach ($Javascripts as $Javascript) {
            $Sender->Head->AddScript($Javascript);
         }
      }
   }
   
   public function Setup() {
      // No setup required.
   }
}