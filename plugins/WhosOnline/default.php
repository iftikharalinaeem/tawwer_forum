<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['WhosOnline'] = array(
   'Name' => 'Who&rsquo;s Online',
   'Description' => "Adds a list of users currently browsing your site to the sidebar.",
   'Version' => '1.5.1',
   'Author' => "Gary Mardell",
   'AuthorEmail' => 'gary@vanillaplugins.com',
   'AuthorUrl' => 'http://vanillaplugins.com',
   'RegisterPermissions' => array('Plugins.WhosOnline.ViewHidden'),
   'SettingsUrl' => '/plugin/whosonline',
   'SettingsPermission' => array('Garden.Settings.Manage')
);

/**
 * TODO:
 * Admin option to allow users it hide the module
 * User Meta table to store if they are hidden or not
 */
 
// Changelog
// 1.3.1 ??
// 1.3.2 ??
// 1.3.3 ??
// 1.3.4 ??
// 1.3.5 ??
// 1.4   Added ability to target only lists, made pinger work on all pages, replace dash menu item w/settings button, adds docs -Lincoln
// 1.5   Remove users from the list when they log out explicitly
// 1.5.1 Add 'Invisible' class to invisibles that are shown for admins

class WhosOnlinePlugin extends Gdn_Plugin {
   /**
    * Settings page.
    */
   public function PluginController_WhosOnline_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('plugin/whosonline');
      $Sender->SetData('Title', T("Who&rsquo;s Online Settings"));
      
      $Config = new ConfigurationModule($Sender);
      $Config->Initialize(array(
          'WhosOnline.Location.Show' => array('Control' => 'RadioList', 'Description' => "This setting determins where the list of online users is displayed.", 'Items' => array('every' => 'Every page', 'discussion' => 'All discussion pages', 'discussionsonly' => 'Only discussions and categories list', 'custom' => 'Use your custom theme'), 'Default' => 'every'),
          'WhosOnline.Hide' => array('Control' => 'CheckBox', 'LabelCode' => "Hide the who's online module for guests."),
          'WhosOnline.DisplayStyle' => array('Control' => 'RadioList', 'Items' => array('list' => 'List', 'pictures' => 'Pictures'), 'Default' => 'list')
      ));
      
      $Config->RenderAll();
      
      
//      $Sender->Form = new Gdn_Form();
//      $Validation = new Gdn_Validation();
//      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
//      $ConfigurationModel->SetField(array('WhosOnline.Location.Show', 'WhosOnline.Frequency', 'WhosOnline.Hide'));
//      $Sender->Form->SetModel($ConfigurationModel);
//            
//      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {    
//         $Sender->Form->SetData($ConfigurationModel->Data);    
//      } else {
//         $Data = $Sender->Form->FormValues();
//         $ConfigurationModel->Validation->ApplyRule('WhosOnline.Frequency', array('Required', 'Integer'));
//         $ConfigurationModel->Validation->ApplyRule('WhosOnline.Location.Show', 'Required');
//         if ($Sender->Form->Save() !== FALSE)
//            $Sender->StatusMessage = T("Your settings have been saved.");
//      }
//      
//      // creates the page for the plugin options such as display options
//      $Sender->Render($this->GetView('whosonline.php'));
   }

   /**
    * Page for Javascript to ping to signal user is still online.
    */
   public function PluginController_ImOnline_Create($Sender) {
      $Session = Gdn::Session();
      $UserMetaData = $this->GetUserMeta($Session->UserID, '%'); 
      
      // render new block and replace whole thing opposed to just the data
      include_once(PATH_PLUGINS.DS.'WhosOnline'.DS.'class.whosonlinemodule.php');
      $WhosOnlineModule = new WhosOnlineModule($Sender);
      $WhosOnlineModule->GetData(ArrayValue('Plugin.WhosOnline.Invisible', $UserMetaData));
      echo $WhosOnlineModule->ToString();
   }
   
   /**
    * Add module to specified pages and include Javascript pinger.
    */
   public function Base_Render_Before($Sender) {
      $ConfigItem = C('WhosOnline.Location.Show', 'every');
      $Controller = $Sender->ControllerName;
      $Application = $Sender->ApplicationFolder;
      $Session = Gdn::Session();

		// Check if it's visible to users
		if (C('WhosOnline.Hide', TRUE) && !$Session->IsValid()) {
			return;
		}
		
		// Is this a page for including the module?
		$ShowOnController = array();		
		switch($ConfigItem) {
         case 'custom':
            return;
			case 'every':
				$ShowOnController = array(
					'discussioncontroller',
					'categoriescontroller',
					'discussionscontroller',
					'profilecontroller',
					'activitycontroller'
				);
				break;
			case 'discussionsonly':
				$ShowOnController = array(
					'discussionscontroller',
					'categoriescontroller'
				);	
				break;
			case 'discussion':
         default:
			   $ShowOnController = array(
					'discussioncontroller',
					'discussionscontroller',
					'categoriescontroller'
				);	
			   break;						
		}
		
		// Include the module
      if (InArrayI($Controller, $ShowOnController)) {
   	   $Sender->AddModule('WhosOnlineModule');
      }
      
      // Ping the server when still online
	   $Sender->AddJsFile('whosonline.js', 'plugins/WhosOnline');
      $Sender->AddCssFile('whosonline.css', 'plugins/WhosOnline');
	   $Frequency = C('WhosOnline.Frequency', 60);
	   if (!is_numeric($Frequency))
	      $Frequency = 60;
	   $Sender->AddDefinition('WhosOnlineFrequency', $Frequency);
      
   }
   
   public function EntryController_SignOut_Handler($Sender) {
      $User = $Sender->EventArguments['SignoutUser'];
      $UserID = GetValue('UserID', $User, FALSE);
      if ($UserID === FALSE) return;
      
      Gdn::SQL()->Delete('Whosonline', array(
         'UserID' => GetValue('UserID', $User)
      ));
   }
   
   /**
    * Add privacy settings to profile menu.
    */
   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $Session = Gdn::Session();
      $ViewingUserID = $Session->UserID;
      
      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', T('Privacy Settings'), '/profile/whosonline', FALSE, array('class' => 'Popup'));
      }
   }
   
   /**
    * Let users modify their privacy settings.
    */
   public function ProfileController_Whosonline_Create($Sender) {
      $Session = Gdn::Session();
      $UserID = $Session->IsValid() ? $Session->UserID : 0;
      $Sender->GetUserInfo();
      
      // Get the data
      $UserMetaData = $this->GetUserMeta($UserID, '%');
      $ConfigArray = array(
            'Plugin.WhosOnline.Invisible' => NULL
         );
      
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Convert to using arrays if more options are added.
         $ConfigArray = array_merge($ConfigArray, $UserMetaData);
         $Sender->Form->SetData($ConfigArray);
      }
      else {
         $Values = $Sender->Form->FormValues();
         $FrmValues = array_intersect_key($Values, $ConfigArray);
         
         foreach($FrmValues as $MetaKey => $MetaValue) {
            $this->SetUserMeta($UserID, $this->TrimMetaKey($MetaKey), $MetaValue); 
         }

         $Sender->StatusMessage = T("Your changes have been saved.");
      }

      $Sender->Render($this->GetView('settings.php'));
   }
   
   public function Gdn_Statistics_Tick_Handler($Sender, $Args) {
      if (!Gdn::Session()->IsValid())
         $this->IncrementGuest();
   }

   public function Setup() { 
      $this->Structure();
   }
   
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure->Table('Whosonline')
			->Column('UserID', 'int(11)', FALSE, 'primary')
       	->Column('Timestamp', 'datetime')
			->Column('Invisible', 'int(1)', 0)
         ->Set(FALSE, FALSE); 
   
   }
   
   public static function GuestCount() {
      if (!Gdn::Cache()->ActiveEnabled())
         return 0;
      
      try {
         $Names = array('__vnOz0', '__vnOz1');

         $Time = time();
         list($Expire0, $Expire1, $Active) = self::Expiries($Time);

         // Get bot keys from the cache.
         $Cache = Gdn::Cache()->Get($Names);

         $Debug = array(
             'Cache' => $Cache, 
             'Active' => $Active);
         Gdn::Controller()->SetData('GuestCountCache', $Debug);

         if (isset($Cache[$Names[$Active]]))
            return $Cache[$Names[$Active]];
         elseif (is_array($Cache) && count($Cache) > 0) {
            // Maybe the key expired, but the other key is still there.
            return array_pop($Cache);
         }
      } catch (Exception $Ex) {
         echo $Ex->getMessage();
      }
   }
   
   public static function Expiries($Time) {
      $Timespan = 600; // 10 mins.
      
      $Expiry0 = $Time - $Time % $Timespan + $Timespan;

      $Expiry1 = $Expiry0 - $Timespan / 2;
      if ($Expiry1 <= $Time)
         $Expiry1 = $Expiry0 + $Timespan / 2;

      $Active = $Expiry0 < $Expiry1 ? 0 : 1;

      return array($Expiry0, $Expiry1, $Active);
   }
   
   protected static function _IncrementCache($Name, $Expiry) {
      $Value = Gdn::Cache()->Increment($Name, 1, array(Gdn_Cache::FEATURE_EXPIRY => $Expiry));
      
      if (!$Value) {
         $Value = 1;
         $R = Gdn::Cache()->Store($Name, $Value, array(Gdn_Cache::FEATURE_EXPIRY => $Expiry));
      }
      
      return $Value;
   }
   
   public function IncrementGuest() {
      if (!Gdn::Cache()->ActiveEnabled())
         return FALSE;
      
      $Now = time();
      
      $TempName = C('Garden.Cookie.Name').'-Vv';
      $TempCookie = GetValue($TempName, $_COOKIE);
      if (!$TempCookie) {
         setcookie($TempName, $Now, $Now + 1200, C('Garden.Cookie.Path', '/'));
         return;
      }
      // We are going to be checking one of two cookies and flipping them once every 10 minutes.
      // When we read from one cookie
      $Name0 = '__vnOz0';
      $Name1 = '__vnOz1';
      
      list($Expire0, $Expire1) = self::Expiries($Now);
      
      if (!Gdn::Session()->IsValid()) {
         // Check to see if this guest has been counted.
         if (!isset($_COOKIE[$Name0]) && !isset($_COOKIE[$Name1])) {
            setcookie($Name0, $Now, $Expire0 + 30, '/'); // cookies expire a little after the cache so they'll definitely be counted in the next one
            $Counts[$Name0] = self::_IncrementCache($Name0, $Expire0);

            setcookie($Name1, $Now, $Expire1 + 30, '/'); // We want both cookies expiring at different times.
            $Counts[$Name1] = self::_IncrementCache($Name1, $Expire1);
         } elseif (!isset($_COOKIE[$Name0])) {
            setcookie($Name0, $Now, $Expire0 + 30, '/');
            $Counts[$Name0] = self::_IncrementCache($Name0, $Expire0);
         } elseif (!isset($_COOKIE[$Name1])) {
            setcookie($Name1, $Now, $Expire1 + 30, '/');
            $Counts[$Name1] = self::_IncrementCache($Name1, $Expire1);
         }
      }
   }
   
   /**
    * @param UserModel $Sender
    * @return type 
    */
   public function UserModel_UpdateVisit_Handler($Sender) {
      $Session = Gdn::Session();
      if (!$Session->UserID)
         return;
      
      $Invisible = Gdn::UserMetaModel()->GetUserMeta($Session->UserID, 'Plugin.WhosOnline.Invisible', FALSE);
      $Invisible = GetValue('Plugin.WhosOnline.Invisible', $Invisible);
		$Invisible = ($Invisible ? 1 : 0);
      
      $Timestamp = Gdn_Format::ToDateTime();
      $Px = $Sender->SQL->Database->DatabasePrefix;
      $Sql = "insert {$Px}Whosonline (UserID, Timestamp, Invisible) values ({$Session->UserID}, :Timestamp, :Invisible) on duplicate key update Timestamp = :Timestamp1, Invisible = :Invisible1";
      $Sender->SQL->Database->Query($Sql, array(':Timestamp' => $Timestamp, ':Invisible' => $Invisible, ':Timestamp1' => $Timestamp, ':Invisible1' => $Invisible));

      
      // Do some cleanup of old entries.
      $Frequency = C('WhosOnline.Frequency', 60);
		$History = time() - 6 * $Frequency; // give bit of buffer
      
      $Sql = "delete from {$Px}Whosonline where Timestamp < :Timestamp limit 10";
      $Sender->SQL->Database->Query($Sql, array(':Timestamp' => Gdn_Format::ToDateTime($History)));
      
      //			$SQL->Replace('Whosonline', array(
      //				'UserID' => $Session->UserID,
      //				'Timestamp' => Gdn_Format::ToDateTime(),
      //				'Invisible' => $Invisible),
      //				array('UserID' => $Session->UserID)
      //			);
   }
}