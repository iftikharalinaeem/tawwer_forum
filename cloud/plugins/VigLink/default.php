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
   public function base_afterBody_handler($sender) {
      echo $this->generateVigLinkCode( c('Plugins.VigLink.ApiKey', '') );
   }

   /**
    * Settings page.
    */
   public function settingsController_vigLink_create($sender, $args) {
      $apiKey = c('Plugins.VigLink.ApiKey', '');

      if ($sender->Form->authenticatedPostBack()) {
         $newKey = trim($sender->Form->getFormValue('ApiKey'));

         if ($this->validateApiKey($newKey)) {
            saveToConfig('Plugins.VigLink.ApiKey', $newKey);
            $sender->StatusMessage = t("VigLink.Saved");
         }
         else {
            $sender->Form->addError( t("VigLink.ErrorInvalid"), 'ApiKey' );
            $sender->Form->setFormValue('ApiKey', $apiKey);
         }
      }
      else {
         $sender->Form->setValue('ApiKey', $apiKey);
      }

      $sender->addSideMenu();
      $sender->setData('Title', t('VigLink.VigLinkSettings'));
      $sender->render('Settings', '', 'plugins/VigLink');
   }

   /**
    * Checks for a valid API Key format
    *
    * @param   array   APY Key
    * @return  boolean TRUE if the API key follows the pattern or is blank.
    */
   protected function validateApiKey( $apiKey ) {
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
   protected function generateVigLinkCode( $vigLinkKey ) {
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

   public function setup() { }

   /**
    * Plugin cleanup.
    */
   public function onDisable() {
      removeFromConfig('Plugins.VigLink.ApiKey');
   }
}
