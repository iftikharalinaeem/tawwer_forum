<?php if (!defined('APPLICATION')) exit();

/**
 * Infrastructure Hosting
 * 
 * This plugin provides the hooks and management tools needed to run Vanilla in 
 * Infrastructure Mode.
 * 
 *  Autostatic CDN
 *  Domain Switching
 *  Custom Routes (static)
 *  Config Load/Save Hooks
 *  Upload Hooks
 *  Infrastructure Control Panel
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Prorietary
 * @package Api
 * @since 1.0
 */

// Define the plugin:
$PluginInfo['vfcom'] = array(
   'Name' => 'Infrastructure Hosting',
   'Description' => "This plugin provides the hooks and management tools needed to run Vanilla in Infrastructure Mode.",
   'Version' => '1.4',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com',
   'Hidden' => TRUE
);

class VfcomPlugin extends Gdn_Plugin {
   
   protected $VfcomClient;
   protected $StaticURL;
   protected $AutoStaticURL;
   protected $WhitelistDomain = 'vanillaforums.com';
   
   public function __construct() {
      if (!defined('CLIENT_NAME'))
         define('CLIENT_NAME', 'unkown.vanillaforums.com');
         
      $ClientParts = explode('.', CLIENT_NAME);
      // Name of this client (usually the <prefix> part of http://<prefix>.<hostname>.com)
      $this->VfcomClient = array_shift($ClientParts);
      if (is_null($this->VfcomClient)) return;
      
      // Root domain of this deployment
      $this->VfcomHostname = implode('.', $ClientParts);
      
      // Targetting URL to the static content server / CDN
      $StaticFormat = C('VanillaForums.StaticFormat', 'http://%s.static.%s');
      $this->StaticURL = sprintf($StaticFormat, $this->VfcomClient, $this->VfcomHostname);
      
      $AutoStaticFormat = C('VanillaForums.AutoStaticFormat', 'http://%s.autostatic.%s');
      $this->AutoStaticURL = sprintf($AutoStaticFormat, $this->VfcomClient, $this->VfcomHostname);
      
      if (defined('PROFILER') && PROFILER) {
         global $XHPROF_ROOT, $XHPROF_SERVER_NAME;
         
         $Frontend = C('VanillaForums.Frontend', NULL);
         if (is_null($Frontend))
            return;
         
         $Frontend = str_replace('.int.','.ext.', $Frontend);
         
         $XHPROF_ROOT = '/var/www/frontend/xhprof';
         $XHPROF_SERVER_NAME = FormatString("{Frontend}/xhprof/render",array(
            'Frontend'     => $Frontend,
            'Client'       => $this->VfcomClient,
            'Hostname'     => $this->VfcomHostname
         ));
      }
   }
   
   /**
    * Hook ASAP
    * 
    * Hook right after the plugin manager so that we're one of the first plugins 
    * to load, and can get our story straight before everyone else comes and
    * messes things up.
    * 
    * @param Gdn_PluginManager $Sender 
    */
   public function Gdn_PluginManager_AfterStart_Handler($Sender) {
      
   }
   
   public function MakeAutoStatic($URL) {
      return CombinePaths(array($this->AutoStaticURL, $URL));
   }
   
   public function InfrastructurePermission() {
      Gdn::Controller()->Permission('Garden.Settings.Manage');
      
      if (!StringEndsWith(GetValue('Email', Gdn::Session()->User, NULL), "@{$this->WhitelistDomain}")) {
         throw PermissionException();
      }
   }

   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      
      if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage'))
         return;

      if (!StringEndsWith(GetValue('Email', Gdn::Session()->User, NULL), "@{$this->WhitelistDomain}"))
         return;
      
      $LinkText = T('Infrastructure');
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Site Settings', T('Settings'));
      $Menu->AddLink('Site Settings', $LinkText, 'plugin/vfcom', 'Garden.Settings.Manage');
   }
   
   /**
    * A standard 404 File Not Found error message is delivered when this action
    * is encountered.
    */
   public function HomeController_FileNotFound_Override($Sender) {
      $Sender->AddCssFile('crashstache.css', 'plugins/vfcom');
      
      if ($Sender->DeliveryMethod() == DELIVERY_METHOD_XHTML)
         $Sender->Render('filenotfound','home','plugins/vfcom');
      else
         $Sender->RenderException(NotFoundException());
   }

   public function PluginController_Vfcom_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      if (!StringEndsWith(GetValue('Email', Gdn::Session()->User, NULL), "@{$this->WhitelistDomain}"))
         throw new Exception(T("Sorry, only authorized personnel are permitted here."));

      $Sender->Title('Infrastructure');
      $Sender->AddSideMenu('plugin/vfcom');
      $Sender->Form = new Gdn_Form();
      $Sender->AddCssFile('vfcom.css', 'plugins/vfcom');

      $this->EnableSlicing($Sender);
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   public function Controller_Index($Sender) {
      
      $FormPrefix = "Form/";
      if ($Sender->Form->AuthenticatedPostBack()) {
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ClearCache", FALSE) !== FALSE) {
            // Clear all caches
            Gdn::PluginManager()->ClearPluginCache();

            // Rebuild now
            Gdn::PluginManager()->AvailablePlugins(TRUE);
            $Sender->InformMessage("The entire plugin cache has been cleared.");
            
            $ThemeManager = Gdn::ThemeManager();
            
            
            foreach ($ThemeManager->SearchPaths() as $SearchPath => $Trash) {
               $SearchPathCacheKey = 'Garden.Themes.PathCache.'.$SearchPath;
               $SearchPathCache = Gdn::Cache()->Remove($SearchPathCacheKey, array(Gdn_Cache::FEATURE_NOPREFIX => TRUE));
            }
            $Sender->InformMessage("The entire theme cache has been cleared.");
         }
            
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ClearLocalCache", FALSE) !== FALSE) {
            foreach (Gdn::PluginManager()->SearchPaths() as $SearchPath => $SearchPathName) {
               if ($SearchPathName != "local") continue;
               
               // Clear local cache
               Gdn::PluginManager()->ClearPluginCache($SearchPath);
               
               // Rebuild now
               Gdn::PluginManager()->AvailablePlugins(TRUE);
               $Sender->InformMessage("The local plugin cache has been cleared.");
               break;
            }
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_IncrementCacheRevision", FALSE) !== FALSE) {
            $Incremented = Gdn::Cache()->IncrementRevision();
            $Sender->InformMessage("The cache revision has been incremented.");
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ReloadConfig", FALSE) !== FALSE) {
            $ConfigFileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, PATH_LOCAL_CONF.'/config.php');
            Gdn::Cache()->Remove($ConfigFileKey, array(
                Gdn_Cache::FEATURE_NOPREFIX => TRUE
            ));
            $Sender->InformMessage("The client config has been reloaded.");
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ToggleCaching", FALSE) !== FALSE) {
            $NewCachingMode = !Gdn::Cache()->ActiveEnabled();
            SaveToConfig('Garden.Cache.Enabled',$NewCachingMode);
            $Sender->InformMessage(sprintf("Caching mode has been turned %s.",(($NewCachingMode) ? 'on': 'off')));
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ToggleDebugMode", FALSE) !== FALSE) {
            $NewDebugMode = !C('Debug', FALSE);
            SaveToConfig('Debug',$NewDebugMode);
            $Sender->InformMessage(sprintf("Debug mode has been turned %s.",(($NewDebugMode) ? 'on': 'off')));
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ToggleUpdateMode", FALSE) !== FALSE) {
            $NewUpdateMode = !C('Garden.UpdateMode', FALSE);
            SaveToConfig('Garden.UpdateMode',$NewUpdateMode);
            $Sender->InformMessage(sprintf("Update mode has been turned %s.",(($NewUpdateMode) ? 'on': 'off')));
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ToggleVFOptions", FALSE) !== FALSE) {
            $NewVFOptions = !Gdn::PluginManager()->CheckPlugin('vfoptions');
            if ($NewVFOptions) {
               Gdn::PluginManager()->EnablePlugin('vfoptions', FALSE, TRUE);
            } else {
               Gdn::PluginManager()->DisablePlugin('vfoptions');
               SaveToConfig('EnabledPlugins.vfoptions', FALSE);
            }
            
            $Sender->InformMessage(sprintf("VF Options has been turned %s.",(($NewVFOptions) ? 'on': 'off')));
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ToggleVFSpoof", FALSE) !== FALSE) {
            $NewVFSpoof = !Gdn::PluginManager()->CheckPlugin('vfspoof');
            if ($NewVFSpoof) {
               Gdn::PluginManager()->EnablePlugin('vfspoof', FALSE, TRUE);
            } else {
               Gdn::PluginManager()->DisablePlugin('vfspoof');
               SaveToConfig('EnabledPlugins.vfspoof', FALSE);
            }
            
            $Sender->InformMessage(sprintf("VF Spoof has been turned %s.",(($NewVFSpoof) ? 'on': 'off')));
         }
         
         if (Gdn::Request()->GetValue("{$FormPrefix}Plugin_vfcom_ToggleAdvancedStats", FALSE) !== FALSE) {
            $NewStats = !C('Garden.Analytics.Advanced');
            SaveToConfig('Garden.Analytics.Advanced', $NewStats, array('RemoveEmpty' => TRUE));
            $Sender->InformMessage(sprintf("Advanced Statistics have been turned %s.",(($NewStats) ? 'on': 'off')));
         }
      }
      
      $Sender->SetData('Caching', Gdn::Cache()->ActiveEnabled());
      $Sender->SetData('DebugMode', C('Debug', FALSE));
      $Sender->SetData('UpdateMode', C('Garden.UpdateMode', FALSE));
      $Sender->SetData('VFOptions', Gdn::PluginManager()->CheckPlugin('vfoptions'));
      $Sender->SetData('VFSpoof', Gdn::PluginManager()->CheckPlugin('vfspoof'));
      $Sender->SetData('AdvancedStats', C('Garden.Analytics.Advanced'));
      
      $Sender->Render('settings','','plugins/vfcom');
   }
   
   /**
    * Force system user to have vanillaforums.com email
    * 
    * This allows vfspoof to log us in as a person with access to the Infrastructure
    * plugin on client sites.
    * 
    * @param UserModel $Sender 
    */
   public function UserModel_BeforeSystemUser_Handler($Sender) {
      $Sender->EventArguments['SystemUser']['Email'] = 'system@vanillaforums.com';
   }
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param type $Args 
    */
   public function UtilityController_Config_Create($Sender, $Args) {
      $this->InfrastructurePermission();
      $Key = $Sender->Request->Get('k', FALSE);
      $Value =$Sender->Request->Get('v', FALSE);
      
      if ($Key === FALSE) {
         throw new Exception('Key is required', 400);
      }
      $Sender->SetData('Key', $Key);
      if ($Value === FALSE) {
         $Sender->SetData('Value', C($Key, NULL));
         $Sender->Render();
         return;
      }
      if (StringBeginsWith($Key, 'Garden.Database', TRUE)) {
         throw new Exception("You can't set this setting.", 400);
      }
      if ($Value === 'NULL') {
         RemoveFromConfig($Key);
      } else {
         if (strtolower($Value) == 'true')
            $Value = TRUE;
         elseif (strtolower($Value) == 'false')
            $Value = FALSE;
         SaveToConfig($Key, $Value);
      }
      $Sender->SetData('Value', C($Key, NULL));
      $Sender->Render();
   }
   
   /**
    * Resend emails that errored out.
    * @param Gdn_Controller $Sender 
    */
   public function UtilityController_ResendEmails_Create($Sender) {
      // Grab all of the activities that did not send.
      $Data = Gdn::SQL()
         ->Select('ActivityID')
         ->From('Activity')
         ->Where('Emailed', 5)
         ->Limit(25)
         ->Get()->ResultArray();
      
      $ActivityModel = new ActivityModel();
      $Count = 0;
      foreach ($Data as $Row) {
         $ActivityID = $Row['ActivityID'];
         $ActivityModel->SendNotification($ActivityID);
         $Count++;
      }
      $Sender->SetData('Count', $Count);
      if ($Sender->DeliveryMethod() == DELIVERY_METHOD_XHTML)
         echo "$Count processed.";
      else
         $Sender->Render();
   }

   /**
    * AutoStatic and ShowInfrastructure
    * 
    * This handles rewriting of CSS, JS and CSS Image resources to the autostatic
    * service on this cluster's data server. In addition, for clients whose configs
    * are set to do so, adds an infrastructure information banner on the top of
    * the page.
    * 
    * @param HeadModule $Sender
    */
   public function HeadModule_BeforeToString_Handler($Sender) {

      /**
       * AutoStatic CDN
       * 
       * This handles the rewrite of all CSS and JS resources to AutoStatic.
       */
      
      try {
         // Allow conditional autostatic for testing
         $UseAutoStatic = C('VanillaForums.AutoStatic.Enabled', FALSE);
         if (!$UseAutoStatic) throw new Exception();

         // Get current tags
         $Tags = $Sender->Tags();

         $AcceptTags = array('link', 'script');
         $FinalTags = array();

         foreach ($Tags as $TagIndex => $Tag) {
            $FinalTags[$TagIndex] = $Tag;
            $TagType = GetValue('_tag', $Tag);
            if (!in_array($TagType, $AcceptTags)) continue;

            switch ($TagType) {
               case 'link':
                  $Key = 'href';
                  break;

               case 'script':
                  $Key = 'src';
                  break;
            }
            $URL = GetValue($Key, $Tag);
            if (!StringBeginsWith($URL, 'http', TRUE))
               $Tag[$Key] = $this->MakeAutoStatic($URL);

            $FinalTags[$TagIndex] = $Tag;
         }

         $Sender->Tags($FinalTags);
      } catch (Exception $e){}
      
      /**
       * Infrastructure Banner
       * 
       * This allows a temporary banner to be inserted above all content, for admin users,
       * in order to ease detection of DNS migration.
       */
      
      try {
         // Only for logged-in users
         if (!Gdn::Session()->UserID) throw new Exception();

         // Only when enabled (finally)
         if (!C('VanillaForums.ShowInfrastructure', FALSE)) throw new Exception();

         if (stristr(Gdn::Session()->User->Email, 'vanillaforums.com') && Gdn::Session()->User->Admin) {
            echo '<div style="text-align:left;margin:10px;padding:5px;font-size:14px;background-color:white;color:gray;">';
            echo "<div>Upstream: ".GetValue('HTTP_X_UPSTREAM', $_SERVER, 'unknown')."</div>";
            echo "<div>Frontend: ".C('VanillaForums.Frontend', 'unknown')."</div>";
            echo '</div>';
         }
      } catch (Exception $e){}
      
   }
   
   /**
    * Static Avatar Routing
    * 
    * This forces all user avatars to the static server via routes, for those that
    * were added prior to the switch to infrastructure.
    * 
    * @param Gdn_Router $Sender
    */
   public function Gdn_Router_BeforeLoadRoutes_Handler($Sender) {
      if (is_null($this->VfcomClient)) return;
      
      $StaticRoute = "/?uploads/(.*)";
      $EncodedStaticRoute = base64_encode($StaticRoute);
      $StaticRouteDestination = "{$this->StaticURL}/uploads/$1";
      $StaticRouteMethod = "Temporary";
      
      $Sender->EventArguments['Routes'][$EncodedStaticRoute] = array(
          $StaticRouteDestination,
          $StaticRouteMethod
      );
   }
   
   /**
    * Handle Custom Domains
    * 
    * This flips users to the custom domain of a forum if accessed from any other
    * valid domain for that forum. e.g. its default vanillaforums.com domain.
    * 
    * @param Gdn_Dispatcher $Sender
    */
   public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender) {
      // Redirect if the domain in the url doesn't match that in the config (so
      // custom domains can't be accessed from their original subdomain).
      if (C('Garden.AutoDomainSwitch',TRUE) === FALSE) return;
      
      $Domain = C('Garden.Domain', '');
      $ServerName = GetValue('HTTP_HOST', $_SERVER, '');
      if ($ServerName == '')
         return;
         
      if ($ServerName != '' && $Domain != '') {
         $Domain = str_replace(array('http://', '/'), array('', ''), $Domain);
         $ServerName = str_replace(array('http://', '/'), array('', ''), $ServerName);
         if ($ServerName != $Domain)
            Redirect('http://'.$Domain.Gdn::Request()->Url(), 301);
      }
   }
   
   /**
    * Handle requests for uploaded images, such as user pics and file uploads
    * 
    * This method adds the static content url to the list of URLs that can serve
    * this request.
    * 
    * @param Gdn_Pluggable $Sender
    * @param array $Args
    * @return void
    */
   public function Gdn_Upload_GetUrls_Handler($Sender, $Args) {
      if (is_null($this->VfcomClient)) return;
      
      $Args['Urls'][''] = "{$this->StaticURL}/uploads";
   }
   
   public function Setup() {
   }
   
}