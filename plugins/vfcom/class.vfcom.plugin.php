<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['vfcom'] = array(
   'Name' => 'Infrastructure Hosting',
   'Description' => "This plugin provides the hooks and management tools need to run Vanilla in Infrastructure Mode.",
   'Version' => '1.3',
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
   
   private $DataAPIToken;
   
   public function __construct() {
      // Get (and protect) and Infrastructure API token
      $this->DataAPIToken = GetValue('X_FRONTEND_TOKEN', $_SERVER, 'D9BB-194F674A-B9BEE7AF');
      unset($_SERVER['X_FRONTEND_TOKEN']);
      
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
            $ConfigFileKey = sprintf(Gdn_Configuration::CONFIG_FILE_CACHE_KEY, PATH_CONF.'/config.php');
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
      }
      
      $Sender->SetData('Caching', Gdn::Cache()->ActiveEnabled());
      $Sender->SetData('DebugMode', C('Debug', FALSE));
      $Sender->SetData('UpdateMode', C('Garden.UpdateMode', FALSE));
      $Sender->SetData('VFOptions', Gdn::PluginManager()->CheckPlugin('vfoptions'));
      $Sender->SetData('VFSpoof', Gdn::PluginManager()->CheckPlugin('vfspoof'));
      
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
    * Intercept Config::Save events for Infrastructure
    * 
    * When the Infrastructure config string is triggered for save, handle it here
    * by sending a call to the data server instead of trying to save it locally.
    * 
    * @param Gdn_ConfigurationSource $Sender
    */
   public function Gdn_ConfigurationSource_BeforeSave_Handler($Sender) {
      $Type = $Sender->EventArguments['ConfigType'];
      if ($Type != 'string') return;
      
      $Source = $Sender->EventArguments['ConfigSource'];
      if ($Source != 'Infrastructure') return;
      
      $Config = $Sender->EventArguments['ConfigData'];
      
      // Write config data to string format, ready for saving
      $ConfigString = Gdn_Configuration::Format($Config, array(
         'VariableName'    => 'Configuration',
         'WrapPHP'         => FALSE,
         'ByLine'          => TRUE
      ));
      
      // Alright, this is a legit client config save dudes, send to data server
      $SaveConfigQuery = Communication::DataServerRequest('/api/forum/save')
         ->Token($this->DataAPIToken)
         ->Parameter('Name', CLIENT_NAME)
         ->Parameter('Config', base64_encode($ConfigString))
         ->Method('POST')
         ->Send();
      
      $ResponseCode = GetValue('Code', $SaveConfigQuery, 404);
      if ($ResponseCode == '200')
         $Sender->EventArguments['ConfigNoSave'] = TRUE;
      return;
   }
   
   /**
    * Copy an uploaded image to local frontend for work
    * 
    * @param Gdn_Upload $Sender
    * @param array $Args
    */
   public function Gdn_Upload_CopyLocal_Handler($Sender, $Args) {
      $Parsed = $Args['Parsed'];
      if ($Parsed['Type'] != 'data')
         return;
      
      $Store = $Parsed['Name'];
      $DestPath = PATH_UPLOADS."/data/work/{$Store}";
      @mkdir(dirname($DestPath), 0775, TRUE);
      
      $File = Communication::DataServerRequest('/api/upload/get')
         ->Token($this->DataAPIToken)
         ->Parameter('Name', CLIENT_NAME)
         ->Parameter('Store', $Store)
         ->SaveAs($DestPath)
         ->Send();
      
      // If it worked, adjust the path and let the firing code know this was handled
      $StatusCode = GetValue('Code', $File);
      if ($StatusCode == '200' && GetValueR('Response.Success', $File) === TRUE) {
         $Args['Handled'] = TRUE;
         $Args['Path'] = $DestPath;
      }
      
      return;
   }
   
   public function Gdn_Upload_Delete_Handler($Sender, $Args) {
      $Parsed = $Args['Parsed'];
      if ($Parsed['Type'] != 'data')
         return;
      
      $Store = $Parsed['Name'];
      $File = Communication::DataServerRequest('/api/upload/delete')
         ->Token($this->DataAPIToken)
         ->Parameter('Name', CLIENT_NAME)
         ->Parameter('Store', $Store)
         ->Send();
      
      // If it worked, let the firing code know this was handled
      $StatusCode = GetValue('Code', $File);
      if ($StatusCode == '200') {
         $Args['Handled'] = TRUE;
      }
      return;
   }
   
   public function Gdn_Upload_SaveAs_Handler($Sender, $Args) {
      $Path = $Args['Path'];
      $Parsed = $Args['Parsed'];
      
      $Store = $Parsed['Name'];
      $UploadFile = Communication::DataServerRequest('/api/upload/file')
         ->Token($this->DataAPIToken)
         ->Parameter('Name', CLIENT_NAME)
         ->Parameter('Store', $Store)
         ->File('Upload', $Path)
         ->Send();
      
      // If it worked, adjust the path and let the firing code know this was handled
      $StatusCode = GetValue('Code', $UploadFile);
      if ($StatusCode == '201') {
         $Parsed = Gdn_Upload::Parse('~data/'.$Store);
         $Args['Parsed'] = $Parsed;
         $Args['Handled'] = TRUE;
         
         @unlink($Path);
      } else {
         throw new Exception('There was an error saving the file to the Data server.', 500);
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
      
      $Args['Urls']['data'] = "{$this->StaticURL}/uploads";
   }
   
   public function Gdn_UploadImage_SaveImageAs_Handler($Sender, $Args) {
      $Path = $Args['Path'];
      $Parsed = $Args['Parsed'];
      
      $Store = $Parsed['Name'];
      $UploadFile = Communication::DataServerRequest('/api/upload/file')
         ->Token($this->DataAPIToken)
         ->Parameter('Name', CLIENT_NAME)
         ->Parameter('Store', $Store)
         ->File('Upload', $Path)
         ->Send();
      
      $StatusCode = GetValue('Code', $UploadFile);
      if ($StatusCode == '200') {
         $Parsed = Gdn_Upload::Parse('~data/'.$Store);
         $Args['Parsed'] = $Parsed;
         $Args['Handled'] = TRUE;
         
         @unlink($Path);
      } else {
         throw new Exception('There was an error saving the file to the Data server.', 500);
      }
   }
   
   public function Setup() {
      
   }
   
}