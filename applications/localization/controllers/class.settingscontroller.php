<?php if (!defined('APPLICATION')) exit();
 
/**
 * @since 1.0
 * @package Localization
 */
class SettingsController extends DashboardController {
   
   /// Properties ///
   
   /**
    *
    * @var LocalizationModel
    */
   public $LocalizationModel;
   
   // Methods ///
   
   public function Initialize() {
      parent::Initialize();
      $this->LocalizationModel = new LocalizationModel();
      Gdn_Theme::Section('Dashboard');
   }
   
   public function Index() {
      $this->Permission('Garden.Settings.Manage');
      
      // Grab all of the locale packs...
      $LocaleModel = new LocaleModel();
      $Locales = $LocaleModel->AvailableLocalePacks();
      $LocalizationModel = $this->LocalizationModel;
      $DbLocales = $LocalizationModel->Locales();
      
      $this->Form = new Gdn_Form();
      
      if ($this->Form->AuthenticatedPostBack()) {
         try {
            if ($this->Form->GetFormValue('SaveLocalePack')) {
               // Save a locale pack's definitions.
               $LocalePack = $Locales[$this->Form->GetFormValue('LocalePack')];
               
               $Files = glob($LocalePack['PluginRoot'].'/*.php');
               $Count = 0;
               $Locale = $LocalePack['Locale'];
               
               if (!LocalizationModel::ValidateLocaleString($Locale)) {
                  throw new Gdn_UserException(sprintf('"%s" is not a valid locale string.', $Locale));
               }
               if ($Locale == 'en-CA') {
                  throw new Gdn_UserException('This locale pack has a locale of en-CA and is probably incorrect.');
               }

               $LocalizationModel->AddNewCodes = FALSE;
               foreach ($Files as $Path) {
                  $Prefix = 'unknown';
                  $LocalizationModel->SaveTranslationsFile($Path, $Locale, $Prefix);
                  $Count++;
               }
               
               // Save the version.
               Gdn::SQL()->Replace('LocaleAddon',
                  array('VersionSaved' => $LocalePack['Version']),
                  array('AddonKey' => $LocalePack['Index']));
               
               // Save the name to the locale.
               Gdn::SQL()->Database->Query("update GDN_Locale l join GDN_LocaleAddon a on l.Locale = a.Locale set l.Name = a.Name where l.Name is null;");
               
               $this->InformMessage(sprintf('%s files were successfully saved.', $Count));
            } elseif ($this->Form->GetFormValue('DownloadLocalePack')) {
               // Download a locale pack from .org.
               $AddonKey = $this->Form->GetFormValue('AddonKey');
               $this->DownloadLocalePack($AddonKey);
               return;
            } elseif ($this->Form->GetFormValue('CreateFile')) {
               $this->CreateFile($this->Form->GetFormValue('LocaleToCreate'));
               
            }
            $this->RedirectUrl = '/localization/settings';
         } catch (Exception $Ex) {
            $this->Form->AddError($Ex);
         }
      }
      
//      decho($Locales);
//      die();
      $this->SetData('LocalePacks', $Locales);
      $LocaleAddons = Gdn::SQL()->Get('LocaleAddon', 'Name')->ResultArray();
      foreach ($LocaleAddons as &$Row) {
         $Row['Status'] = '';
         if (!$Row['VersionLastDownload'])
            $Row['Status'] = 'New';
         elseif ($Row['VersionCurrent'] != $Row['VersionLastDownload']) {
            $Row['Status'] = 'Changed';
         }
         $Row['NameAndStatus'] = $Row['Name'].($Row['Status'] ? " ({$Row['Status']})" : '');
         
         $Row['SaveStatus'] = '';
         if ($Row['Status'])
            $Row['SaveStatus'] = 'Needs Download';
         elseif (!$Row['VersionSaved'])
            $Row['SaveStatus'] = 'New';
         elseif ($Row['VersionLastDownload'] != $Row['VersionSaved'])
            $Row['SaveStatus'] = 'Changed';
         $Row['NameAndSaveStatus'] = $Row['Name'].($Row['SaveStatus'] ? " ({$Row['SaveStatus']})" : '');
      }
      $this->SetData('LocaleAddons', $LocaleAddons);
      $this->SetData('DbLocales', $DbLocales);
      $this->Title('Localization Settings');
      $this->AddSideMenu();
      $this->Render();
   }
   
   public function CreateFile($Locale) {
      $this->Permission('Garden.Settings.Manage');
      
      $this->LocalizationModel->ServeLocaleFile($Locale);
   }
   
   public function DownloadLocaleList() {
      $this->Permission('Garden.Settings.Manage');
      
      $Count = $this->LocalizationModel->DownloadLocaleList();
      $this->InformMessage('The locale list was successfully downloaded.');
      
      $this->RedirectUrl = '/localization/settings';
      $this->AddSideMenu();
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
   
   public function DownloadLocalePack($AddonKey) {
      $this->Permission('Garden.Settings.Manage');
      
      $this->LocalizationModel->DownloadLocale($AddonKey);
      $this->InformMessage('The locale pack was successfully downloaded.');
      
      $this->RedirectUrl = '/localization/settings';
      $this->AddSideMenu();
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
   
   public function GenerateTransifex() {
      set_time_limit(0);
      header('Content-Type: text/plain');
      $this->LocalizationModel->GenerateAllTransifex();
   }
   
   public function ParseFolders() {
      set_time_limit(0);
      
      header('Content-Type: text/plain');
      $R = $this->LocalizationModel->ParseFolder(PATH_APPLICATIONS);
      print_r($R);
      
      $R = $this->LocalizationModel->ParseFolder(PATH_PLUGINS);
      print_r($R);
      
      $this->LocalizationModel->ParseTranslationCalls('/www/vanilla/applications/dashboard/views/modules/guest.php');
   }
   
   public function SaveLocaleDeveloper() {
      $this->Permission('Garden.Settings.Manage');
      
      set_time_limit(300);
      
      $LocaleDeveloper = Gdn::PluginManager()->GetPluginInstance('LocaleDeveloperPlugin');
      $Files = glob($LocaleDeveloper->LocalePath.'/captured_*.php');
      
      if (!empty($Files)) {
         $LocalizationModel = new LocalizationModel();
         $Count = 0;
         foreach ($Files as $Path) {
            $Prefix = strtolower(basename($Path));
            $Prefix = StringBeginsWith($Prefix, 'captured_', TRUE, TRUE);
            $Prefix = StringEndsWith($Prefix, '.php', TRUE, TRUE);
            
            list($Section, $Prefix) = explode('_', $Prefix, 2);
            $Dashboard = $Section == 'dash' ? 1 : 0;
            
            $LocalizationModel->SaveTranslationsFile($Path, 'en-CA', $Prefix, $Dashboard);
            $Count++;
         }
         
         $this->InformMessage(sprintf('%s files were successfully saved.', $Count));
      }
      LocalizationModel::RefreshCalculatedFields();
      
      $this->RedirectUrl = '/localization/settings';
      $this->AddSideMenu();
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
}