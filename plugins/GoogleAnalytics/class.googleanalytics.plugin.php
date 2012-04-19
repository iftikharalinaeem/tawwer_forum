<?php if (!defined('APPLICATION')) exit();

$PluginInfo['GoogleAnalytics'] = array(
   'Name' => 'Google Analytics',
   'Description' => 'Adds google analytics tracking script to the forum.',
   'Version' => '2.0',
   'SettingsUrl' => 'dashboard/settings/ga',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GoogleAnalyticsPlugin implements Gdn_IPlugin {

   /**
    * Includes Google Analytics on all pages if the conf file contains
    * Plugins.GoogleAnalytics.TrackerCode and
    * Plugins.GoogleAnalytics.TrackerDomain.
    */
   public function Base_Render_Before(&$Sender) {
      $Blacklist = C('Plugins.GoogleAnalytics.ControllerBlacklist', FALSE);
      if (!$Blacklist && strtolower($Sender->MasterView) == 'admin') {
         return;
      }
      if (is_array($Blacklist) && InArrayI($Sender->ControllerName, $Blacklist))
         return;
      
      $TrackerDomain = C('Plugins.GoogleAnalytics.TrackerDomain');
      $TrackerCode = C('Plugins.GoogleAnalytics.TrackerCode'); // Old way
      $TrackerAccount = C('Plugins.GoogleAnalytics.Account'); // New way
      if (!$TrackerAccount)
         $TrackerAccount = $TrackerCode;
      
      if ($TrackerAccount != '' && !is_array($TrackerAccount))
         $TrackerAccount = array($TrackerAccount);
      if (!is_array($TrackerDomain))
         $TrackerDomain = array($TrackerDomain);
      
      // If an account was specified, build the tracking command to send
      if (is_array($TrackerAccount) && $Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         // Pushing multiple GA commands like this is documented here: 
         // https://developers.google.com/analytics/devguides/collection/gajs/#MultipleCommands
         $Alpha = explode(',', ',b.,c.,d.,e.,f.,g.,h.,i.,j.,k.,l.,m.,n.,o.,p.');
         $Script = '<script type="text/javascript">';
         $Script .= "\nvar _gaq = _gaq || [];\n";
         $Script .= "_gaq.push(\n";
         $AlphaIndex = 0;
         foreach ($TrackerAccount as $Index => $Account) {
            if ($Account == '')
               continue;
            $Prefix = $Alpha[$AlphaIndex];
            $AlphaIndex++;
            $Domain = GetValue($Index, $TrackerDomain);
            if ($Index > 0)
               $Script .= ',';
            
            $Script .= "['{$Prefix}_setAccount', '{$Account}']\n";
            if ($Domain)
               $Script .= ",['{$Prefix}_setDomainName', '{$Domain}']\n";

            if ($Sender->Data('AnalyticsFunnelPage', FALSE)) {
               $FunnelPageName = $Sender->Data('AnalyticsFunnelPage');
               $Script .= ",['{$Prefix}_trackPageview','{$FunnelPageName}']\n";
            } else {
               $Script .= ",['{$Prefix}_trackPageview']\n";
            }
         }
         $Script .= ");
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>";
         if ($AlphaIndex > 0)
            $Sender->AddAsset('Foot', $Script);
      }
   }
   
   /* Make a dashboard page to manage Google Analytics tracking codes */
   public function SettingsController_GA_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Google Analytics Settings');
      $Sender->AddSideMenu();
      $Sender->Form = new Gdn_Form();
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('Plugins.GoogleAnalytics.TrackerDomain', 'Plugins.GoogleAnalytics.Account'));
      
      $Sender->Form->SetModel($ConfigurationModel);
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Format settings as strings
         $Domain = GetValue('Plugins.GoogleAnalytics.TrackerDomain', $ConfigurationModel->Data);
         if (is_array($Domain))
            $Domain = implode("\n", $Domain);
         $ConfigurationModel->Data['Plugins.GoogleAnalytics.TrackerDomain'] = $Domain;

         $Account = GetValue('Plugins.GoogleAnalytics.Account', $ConfigurationModel->Data);
         if (is_array($Account))
            $Account = implode("\n", $Account);

         // Check for the old way of storing this info...
         if ($Account == '')
            $Account = C('Plugins.GoogleAnalytics.TrackerCode', '');
         
         $ConfigurationModel->Data['Plugins.GoogleAnalytics.Account'] = $Account;

         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         // Format the strings as arrays based on newlines & spaces
         $Account = $Sender->Form->GetValue('Plugins.GoogleAnalytics.Account');
         $Account = explode(' ', str_replace("\n", ' ', $Account));
         $Account = array_unique(array_map('trim', $Account));
         $Sender->Form->SetFormValue('Plugins.GoogleAnalytics.Account', $Account);
         
         $Domain = $Sender->Form->GetValue('Plugins.GoogleAnalytics.TrackerDomain');
         $Domain = explode(' ', str_replace("\n", ' ', $Domain));
         $Domain = array_unique(array_map('trim', $Domain));
         $Sender->Form->SetFormValue('Plugins.GoogleAnalytics.TrackerDomain', $Domain);

         if ($Sender->Form->Save() !== FALSE)
            $Sender->InformMessage(T("Your settings have been saved."));
         
         // Reformat arrays as string so they display properly in the form
         $Sender->Form->SetFormValue('Plugins.GoogleAnalytics.Account', implode("\n", $Account));
         $Sender->Form->SetFormValue('Plugins.GoogleAnalytics.TrackerDomain', implode("\n", $Domain));
      }
      
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Render('settings', '', 'plugins/GoogleAnalytics');
   }
   
   public function Setup() {
      // No setup required.
   }
}