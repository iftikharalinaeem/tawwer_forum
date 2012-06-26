<?php if (!defined('APPLICATION')) return;

class LocalizationModel extends Gdn_Model {
   const MAX_CODE_LENGTH = 250;
   
   /// Properties ///
   
   public static $Locales = NULL;
   
   public static $Prefixes = NULL;
   
   public $AddNewCodes = TRUE;
   
   /// Methods ///
   
   public static function CalculateLocales(&$Locales = NULL) {
      $En = $Locales['en-CA'];
      $Cols = array('Core', 'Admin', 'Addon', 'Other', 'ApprovedCore', 'ApprovedAdmin', 'ApprovedAddon', 'ApprovedOther');
      foreach ($Locales as &$Row) {
         
         foreach ($Cols as $Col) {
            $Num = $Row["Count{$Col}"];
            $Den = $En["Count$Col"];
            
            if (!$Den) {
               $Val = NULL;
            } else {
               $Val = $Num / $Den;
            }
            $Row["Percent$Col"] = $Val;
            $Row["Total$Col"] = $Den;
         }
      }
   }
   
   public function DownloadLocale($AddonKey) {
      // Grab the addon info.
      $SlugUrl = rawurlencode(strtolower($AddonKey)).'-locale';
      $AddonInfo = file_get_contents("http://vanillaforums.org/addon/$SlugUrl.json");
      if (!$AddonInfo)
         throw new Exception("Couldn't get addon info for $Slug");
      $AddonInfo = json_decode($AddonInfo, TRUE);
      
      // Grab the addon's zip file.
      $DownloadUrl = $AddonInfo['Url'];
      
      if (preg_match('`http://vanillaforums.org/uploads/~cf/(.+)`', $DownloadUrl, $Matches)) {
         $DownloadUrl = 'http://cdn.vanillaforums.com/www.vanillaforums.org/'.$Matches[1];
      }
      
      $TempPath = PATH_UPLOADS."/$SlugUrl.zip";
      if (!copy($DownloadUrl, $TempPath)) {
         throw new Exception("Couldn't download $DownloadUrl.");
      }
      
      // Unzip the addon.
      $Zip = new ZipArchive();
      $Zip->open($TempPath);
      $FinalPath = PATH_ROOT."/locales";
      $Zip->extractTo($FinalPath);
      $Zip->close();
      
      unlink($TempPath);
      
      // Update the locale addon db.
      $LocaleModel = new LocaleModel();
      $LocaleInfo = GetValue($AddonKey, $LocaleModel->AvailableLocalePacks());
      
      if (!$LocaleInfo) {
         throw Exception('Something went wrong downloading the locale pack. It seemed to download fine, but then we couldn\'t find it in your /locales folder.');
      }
      
      $Locale = $LocaleInfo['Locale'];
      if (!self::ValidateLocaleString($Locale)) {
         throw new Exception("'$Locale' is not a valid locale code.");
      }
      
      $this->SQL->Replace('LocaleAddon',
         array('VersionLastDownload' => $LocaleInfo['Version'], 'Locale' => $LocaleInfo['Locale']),
         array('AddonKey' => $AddonKey));
      
      return $AddonInfo;
   }
   
   public function DownloadLocaleList() {
      $Url = 'http://vanillaforums.org/addon/browse.json?filtertotype=locales&Page=1-100';
      $Addons = file_get_contents($Url);
      $Addons = json_decode($Addons, TRUE);
      $Addons = $Addons['Addons'];
      
      $Count = 0;
      foreach ($Addons as $Addon) {
         if ($Addon['Type'] != 'Locale')
            continue;
         
         $AddonKey = $Addon['AddonKey'];
         if (!$AddonKey)
            continue;
         
         $Name = trim(StringEndsWith($Addon['Name'], 'Locale', TRUE, TRUE));
         
         $this->SQL->Replace('LocaleAddon', array('Name' => $Name, 'DateLastDownload' => Gdn_Format::ToDateTime(), 'VersionCurrent' => $Addon['Version']), array('AddonKey' => $AddonKey));
         
         $Count++;
      }
      
      return $Count;
   }
   
   public static function Locales() {
      if (self::$Locales === NULL) {
         $Locales = Gdn::SQL()->Get('Locale')->ResultArray();
         $Locales = Gdn_DataSet::Index($Locales, array('Locale'));
         self::CalculateLocales($Locales);
         self::$Locales = $Locales;
      }
      return self::$Locales;
   }
   
   public function GetCodes() {
      $Codes = Gdn::SQL()->Get('LocaleCode')->ResultArray();
      $Codes = Gdn_DataSet::Index($Codes, array('Name'));
      return $Codes;
   }
   
   public function GetTranslations($Locale) {
      $Translations = Gdn::SQL()->GetWhere('LocaleTranslation', array('Locale' => $Locale))->ResultArray();
      $Translations = Gdn_DataSet::Index($Translations, array('CodeID'));
      return $Translations;
   }
   
   public function GetTranslationsWhere($Where, $Offset, $Limit) {
      $OtherLocale = 'en-CA';
      
      if (isset($Where['Search'])) {
         
         $this->SQL->Like('t.Translation', $Where['Search']);
         
         switch(GetValue('Field', $Where)) {
            case 'translation':
               break;
            default;
               $OtherLocale = $Where['Locale'];
               $Where['Locale'] = 'en-CA';
               break;
         }
         
         unset($Where['Search'], $Where['Field']);
      }
      
      $Result = $this->SQL
         ->Select('t.*')
         ->Select('c.Name')
         ->From('LocaleTranslation t')
         ->Join('LocaleCode c', 't.CodeID = c.CodeID')
         ->Where($Where)
         ->Limit($Limit, $Offset)
         ->OrderBy('c.Name')
         ->Get()->ResultArray();
      
      // Join in the english tranlations.
      Gdn::SQL()->Where('Locale', $OtherLocale);
      Gdn_DataSet::Join($Result, array('table' => 'LocaleTranslation', 'parent' => 'CodeID', 'prefix' => 'En', 'Translation', 'TranslationID'));
      
      if ($OtherLocale <> 'en-CA') {
         $Columns = array('Translation', 'TranslationID');
         
         // Swap the translations around.
         foreach ($Result as &$Row) {
            foreach ($Columns as $Column) {
               $Tmp = $Row[$Column];
               $Row[$Column] = $Row['En'.$Column];
               $Row['En'.$Column] = $Tmp;
            }
         }
      }
      
      return $Result;
   }
   
   public static function Prefixes() {
      if (self::$Prefixes === NULL) {
         $Prefixes = Gdn::SQL()->Get('LocalePrefix')->ResultArray();
         $Prefixes = Gdn_DataSet::Index($Prefixes, array('Prefix'));
         self::$Prefixes = $Prefixes;
      }
      return self::$Prefixes;
   }
   
   public function Approve($Locale, $Data) {
      $Sets = array(
          'ApprovalUserID' => Gdn::Session()->UserID,
          'DateApproved' => Gdn_Format::ToDateTime());
      
      if (GetValue('Approve', $Data))
         $Sets['Approved'] = 'Approved';
      elseif (GetValue('Reject', $Data))
         $Sets['Approved'] = 'Rejected';
      
      $this->SQL->Put('LocaleTranslation',
         $Sets,
         array('Locale' => $Locale, 'CodeID' => $Data['CodeID']));
      
      $this->UpdateLocaleCount($Locale);
   }
   
   public function ParseFolder($Folder, $Depth = 0) {
      static $Result;
      if ($Depth == 0)
         $Result = array('Inserted' => 0, 'Updated' => 0, 'Skipped' => 0);
      
      if ($Depth > 10)
         return;
      
      $Folder = rtrim($Folder, '/');
      
      // Parse the php files in the folder.
      $Paths = glob("$Folder/*.php");
      
      foreach ($Paths as $Path) {
         $Prefix = DeveloperLocale::PrefixFromPath($Path);
         
         if (preg_match('`/locale/`', $Path)) {
            continue;
            // This is a locale file.
            if (preg_match('`([a-z]{2}-[a-z]{2})`i', $Path, $Matches))
               $Locale = $Matches[1];
            else
               $Locale = 'en-CA';
            
            $R = $this->SaveTranslationsFile($Path, $Locale, $Prefix);
//            echo $Path."\n";
//            print_r($R);
            $Result['Inserted'] += $R['Inserted'];
            $Result['Updated'] += $R['Updated'];
            $Result['Skipped'] += $R['Skipped'];
         } else {
            $Dashboard = NULL;
            if (preg_match('`settings`', $Path))
               $Dashboard = 1;
            
            // This is a source file so try and grab calls to T().
            $Definitions = $this->ParseTranslationCalls($Path);
            $R = $this->SaveTranslations($Definitions, 'en-CA', 'unknown', $Dashboard);
            $Result['Inserted'] += $R['Inserted'];
            $Result['Updated'] += $R['Updated'];
            $Result['Skipped'] += $R['Skipped'];
         }
      }
      
      // Grab the subfolders and recurse.
      $Subfolders = glob("$Folder/*", GLOB_ONLYDIR);
      foreach ($Subfolders as $Subfolder) {
         $this->ParseFolder($Subfolder, $Depth + 1);
      }
      return $Result;
   }
   
   public function ParseTranslationCalls($Path) {
      $Str = file_get_contents($Path);
      $Regex = <<<EOT
`T\s*\(\s*'([^\\\\']+)'\s*(?:,\s*'([^']+)'\s*)?\)`
EOT;
      
      $Definitions = array();
      
      if (preg_match_all($Regex, $Str, $Matches, PREG_SET_ORDER)) {
         foreach ($Matches as $Match) {
            $Code = $Match[1];
            $Default = GetValue(2, $Match, $Code);
            
//            if ($Default != $Code)
//               print_r($Match);
            
            $Definitions[$Code] = T($Code, $Default);
         }
//         print_r($Matches);
      }
      return $Definitions;
   }
   
   public static function RefreshCalculatedFields() {
      Gdn::Database()->Query("update GDN_LocaleCode c
         set Dashboard = coalesce(MyDashboard, CapturedDashboard, 0)");

      Gdn::Database()->Query("update GDN_LocaleCode c
         join GDN_LocalePrefix p
            on c.Prefix = p.Prefix
         set c.Active = coalesce(c.MyActive, p.Active)");
   }
   
   public function SaveTranslation($Locale, $Data) {
      // Grab the current translation.
      $Translation = Gdn::SQL()->GetWhere('LocaleTranslation', array('Locale' => $Locale, 'CodeID' => $Data['CodeID']))->FirstRow(DATASET_TYPE_ARRAY);
      $Now = Gdn_Format::ToDateTime();
      $UserID = Gdn::Session()->UserID;
      
      if (!$Data['Translation'])
         $Data['Translation'] = NULL;
      
      if ($Translation) {
         if ($Translation['Translation'] == $Data['Translation'])
            return;
         
         $TranslationID = $Translation['TranslationID'];
         
         $Set = array('Translation' => $Data['Translation']);
         if ($Translation['DateInserted']) {
            $Set['DateUpdated'] = $Now;
            $Set['UpdateUserID'] = $UserID;
         } else {
            $Set['DateInserted'] = $Now;
            $Set['InsertUserID'] = $UserID;
         }
         
         if ($Data['Translation']) {
            $Set['Approved'] = 'Translated';
         } else {
            $Set['Approved'] = 'New';
         }
         
         Gdn::SQL()->Put('LocaleTranslation', $Set, array('TranslationID' => $TranslationID));
      } else {
         $TranslationID = Gdn::SQL()->Insert('LocaleTranslation',
            array('Locale' => $Locale, 'CodeID' => $Data['CodeID'], 'Translation' => $Data['Translation'], 'InsertUserID' => $UserID, 'DateInserted' => $Now, 'Approved' => 'Translated'));
      }
      
      if ($TranslationID && $Data['Translation']) {
         Gdn::SQL()->Insert('LocaleTranslationVersion', 
            array('TranslationID' => $TranslationID, 'Translation' => $Data['Translation'], 'InsertUserID' => $UserID, 'DateInserted' => $Now));
      }
      $this->UpdateLocaleCount($Locale, $Data['CodeID']);
   }
   
   public function SaveTranslations($Definitions, $Locale = 'en-CA', $Prefix = 'unknown', $Dashboard = NULL) {
      $AllCodes = $this->GetCodes();
      $Prefixes = self::Prefixes();
      $Locales = self::Locales();
      $CurrentTranslations = $this->GetTranslations($Locale);
      $Px = Gdn::Database()->DatabasePrefix;
      $UserID = Gdn::Session()->UserID;
      
      $Result = array('Inserted' => 0, 'Updated' => 0, 'Skipped' => 0);
      
      // Save the prefix.
      if (!isset($Prefixes[$Prefix])) {
         $Set = array('Prefix' => $Prefix);
         Gdn::SQL()->Options('Ignore', TRUE)->Insert('LocalePrefix', $Set);
         self::$Prefixes[$Prefix] = $Set;
      }
      
      // Save the locale.
      if (!isset($Locales[$Locale])) {
         $Set = array('Locale' => $Locale);
         Gdn::SQL()->Options('Ingore', TRUE)->Insert('Locale', $Set);
         $Locales[$Locale] = $Set;
         self::$Locales[$Locale] = $Set;
      }
      
      foreach ($Definitions as $Name => $Translation) {
         if (strlen($Name) > self::MAX_CODE_LENGTH) {
            trigger_error("Code too long: $Name");
            Gdn::SQL()->Reset();
            continue;
         }
         
         // Get the code ID of the translation.
         if (is_numeric($Name)) {
            // The name is really a code.
            $CodeID = $Name;
            
         } elseif (!isset($AllCodes[$Name])) {
            if (!$this->AddNewCodes)
               continue;
            
            $CodeID = Gdn::SQL()->Options('Ignore', TRUE)->Insert('LocaleCode', array('Name' => $Name, 'Prefix' => $Prefix, 'CapturedDashboard' => $Dashboard, 'Dashboard' => (int)$Dashboard));
            if (!$CodeID)
               continue;
         } else {
            $Code = $AllCodes[$Name];
            $CodeID = $Code['CodeID'];
            $Set = array();
            // Check to see if we need to update the code.
            if ($Prefix != 'unknown' && $Code['Prefix'] != 'core') {
               if ($Code['Prefix'] != $Prefix)
                  $Set['Prefix'] = $Prefix;
            }
            if ($Dashboard !== NULL && ($Code['CapturedDashboard'] === NULL || $Dashboard != $Code['CapturedDashboard'])) {
               $Set['CapturedDashboard'] = $Dashboard;
               $Set['Dashboard'] = $Code['MyDashboard'] !== NULL ? $Code['MyDashboard'] :
                  $Dashboard !== NULL ? $Dashboard : 0;
            }
            
            if (!empty($Set)) {
               Gdn::SQL()->Put('LocaleCode', $Set, array('CodeID' => $CodeID));
            }
         }
         
         $TranslationID = FALSE;
         if (isset($CurrentTranslations[$CodeID])) {
            $CurrentTranslationInfo = $CurrentTranslations[$CodeID];
            if ($Translation == $CurrentTranslationInfo['Translation']) {
               $Result['Skipped']++;
               continue;
            }
            
            $TranslationID = $CurrentTranslationInfo['TranslationID'];
         }
         
         $Now = Gdn_Format::ToDateTime();
         
         // Save the translation.
         if ($TranslationID) {
            if (!is_string($Translation))
               continue;
            
            // See if there is already a version of this translation.
            $TranslationVersion = Gdn::SQL()->GetWhere('LocaleTranslation',
               array('TranslationID' => $TranslationID, 'Translation' => $Translation))->ResultArray();
            
            Gdn::SQL()->Put('LocaleTranslation',
               array('Translation' => $Translation, 'DateUpdated' => $Now, 'UpdateUserID' => $UserID, 'Approved' => 'Translated'),
               array('TranslationID' => $TranslationID));
            $Result['Updated']++;
            
            if (!$TranslationVersion && $Translation) {
               // Save the version of the translation.
               Gdn::SQL()->Insert('LocaleTranslationVersion',
                  array('TranslationID' => $TranslationID, 'Translation' => $Translation, 'DateInserted' => $Now, 'InsertUserID' => $UserID));
            }
         } else {
            Gdn::SQL()->Reset();
            $TranslationID = Gdn::SQL()->Insert('LocaleTranslation',
               array('CodeID' => $CodeID, 'Locale' => $Locale, 'Translation' => $Translation, 'Approved' => 'Translated',
                   'DateInserted' => $Now, 'InsertUserID' => $UserID));
            $Result['Inserted']++;
            
            if ($TranslationID) {
               // Save the version of the translation.
               Gdn::SQL()->Insert('LocaleTranslationVersion',
                  array('TranslationID' => $TranslationID, 'Translation' => $Translation, 'DateInserted' => $Now, 'InsertUserID' => $UserID));
            }
         }
      }
      $this->UpdateLocaleCount($Locale);
      return $Result;
   }
   
   public function ServeLocaleFile($Locale) {
      $this->UpdateLocaleCounts();
      
      
      $this->SQL
         ->Select('c.CodeID')
         ->Select('c.Prefix')
         ->Select('c.Name', '', 'Code')
         ->Select('coalesce(dt.Translation, c.Name)', '', 'English')
         ->Select('t.Locale')
         ->Select('t.Translation')
         ->From('LocaleTranslation t')
         ->Join('LocaleTranslation dt', "t.CodeID = dt.CodeID and dt.Locale = 'en-CA'")
         ->Join('LocaleCode c', 'c.CodeID = t.CodeID and c.CodeID = dt.CodeID')
         ->Where('t.Locale', $Locale)
         ->Where('c.Active', 1);
      
      $Data = $this->SQL->Get()->ResultArray();
      
      // Write the header.
      
      
   }
   
   public function UpdateLocaleCount($Locale, $CodeID = NULL) {
      $Where = array('Locale' => $Locale, 'Approved' => array('Translated', 'Approved'), 'Active' => 1);
      
      if ($CodeID) {
         $CodeRow = $this->SQL->GetWhere('LocaleCode', array('CodeID' => $CodeID))->FirstRow(DATASET_TYPE_ARRAY);
         if ($CodeRow)
            $Where['CountGroup'] = $CodeRow['CountGroup'];
      }
      
      $Data = $this->SQL
         ->Select('TranslationID', 'count', 'CountTranslations')
         ->Select('CountGroup')
         ->Select('Approved')
         ->From('LocaleCode c')
         ->Join('LocaleTranslation t', 'c.CodeID = t.CodeID')
         ->Where($Where)
         ->GroupBy('CountGroup')
         ->GroupBy('Approved')
         ->Get()->ResultArray();
      
      $Sets = array('CountCore' => 0, 'CountAdmin' => 0, 'CountAddon' => 0, 'CountOther' => 0, 'CountApprovedCore' => 0, 'CountApprovedAdmin' => 0, 'CountApprovedAddon' => 0, 'CountApprovedOther' => 0);
      foreach ($Data as $Row) {
         $Columns = array('Count'.$Row['CountGroup']);
         
         if ($Row['Approved'] == 'Approved' || $Locale == 'en-CA') {
            $Columns[] = 'CountApproved'.$Row['CountGroup'];
         }
         
         foreach ($Columns as $Column) {
            if (isset($Sets[$Column]))
               $Sets[$Column] += $Row['CountTranslations'];
            else
               $Sets[$Column] = $Row['CountTranslations'];
         }
      }
      
      $this->SQL->Put('Locale', $Sets, array('Locale' => $Locale));
      return array('Data' => $Data, 'Sets' => $Sets);
   }
   
   public function UpdateLocaleCounts() {
      // Update the prefixes.
      Gdn::SQL()->Update('LocaleCode c')
         ->Join('LocalePrefix p', 'p.Prefix = c.Prefix')
         ->Set('c.CountGroup', 'p.CountGroup', FALSE, FALSE)
         ->Set('c.Active', 'coalesce(c.MyActive, p.Active)', FALSE, FALSE)
         ->Put();
      
      // Make sure every code has a translation for every locale.
      $Sql = "insert GDN_LocaleTranslation (
	Locale,
	CodeID
)
select
	l.Locale,
	c.CodeID
from GDN_Locale l
join GDN_LocaleCode c
	on 1 = 1
left join GDN_LocaleTranslation t
	on t.Locale = l.Locale and t.CodeID = c.CodeID
where t.TranslationID is null";
      Gdn::Database()->Query($Sql);
      
      $Sql = "select
	t.Locale,
	c.CountGroup,
	count(t.TranslationID) as CountTranslations
from GDN_LocaleTranslation t
join GDN_LocaleCode c
	on c.CodeID = t.CodeID
where t.Translation is not null
   and c.Active = 1
group by t.Locale, c.CountGroup";
      
      $Data = Gdn::Database()->Query($Sql)->ResultArray();
      $Locales = self::Locales();
//      $Data = Gdn_DataSet::Index($Data, array('Locale'));
      
      $Sets = array();
      foreach ($Data as $Row) {
         $CurrentValue = GetValueR($Row['Locale'].'.'.'Count'.$Row['CountGroup'], $Locales);
         if ($CurrentValue != $Row['CountTranslations'])
            $Sets[$Row['Locale']]['Count'.$Row['CountGroup']] = $Row['CountTranslations'];
      }
      
      foreach ($Sets as $Locale => $Set) {
         Gdn::SQL()->Put('Locale', $Set, array('Locale' => $Locale));
      }
      
      return $Data;
   }
   
   public static function ValidateLocaleString($String) {
      return preg_match('`^[a-z]{2}(?:[_-][a-z]{2})?$`i', $String);
   }
   
   public function SaveTranslationsFile($Path, $Locale = 'en-CA', $Prefix = 'unknown', $Dashboard = NULL) {
      $Definition = array();
      include($Path);
      return $this->SaveTranslations($Definition, $Locale, $Prefix, $Dashboard);
   }
   
   public function GenerateAllTransifex($DestFolder = '/www/tx/vanilla') {
      // Generate the source files.
      $this->GenerateTransifex("$DestFolder/source/vanilla.site_core/en_CA.php", 'en-CA', 0);
      $this->GenerateTransifex("$DestFolder/source/vanilla.dash_core/en_CA.php", 'en-CA', 1);
      $this->GeneratePO("$DestFolder/source/vanilla.test/en_CA.po", 'en-CA', 0);
      return;
      // Grab the active locales.
      $Locales = self::Locales();
      foreach ($Locales as $Code => $Locale) {
         if ($Code == 'en-CA' || !$Locale['Active'])
            continue;
         
         $FileCode = str_replace('-', '_', $Code);
         $this->GenerateTransifex("$DestFolder/translations/vanilla.site_core/$FileCode.php", $Code, 0);
         $this->GenerateTransifex("$DestFolder/translations/vanilla.dash_core/$FileCode.php", $Code, 1);
      }
   }
   
   public function GetTranslationData($Locale, $Dashboard = 0) {
      $QLocale = $this->Database->Connection()->quote($Locale);
      
      $this->SQL
         ->Select('c.Name')
         ->Select('c.Description')
         ->Select('t.Translation')
         ->From('LocaleCode c')
         ->Join('LocaleTranslation t', "c.CodeID = t.CodeID and t.Locale = $QLocale", $Locale == 'en-CA' ? 'left' : 'inner')
         ->Where('c.Active', 1)
         ->Where('c.Dashboard', $Dashboard)
         ->OrderBy('Name');
      
      if ($Locale != 'en-CA')
         $this->SQL->Where('t.Translation is not null');
      
      $Data = $this->SQL->Get()->ResultArray();
      
      return $Data;
   }
   
   public function GeneratePO($Path, $Locale, $Dashboard = 0) {
      $Data  = $this->GetTranslationData($Locale, $Dashboard);
      $Esc = "\0\f\n\r\t\v\"";

      echo "Generating $Path...\n";
      // Write the file.
      if (!file_exists(dirname($Path))) {
         mkdir(dirname($Path), 0777, TRUE);
      }
      
      $Date = date('c');
      
      $Header = <<<EOT
msgid ""
msgstr ""
   "Project-Id-Version: $Date\\n"
   "Report-Msgid-Bugs-To: Todd Burry <todd@vanillaforums.com>\\n"
   "POT-Creation-Date: $Date\\n"
   "PO-Revision-Date: $Date\\n"
   "Last-Translator: Todd Burry <todd@vanillaforums.com>\\n"
   "Language-Team: Todd Burry <todd@vanillaforums.com>\\n"
   "MIME-Version: 1.0\\n"
   "Content-Type: text/plain; charset=UTF-8\\n"
   "Content-Transfer-Encoding: 8bit\\n"

EOT;
      
      $fp = fopen($Path, 'wb');
      fwrite($fp, $Header);
      
      $i = 0;
      foreach ($Data as $Row) {
         $Code = $Row['Name'];
         $Translation = $Row['Translation'];
         $Description = $Row['Description'];
         
         if (!$Translation) {
            $Translation = $Code;
         }
         
         fwrite($fp, "\n");
         
         // Write the description.
         if ($Description) {
            $Comment = str_replace(array("\r\n", "\n", "\r"), "\n#. ", $Description);
            $Comment = '#. '.$Comment."\n";
            fwrite($fp, $Comment);
         }
         
         // Fix the msgid, msgstr for PO rules.
         if ($Code[0] === "\n" && $Translation['0'] !== "\n")
            $Code = ltrim($Code, "\n");
         elseif ($Translation[0] === "\n" && $Code[0] !== "\n")
            $Translation = ltrim($Translation, "\n");
         
         if (substr($Code, -1) === "\n" && substr($Translation, -1) !== "\n")
            $Code = rtrim($Code, "\n");
         elseif (substr($Translation, -1) === "\n" && substr($Code, -1) !== "\n")
            $Translation = rtrim($Translation, "\n");
         
         // Write the id.
         fwrite($fp, 'msgid "'.addcslashes($Code, $Esc)."\"\n");
         
         // Write the string.
         fwrite($fp, 'msgstr "'.addcslashes($Translation, $Esc)."\"\n");
         $i++;
//         if ($i > 20)
//            break;
      }
      fclose($fp);
   }
   
   public function GenerateTransifex($Path, $Locale, $Dashboard = 0) {
      $Data  = $this->GetTranslationData($Locale, $Dashboard);

      echo "Generating $Path...\n";
      // Write the file.
      if (!file_exists(dirname($Path))) {
         mkdir(dirname($Path), 0777, TRUE);
      }
      
      $fp = fopen($Path, 'wb');
      fwrite($fp, "<?php\n\n");
      
      $LastC = '';
      
      foreach ($Data as $Row) {
         $Code = $Row['Name'];
         $Translation = $Row['Translation'];
         $Description = $Row['Description'];
         
         // Add a blank line between letters of the alphabet.
         if (isset($Code[0]) && strcasecmp($LastC, $Code[0]) != 0) {
            fwrite($fp, "\n");
            $LastC = $Code[0];
         }
         
         // Write the description as a comment.
         if ($Description) {
            $Comment = self::FormatComment($Description);
            fwrite($fp, $Comment);
         }
         
         if (!$Translation) {
            $Translation = $Code;
         }     

         // Write the definition.
         $Str = '$Definition['.var_export($Code, TRUE).'] = '.var_export($Translation, TRUE).";\n";
         fwrite($fp, $Str);
         
         if ($Description)
            fwrite($fp, "\n");
      }

      fclose($fp);
   }
   
   public static function FormatComment($Str) {
      $Str = str_replace(array('/*', '*/'), '', $Str);
      $Str = str_replace("\n", "\n * ", $Str);
      return "\n/**$Str***/\n";
   }
}