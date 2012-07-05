<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['PrizeDriven'] = array(
   'Name' => 'Prize Driven',
   'Description' => "Contains the customizations for prizedriven.com.",
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.16'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
//   'SettingsUrl' => '/settings/prizedriven',
   'RegisterPermissions' => array('Plugins.PrizeDriven.Designer', 'Plugins.PrizeDriven.Company')
);

class PrizeDrivenPlugin extends Gdn_Plugin {
   /// PROPERTIES ///


   /// METHODS ///

   public static function CanDownloadFile($Media, $CanDownloadFiles, $CanDownload) {
      // Companies have special download permissions.
      if (self::IsCompany()) {
         if (GetValue('InsertUserID', $Media) == Gdn::Session()->UserID)
            $CanDownload = TRUE;
         elseif ($CanDownloadFiles)
            $CanDownload = TRUE;
         elseif (in_array(strtolower(pathinfo(GetValue('Name', $Media), PATHINFO_EXTENSION)), array('jpg', 'gif', 'png')))
            $CanDownload = TRUE;
         else
            $CanDownload = FALSE;
      }
      return $CanDownload;
   }

   public static function DesignerCount($DiscussionID) {
      static $Cache = array();
      if (isset($Cache[$DiscussionID]))
         return $Cache[$DiscussionID];

      $Roles = array_keys(self::DesignerRoles());

      $Result = Gdn::SQL()
         ->Distinct()
         ->Select('c.InsertUserID', 'count', 'CountDesigners')
         ->From('Comment c')
         ->Join('UserRole ur', 'c.InsertUserID = ur.UserID')
         ->Where('c.DiscussionID', $DiscussionID)
         ->WhereIn('ur.RoleID', $Roles)
         ->Get()->Value('CountDesigners');

      $Cache[$DiscussionID] = $Result;

      return $Result;
   }

   public static function DesignerRoles() {
      static $DesignerRoles = NULL;
      if ($DesignerRoles === NULL) {
         $Data = Gdn::SQL()
            ->Select('r.*')
            ->From('Role r')
            ->Join('Permission p', 'r.RoleID = p.RoleID and p.JunctionID is null')
            ->Where('p.`Plugins.PrizeDriven.Designer`', 1)
            ->Get()->ResultArray();

         $DesignerRoles = ConsolidateArrayValuesByKey($Data, 'RoleID', 'Name');

      }
      return $DesignerRoles;
   }

   public static function FormatDate($Date, $Format = '%Y-%m-%d', $NullFormat = '') {
      if ($Format == 'display') {
         $Format = T('Date.DefaultFormat', '%B %e, %Y');
         $NullFormat = '-';
      }

      if (!$Date)
         return $NullFormat;
      if (!is_numeric($Date))
         $Date = strtotime($Date);
      $Result = strftime($Format, $Date);
      return $Result;
   }

   public static function FormatCompetitionSpan($StartDate, $FinishDate, $Html = true) {
      $Today = time();

      // Check to see when the competition starts.
      if ($StartDate) {
         $StartDate = Gdn_Format::ToTimestamp($StartDate);
         if ($StartDate > $Today) {
            $Result = 'Starts '.self::FormatSpan($StartDate, $Today, 'in %s');
            if ($Html) {
               $Result = '<span class="Tag PrizeDriven-TagStart">'.$Result.'</span>';
            }
            return $Result;
         }
      }

      // Check to see when the competition ends.
      if ($FinishDate) {
         $FinishDate = Gdn_Format::ToTimestamp($FinishDate) + 60 * 60 * 24 - 1;
         if ($Today < $FinishDate) {
            $Result = 'Finishes '.self::FormatSpan($FinishDate, $Today, 'in %s');
            if ($Html) {
               $Result = '<span class="Tag PrizeDriven-TagFinish">'.$Result.'</span>';
            }
            return $Result;
         } else {
            $Result = 'Finished';
            if ($Html) {
               $Result = '<span class="Tag PrizeDriven-TagFinished">'.$Result.'</span>';
            }
            return $Result;
         }
      }

      return '';

   }

   public static function FormatSpan($Timestamp1, $Timestamp2 = NULL, $InFormat = '') {
      if ($Timestamp2 === NULL)
         $Timestamp2 = time();
      
      $Hour = 60;
      $Day = 60 * 24;

      $Span = abs($Timestamp2 - $Timestamp1) / 60;

      if (!$InFormat)
         $InFormat = '%s';

      if ($Span < $Hour)
         return sprintf($InFormat, Plural(round($Span), '%s min', '%s mins'));
      elseif ($Span < $Day)
         return sprintf($InFormat, Plural(round($Span / $Hour), '%s hour', '%s hours'));
      else {
         $Days = round($Span / $Day);
         if ($Days < 30) {
            return sprintf($InFormat, Plural($Days, '%s day', '%s days'));
         } else {
            return Gdn_Format::Date($Timestamp1, T('Date.DefaultFormat', '%B %e, %Y'));
         }
      }
   }

   public static function IsCompany() {
      return !Gdn::Session()->CheckPermission('Admin') && Gdn::Session()->CheckPermission('Plugins.PrizeDriven.Company');
   }

   public static function IsDesigner() {
      return !Gdn::Session()->CheckPermission('Admin') && Gdn::Session()->CheckPermission('Plugins.PrizeDriven.Designer');
   }

   public function Setup() {
      $this->Structure();
      SaveToConfig(array(
          //'Modules.Vanilla.Panel' => array('NewDiscussionModule', 'SignedInModule', 'GuestModule', 'Ads', 'CompetitionModule'),
          'Plugins.FileUpload.UseDownloadUrl' => TRUE
          ));
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Discussion')
         ->Column('DateCompetitionStarts', 'datetime', NULL)
         ->Column('DateCompetitionFinishes', 'datetime', NULL)
         ->Column('CanDownloadFiles', 'tinyint(1)', 0)
         ->Set();
   }

   public static function ToDate($Date, $Format) {
      if (preg_match('/^(\d+)[^\d]+(\d+)[^\d]+(\d+)/', $Date, $Matches)) {
         if (strlen($Matches[1]) == 4 || $Format == 'y/m/d') {
            // y m d
            $Result = mktime(0, 0, 0, $Matches[2], $Matches[3], $Matches[1]);
         } elseif ($Matches[1] > 12 || $Format == 'd/m/y' || $Format == '31/12/2000') {
            // d m y
            $Result = mktime(0, 0, 0, $Matches[2], $Matches[1], $Matches[3]);
         } else {
            // m d y
            $Result = mktime(0, 0, 0, $Matches[1], $Matches[2], $Matches[3]);
         }
         return Gdn_Format::ToDate($Result);
      } else {
         $Result = strtotime($Date);
         if ($Result === FALSE)
            return NULL;
         else
            return Gdn_Format::ToDate($Result);
      }
   }

   /// EVENTS ///

   public function Base_BeforeDiscussionMeta_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];

      $Tag = self::FormatCompetitionSpan(GetValue('DateCompetitionStarts', $Discussion), GetValue('DateCompetitionFinishes', $Discussion));
      echo $Tag;
   }

   public function Base_BeforeDispatch_Handler($Sender) {
      date_default_timezone_set('America/New_York');
   }

   public function Base_BeforeFile_Handler($Sender, $Args) {
      // Companies have special download permissions.
      if (self::IsCompany()) {
         $Media = $Args['Media'];
         $Args['CanDownload'] = self::CanDownloadFile($Media, $Sender->Data('Discussion.CanDownloadFiles'), $Args['CanDownload']);
      }
   }

//   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
//      $Menu = $Sender->EventArguments['SideMenu'];
//      $Menu->AddLink('Add-ons', T('Prize Driven'), '/settings/prizedriven', 'Garden.Settings.Manage');
//   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function DiscussionController_Render_Before($Sender, $Args) {
      $FinishDate = $Sender->Data('Discussion.DateCompetitionFinishes');
      if ($FinishDate && (Gdn_Format::ToTimestamp($FinishDate) + 60 * 60 * 24 - 1) <= time()) {
         SetValue('Closed', $Sender->Data['Discussion'], TRUE);
         Gdn::Locale()->SetTranslation('This discussion has been closed.', 'This competition is finished.');
      } elseif ($MaxCommenters = C('Plugins.PrizeDriven.MaxCommenters')) {
         if (self::IsDesigner()) {
            // Check to see if this designer is already in the discussion.
            $Data = Gdn::SQL()->Limit(1)->GetWhere('Comment', array('DiscussionID' => $Sender->Data('Discussion.DiscussionID'), 'InsertUserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
            if (!$Data) {
               // Close the discussion if the maximum number of designers are in it.
               $DesignerCount = self::DesignerCount($Sender->Data('Discussion.DiscussionID'));
               if ($DesignerCount >= $MaxCommenters) {
                  SetValue('Closed', $Sender->Data['Discussion'], TRUE);
                  Gdn::Locale()->SetTranslation('This discussion has been closed.', 'This competition is full.');
                  $Comments = new Gdn_DataSet(array());
                  $Sender->Data['Comments'] = $Comments;
               }
            }
         }
      }
      
      $Sender->AddModule('CompetitionModule', 'Panel');




//      if (!$Sender->Data('Discussion.CanDownloadFiles')) { // $Sender->Data('Discussion.InsertUserID') ==
//         $Sender->InformMessage("You can't download files for this competition yet.", '');
//      }
   }

   /**
    * @param DiscussionModel $Sender
    * @param array $Args
    */
   public function DiscussionModel_AfterSaveDiscussion_Handler($Sender, $Args) {
      $this->UpdateDiscussionCounts(Gdn::Session()->UserID);
      $this->UpdateDiscussionCounts(GetValueR('FormPostValues.InsertUserID', $Args));
   }

   protected function UpdateDiscussionCounts($UserID) {
      // Calculat the count of discussions for the discussion's user.
      $Count = Gdn::SQL()
         ->GetCount('Discussion', array('InsertUserID' => $UserID));

      Gdn::SQL()->Put('User', array('CountDiscussions' => $Count), array('UserID' => $UserID));
   }

   /**
    *
    * @param DiscussionModel $Sender
    * @param array $Args
    */
   public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender, $Args) {
      $Post =& $Args['FormPostValues'];

      // Switch dates to the correct format.
      $DateFormat = $Post['DateFormat'];
      $Post['DateCompetitionStarts'] = self::ToDate($Post['DateCompetitionStarts2'], $DateFormat);
      $Post['DateCompetitionFinishes'] = self::ToDate($Post['DateCompetitionFinishes2'], $DateFormat);
      $Sender->Validation->ApplyRule('Company', 'Required');

      if (GetValue('Company', $Post)) {
         // Find the user.
         $UserID = $Sender->SQL->GetWhere('User', array('Name' => trim($Post['Company'])))->Value('UserID');
         if (!$UserID)
            $Sender->Validation->AddValidationResult('Company', 'The company name appears to be invalid.');
         else
            $Post['InsertUserID'] = $UserID;
      }
   }

   public function Base_BeforeDownload_Handler($Sender, $Args) {
      $Media = $Args['Media'];
      
      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage') || !Gdn::Session()->CheckPermission('Plugins.PrizeDriven.Company'))
         return;

      Gdn::SQL()
         ->Select('d.CanDownloadFiles')
         ->From('Discussion d');

      if (GetValue('ForeignTable', $Media) == 'discussion') {
         $Discussion = Gdn::SQL()->Where('d.DiscussionID', GetValue('ForeignID', $Media));
      } elseif (GetValue('ForeignTable', $Media) == 'comment') {
         Gdn::SQL()
            ->Join('Comment c', 'c.DiscussionID = d.DiscussionID')
            ->Where('c.CommentID', GetValue('ForeignID', $Media));
      } else {
         Gdn::SQL()->Reset();
         return;
      }

      $CanDownloadFiles = Gdn::SQL()->Get()->Value('CanDownloadFiles');
      $CanDownload = self::CanDownloadFile($Media, $CanDownloadFiles, Gdn::Session()->CheckPermission('Plugins.Attachments.Download.Allow'));

      if (!$CanDownload)
         throw PermissionException();
   }

   /**
    * @param Gdn_Controller $Sender
    * @param <type> $Args
    */
   public function PostController_DiscussionFormOptions_Handler($Sender, $Args) {
      $Sender->AddJsFile('prizedriven.js', 'plugins/PrizeDriven');
      $Sender->AddJsFile('jquery-ui-1.8.11.custom.min.js', 'plugins/PrizeDriven');
      $Sender->AddJsFile('jquery.autocomplete.js');
      $Sender->AddCssFile('jquery-ui-1.8.11.custom.css', 'plugins/PrizeDriven');

      $this->Form = $Sender->Form;


      if (!$this->Form->IsPostBack()) {
         $Form = $this->Form; //new Gdn_Form();
         $Form->SetValue('DateCompetitionStarts2', self::FormatDate($Form->GetValue('DateCompetitionStarts')));
         $Form->SetValue('DateCompetitionFinishes2', self::FormatDate($Form->GetValue('DateCompetitionFinishes')));
         $Form->SetValue('Company', $Form->GetValue('InsertName'));
      }

      include $Sender->FetchViewLocation('Post', '', 'plugins/PrizeDriven');
   }

//   public function SettingsController_PrizeDriven_Create($Sender, $Args) {
//      $Config = new ConfigurationModule($Sender);
//      $Config->Initialize(array(
//         'Plugins.PrizeDriven.MaxCommenters' => array('Default' => 15, 'Description' => 'This is the number of designers that can contribute to a competition.')));
//
//      $Sender->Title(T('Prize Driven Settings'));
//      $Sender->AddSideMenu();
//      $Config->RenderAll();
////      $Sender->Render('Settings', '', 'plugins/PrizeDriven');
//   }
}