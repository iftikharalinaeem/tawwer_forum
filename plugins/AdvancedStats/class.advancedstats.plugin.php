<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AdvancedStats'] = array(
   'Name' => 'Advanced Stats',
   'Description' => "Track and access advanced statistics to better monitor the health of your site.",
   'Version' => '1.0.1',
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class AdvancedStatsPlugin extends Gdn_Plugin {
   /// Methods ///
   
   /**
    * The first date that stats can be considered.
    * @return datetime
    */
   public static function FirstDate() {
      $MinDate = '2000-01-01';
      $MinTimestamp = strtotime($MinDate);
      
      $FirstUserDate = Gdn::SQL()
         ->Select('DateInserted', 'min')
         ->From('User')
         ->Where('DateInserted >', '1976-01-01')
         ->Get()->Value('DateInserted');
      
      if (Gdn_Format::ToTimestamp($FirstUserDate) <= $MinTimestamp)
         return $MinDate;
      
      $FirstDiscussionDate = Gdn::SQL()
         ->Select('DateInserted', 'min')
         ->From('Discussion')
         ->Where('DateInserted >', '1976-01-01')
         ->Get()->Value('DateInserted');
      
      if (Gdn_Format::ToTimestamp($FirstDiscussionDate) <= $MinTimestamp)
         return $MinDate;
      
      $FirstDate = Gdn_Format::ToDateTime(min(Gdn_Format::ToTimestamp($FirstUserDate), Gdn_Format::ToTimestamp($FirstDiscussionDate)));
      
      return $FirstDate;
   }
      
   /**
    * Gets a url suitable to ping the statistics server.
    * @param type $Path
    * @param type $Params
    * @return string 
    */
   public static function StatsUrl($Path, $Params = array()) {
      $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
      
      $Path = '/'.trim($Path, '/');
      
      $Timestamp = time();
      $DefaultParams = array(
          'vid' => Gdn::InstallationID(),
          't' => $Timestamp,
          's' => md5($Timestamp.Gdn::InstallationSecret()));
      
      $Params = array_merge($DefaultParams, $Params);
      
      $Result = $AnalyticsServer.$Path.'?'.http_build_query($Params);
      return $Result;
   }
   
   /// Event Handlers ///
   
   
   /**
    *
    * @param ActivityController $Sender
    * @param array $Args 
    */
   public function ActivityController_Buzz_Create($Sender, $Date = FALSE, $Slot = 'w') {
      $BuzzModel = new BuzzModel();
      $Get = array_change_key_case($Sender->Request->Get());
      
      $Sender->AddCssFile('buzz.css', 'plugins/vfcom');
      $Sender->Data = $BuzzModel->Get($Slot, $Date);
      
      $Sender->SetData('Title', T("What's the Buzz?"));
      $Sender->Render('Buzz', 'Activity', 'plugins/AdvancedStats');
   }
   
   public function UtilityController_Buzz_Create($Sender, $Date = FALSE, $Slot = 'w') {
      $this->ActivityController_Buzz_Create($Sender, $Date, $Slot);
   }
   
   
   /**
    * Adds & removes dashboard menu options.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      if (C('Garden.Analytics.Advanced')) {
         // Add stats menu option.
         $Menu->AddLink('Dashboard', 'Statistics', '/dashboard/settings/statistics', 'Garden.Settings.Manage');
      }
   }
   
   /**
    * @param Gdn_Controller $Sender
    * @param type $Args 
    */
   public function Base_Render_Before($Sender, $Args) {
//      if ($Sender->MasterView != 'admin') {
         $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
         $Version = GetValue('Version', Gdn::PluginManager()->GetPluginInfo('AdvancedStats'));
         
         
         
         if ($AnalyticsServer == 'http://analytics.vanillaforums.com') {            
            $Url = "http://autostatic-cl1.vanilladev.com/analytics.vanillaforums.com/applications/vanillastats/js/track.min.js?v=$Version";
         } else
            $Url = $AnalyticsServer.'/applications/vanillastats/js/track'.(Debug() ? '' : '.min').'.js?v='.$Version;
         
         $Sender->AddJsFile($Url, '', array('defer' => 'defer'));
         $Sender->AddDefinition('StatsUrl', self::StatsUrl('{p}'));
//      }
   }
   
   /**
    * Creates an analytics page to load remote analytics data.
    */
   public function SettingsController_Statistics_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Site Statistics');
      $Sender->AddSideMenu('dashboard/settings/statistics');
		$Sender->Render('stats', '', 'plugins/AdvancedStats');
   }
   
   /**
    * @param UserModel $Sender
    * @param array $Args 
    */
   public function UserModel_BeforeInsertUser_Handler($Sender, $Args) {
      // Check for the tracker cookie and save that with the user.
      $TrackerCookie = GetValue('__vna', $_COOKIE);
      if ($TrackerCookie) {
         $Parts = explode('.', $TrackerCookie);
         $DateFirstVisit = Gdn_Format::ToDateTime($Parts[0]);
         $SignedIn = GetValue(2, $Parts);
         if (!$SignedIn)
            $Args['InsertFields']['DateFirstVisit'] = $DateFirstVisit;
      }
   }
   
   public function UtilityController_Ping_Create($Sender) {
      $Sender->SetData('VanillaID', Gdn::InstallationID());
      $Sender->SetData('DateFirstStats', self::FirstDate());
      $Sender->Render();
   }
}