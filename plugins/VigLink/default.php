<?php if (!defined('APPLICATION')) exit();
/**
 * Copyright 2011 VigLink - http://www.VigLink.com
 *
 * VigLink is the easiest way to monetize your links.
 * VigLink automatically affiliates all of your outbound links so you get paid when visitors click through and buy something.
 * VigLink's analytics dashboard tells you which outbound links are clicked most, which are making you the most money, and more.
 * This component will install the VigLink javascript on your site, without the need to edit templates.
 *
 * Have questions or comments about VigLink or the plugin? Suggestions for something you'd like us to add? Please let us know!
 * http://www.VigLink.com/support
 */

// Define the plugin:
$PluginInfo['VigLink'] = array(
   'Description' => 'VigLink is the easiest way to monetize your links. <b>You must go to Settings and enter your API Key for this plugin to work.</b>',
   'Version' => '1.1',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'MobileFriendly' => TRUE,
   'SettingsUrl' => '/dashboard/settings/VigLink',
   'Author' => "VigLink",
   'AuthorEmail' => 'support@VigLink.com',
   'AuthorUrl' => 'http://www.VigLink.com'
);

// v 1.1 - Lincoln cleaned up coding conventions and refactored the Settings page. 2012-06-29


class VigLinkPlugin implements Gdn_IPlugin {
   /**
    * Insert code.
    */
   public function Base_AfterBody_Handler($Sender) {
      echo $this->GenerateVigLinkCode( C('Plugins.VigLink.ApiKey', '') );
   }
   
   /**
    * Settings page.
    */
   public function SettingsController_VigLink_Create($Sender, $Args) {
      $ApiKey = C('Plugins.VigLink.ApiKey', '');
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $NewKey = trim($Sender->Form->GetFormValue('ApiKey'));
         
         if ($this->ValidateApiKey($NewKey)) {
            SaveToConfig('Plugins.VigLink.ApiKey', $NewKey);
            $Sender->StatusMessage = T("VigLink.Saved");
         } 
         else {
            $Sender->Form->AddError( T("VigLink.ErrorInvalid"), 'ApiKey' );
            $Sender->Form->SetFormValue('ApiKey', $ApiKey);
         }
      } 
      else {
         $Sender->Form->SetFormValue('ApiKey', $ApiKey);
      }

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('VigLink.VigLinkSettings'));
      $Sender->Render('Settings', '', 'plugins/VigLink');
   }

   /**
    * Checks for a valid API Key format
    *
    * @param   array   APY Key
    * @return  boolean TRUE if the API key follows the pattern or is blank.
    */
   protected function ValidateApiKey( $ApiKey ) {
      if (preg_match('/^[0-9a-f]{32}$/', $ApiKey) || $ApiKey === '')
         return true;
      
      return false;
   }

   /**
    * Generates the VigLink code
    *
    * @param string $VigLinkKey
    * @return string   the VigLink code
    */
   protected function GenerateVigLinkCode( $VigLinkKey ) {
      if (empty($VigLinkKey))
         return '';
         
      return '
   <script type="text/javascript">
      //<![CDATA[
      var vglnk = { api_url: \'//api.VigLink.com/api\',
          key: \'' . $VigLinkKey . '\' };
      (function(d, t) {
      var s = d.createElement(t); s.type = \'text/javascript\'; s.async = true;
      s.src = (\'https:\' == document.location.protocol ? vglnk.api_url :
          \'//cdn.VigLink.com/api\') + \'/vglnk.js\';
      var r = d.getElementsByTagName(t)[0]; r.parentNode.insertBefore(s, r);
      }(document, \'script\'));
      //]]>
   </script>';
   }

   public function Setup() { }

   /**
    * Plugin cleanup.
    */
   public function OnDisable() {
      RemoveFromConfig('Plugins.VigLink.ApiKey');
   }
}