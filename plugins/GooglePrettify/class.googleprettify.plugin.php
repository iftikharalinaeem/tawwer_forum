<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['GooglePrettify'] = array(
   'Name' => 'Syntax Prettifier',
   'Description' => 'Adds pretty syntax highlighting to source code posted in your forum. This is a great addon for communities that support programmers and designers.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/dashboard/settings/googleprettify',
   'SettingsPermission' => 'Garden.Settings.Manage',
);

class GooglePrettifyPlugin extends Gdn_Plugin {
   /// PROPERTIES ///
   
   /// METHODS ///
   
   public function GetJs() {
      $LineNums = '';
      if (C('Plugins.GooglePrettify.LineNumbers'))
         $LineNums = 'linenums';
      
      $Result = "jQuery(document).ready(function($) {
   $('.Message pre').addClass('prettyprint $LineNums');
   prettyPrint();
});";
      return $Result;
   }
   
   /// EVENT HANDLERS ///
   
   public function AssetModel_StyleCss_Handler($Sender) {
      if (!C('Plugins.GooglePrettify.NoCssFile'))
         $Sender->AddCssFile('prettify.css', 'plugins/GooglePrettify');
   }
   
   public function AssetModel_GenerateETag_Handler($Sender, $Args) {
      if (!C('Plugins.GooglePrettify.NoCssFile'))
         $Args['ETagData']['Plugins.GooglePrettify.NoCssFile'] = TRUE;
   }
   
   /**
    *
    * @param DiscussionController $Sender 
    */
   public function DiscussionController_Render_Before($Sender) {
//      $Sender->AddJsFile('prettify.plugin.js', 'plugins/GooglePrettify');
      $Sender->Head->AddTag('script', array('type' => 'text/javascript', '_sort' => 100), $this->GetJs());
      $Sender->AddJsFile('prettify.js', 'plugins/GooglePrettify', array('_sort' => 101));
   }
   
   public function SettingsController_GooglePrettify_Create($Sender, $Args) {
      $Cf = new ConfigurationModule($Sender);
      $CssUrl = Asset('/plugins/GooglePrettify/design/prettify.css', TRUE);
      
      $Cf->Initialize(array(
          'Plugins.GooglePrettify.LineNumbers' => array('Control' => 'CheckBox', 'Description' => 'Add line numbers to source code.', 'Default' => FALSE),
          'Plugins.GooglePrettify.NoCssFile' => array('Control' => 'CheckBox', 'LabelCode' => 'Exclude Default CSS File', 'Description' => "If you want to define syntax highlighting in your custom theme you can disable the <a href='$CssUrl'>default css</a> with this setting.", 'Default' => FALSE)
      ));

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Syntax Prettifier Settings'));
      $Cf->RenderAll();
   }
}