<?php if (!defined('APPLICATION')) exit();

class GoogleAnalyticsPlugin implements Gdn_IPlugin {

   /**
    * Includes Google Analytics on all pages if the conf file contains
    * Plugins.GoogleAnalytics.TrackerCode and
    * Plugins.GoogleAnalytics.TrackerDomain.
    * 
    * @param Gdn_Controller $sender
    */
   public function base_afterRenderAsset_handler($sender, $args) {
      if ($args['AssetName'] != 'Head')
         return;
      
      $blacklist = c('Plugins.GoogleAnalytics.ControllerBlacklist', FALSE);
      if (!$blacklist && strtolower($sender->MasterView) == 'admin') {
         return;
      }
      if (is_array($blacklist) && inArrayI($sender->ControllerName, $blacklist))
         return;
      
      $trackerDomain = c('Plugins.GoogleAnalytics.TrackerDomain');
      $trackerCode = c('Plugins.GoogleAnalytics.TrackerCode'); // Old way
      $trackerAccount = c('Plugins.GoogleAnalytics.Account'); // New way
      if (!$trackerAccount)
         $trackerAccount = $trackerCode;
      
      if ($trackerAccount != '' && !is_array($trackerAccount))
         $trackerAccount = [$trackerAccount];
      if (!is_array($trackerDomain))
         $trackerDomain = [$trackerDomain];
      
      // If an account was specified, build the tracking command to send
      if (is_array($trackerAccount) && $sender->deliveryType() == DELIVERY_TYPE_ALL) {
         // Pushing multiple GA commands like this is documented here: 
         // https://developers.google.com/analytics/devguides/collection/gajs/#MultipleCommands
         $alpha = explode(',', ',b.,c.,d.,e.,f.,g.,h.,i.,j.,k.,l.,m.,n.,o.,p.');
         $script = '<script type="text/javascript">';
         $script .= "\nvar _gaq = _gaq || [];\n";
         $script .= "_gaq.push(\n";
         $alphaIndex = 0;
         foreach ($trackerAccount as $index => $account) {
            if ($account == '')
               continue;
            $prefix = $alpha[$alphaIndex];
            $alphaIndex++;
            $domain = getValue($index, $trackerDomain);
            if ($index > 0)
               $script .= ',';
            
            $script .= "['{$prefix}_setAccount', '{$account}']\n";
            if ($domain)
               $script .= ",['{$prefix}_setDomainName', '{$domain}']\n";

            if ($sender->data('AnalyticsFunnelPage', FALSE)) {
               $funnelPageName = $sender->data('AnalyticsFunnelPage');
               $script .= ",['{$prefix}_trackPageview','{$funnelPageName}']\n";
            } else {
               $script .= ",['{$prefix}_trackPageview']\n";
            }
         }
         $script .= ");
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>";
         if ($alphaIndex > 0) {
            echo $script;
         }
      }
   }
   
   /* Make a dashboard page to manage Google Analytics tracking codes */
   public function settingsController_gA_create($sender) {
      $sender->permission('Garden.Settings.Manage');
      $sender->title('Google Analytics Settings');
      $sender->addSideMenu();
      $sender->Form = new Gdn_Form();
      $validation = new Gdn_Validation();
      $configurationModel = new Gdn_ConfigurationModel($validation);
      $configurationModel->setField(['Plugins.GoogleAnalytics.TrackerDomain', 'Plugins.GoogleAnalytics.Account']);
      
      $sender->Form->setModel($configurationModel);
      if ($sender->Form->authenticatedPostBack() === FALSE) {
         // Format settings as strings
         $domain = getValue('Plugins.GoogleAnalytics.TrackerDomain', $configurationModel->Data);
         if (is_array($domain))
            $domain = implode("\n", $domain);
         $configurationModel->Data['Plugins.GoogleAnalytics.TrackerDomain'] = $domain;

         $account = getValue('Plugins.GoogleAnalytics.Account', $configurationModel->Data);
         if (is_array($account))
            $account = implode("\n", $account);

         // Check for the old way of storing this info...
         if ($account == '')
            $account = c('Plugins.GoogleAnalytics.TrackerCode', '');
         
         $configurationModel->Data['Plugins.GoogleAnalytics.Account'] = $account;

         // Apply the config settings to the form.
         $sender->Form->setData($configurationModel->Data);
      } else {
         // Format the strings as arrays based on newlines & spaces
         $account = $sender->Form->getValue('Plugins.GoogleAnalytics.Account');
         $account = explode(' ', str_replace("\n", ' ', $account));
         $account = array_unique(array_map('trim', $account));
         $sender->Form->setFormValue('Plugins.GoogleAnalytics.Account', $account);
         
         $domain = $sender->Form->getValue('Plugins.GoogleAnalytics.TrackerDomain');
         $domain = explode(' ', str_replace("\n", ' ', $domain));
         $domain = array_unique(array_map('trim', $domain));
         $sender->Form->setFormValue('Plugins.GoogleAnalytics.TrackerDomain', $domain);

         if ($sender->Form->save() !== FALSE)
            $sender->informMessage(t("Your settings have been saved."));
         
         // Reformat arrays as string so they display properly in the form
         $sender->Form->setFormValue('Plugins.GoogleAnalytics.Account', implode("\n", $account));
         $sender->Form->setFormValue('Plugins.GoogleAnalytics.TrackerDomain', implode("\n", $domain));
      }
      
      $sender->permission('Garden.Settings.Manage');
      $sender->render('settings', '', 'plugins/GoogleAnalytics');
   }
   
   public function setup() {
      // No setup required.
   }
}
