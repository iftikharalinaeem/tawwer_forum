<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GoogleGadgets'] = array(
   'Name' => 'Google Gadgets',
   'Description' => 'A helper plugin for Google Gadgets so we use separate views for various pages when requests come from gadgets.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GoogleGadgetsPlugin implements Gdn_IPlugin {
    
   public function Gdn_DiscussionsController_Render_Before(&$Sender) {
      if (GetIncomingValue('Gadget') !== FALSE)
         $Sender->View = PATH_PLUGINS . DS . 'GoogleGadgets' . DS . 'views' . DS . strtolower($Sender->RequestMethod) . '.php';
   }
   
   public function Setup() {
      // No setup required.
   }   
}

if (!function_exists('UserAnchor')) {
   function UserAnchor($User, $CssClass = '') {
      if ($CssClass != '')
         $CssClass = ' class="'.$CssClass.'"';

      return '<a href="'.Url('/profile/'.$User->UserID.'/'.urlencode($User->Name), TRUE).'"'.$CssClass.'>'.$User->Name.'</a>';
   }
}