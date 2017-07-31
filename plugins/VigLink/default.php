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

// v 1.1 - Lincoln cleaned up coding conventions and refactored the Settings page. 2012-06-29

class VigLinkPlugin implements Gdn_IPlugin {
   /**
    * Insert code.
    */
   public function Base_AfterBody_Handler($sender) {
      echo $this->GenerateVigLinkCode( C('Plugins.VigLink.ApiKey', '') );
   }

   /**
    * Settings page.
    */
   public function SettingsController_VigLink_Create($sender, $args) {
      $apiKey = C('Plugins.VigLink.ApiKey', '');

      if ($sender->Form->AuthenticatedPostBack()) {
         $newKey = trim($sender->Form->GetFormValue('ApiKey'));

         if ($this->ValidateApiKey($newKey)) {
            SaveToConfig('Plugins.VigLink.ApiKey', $newKey);
            $sender->StatusMessage = T("VigLink.Saved");
         }
         else {
            $sender->Form->AddError( T("VigLink.ErrorInvalid"), 'ApiKey' );
            $sender->Form->SetFormValue('ApiKey', $apiKey);
         }
      }
      else {
         $sender->Form->SetValue('ApiKey', $apiKey);
      }

      $sender->AddSideMenu();
      $sender->SetData('Title', T('VigLink.VigLinkSettings'));
      $sender->Render('Settings', '', 'plugins/VigLink');
   }

   /**
    * Checks for a valid API Key format
    *
    * @param   array   APY Key
    * @return  boolean TRUE if the API key follows the pattern or is blank.
    */
   protected function ValidateApiKey( $apiKey ) {
      if (preg_match('/^[0-9a-f]{32}$/', $apiKey) || $apiKey === '')
         return true;

      return false;
   }

   /**
    * Generates the VigLink code
    *
    * @param string $vigLinkKey
    * @return string   the VigLink code
    */
   protected function GenerateVigLinkCode( $vigLinkKey ) {
      if (empty($vigLinkKey))
         return '';

      return '
   <script type="text/javascript">
      //<![CDATA[
      var vglnk = { api_url: \'//api.VigLink.com/api\',
          key: \'' . $vigLinkKey . '\' };
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
