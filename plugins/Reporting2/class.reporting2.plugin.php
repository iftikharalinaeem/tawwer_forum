<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['Reporting2'] = array(
   'Name' => 'Reporting',
   'Description' => 'Allows users to report posts to moderators for abuse, terms of service violations etc.',
   'Version' => '2.0.1',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'SettingsUrl' => '/settings/reporting',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com',
   'MobileFriendly' => true
);

class Reporting2Plugin extends Gdn_Plugin {
   /// Methods ///
   public function Setup()  {
      $this->Structure();
   }

   /**
    * Add a category 'Type' and a special category for reports.
    */
   public function Structure() {
      Gdn::Structure()->Table('Category')
         ->Column('Type', 'varchar(20)', TRUE)
         ->Set();

      // Try and find the category by type.
      $CategoryModel = new CategoryModel();
      $Category = $CategoryModel->GetWhere(array('Type' => 'Reporting'))->firstRow(DATASET_TYPE_ARRAY);

      if (empty($Category)) {
         // Try and get the category by slug.
         $Category = CategoryModel::Categories('reported-posts');
         if (!empty($Category)) {
            // Set the reporting type on the category.
            $CategoryModel->SetField($Category['CategoryID'], array('Type' => 'Reporting'));
         }
      }

      if (empty($Category)) {
         // Create the category if none exists
         $Row = array(
            'Name' => 'Reported Posts',
            'UrlCode' => 'reported-posts',
            'HideAllDiscussions' => 1,
            'DisplayAs' => 'Discussions',
            'Type' => 'Reporting',
            'AllowDiscussions' => 1,
            'Sort' => 1000);
         $CategoryID = $CategoryModel->Save($Row);

         // Get RoleIDs for moderator-empowered roles
         $RoleModel = new RoleModel();
         $ModeratorRoles = $RoleModel->GetByPermission('Garden.Moderation.Manage');
         $ModeratorRoleIDs = array_column($ModeratorRoles->Result(DATASET_TYPE_ARRAY), 'RoleID');

         // Get RoleIDs for roles that can flag
         $AllowedRoles = $RoleModel->GetByPermission('Garden.SignIn.Allow');
         $AllowedRoleIDs = array_column($AllowedRoles->Result(DATASET_TYPE_ARRAY), 'RoleID');
         // Disallow applicants & unconfirmed by default
         if(($Key = array_search(C('Garden.Registration.ApplicantRoleID'), $AllowedRoleIDs)) !== false) {
            unset($AllowedRoleIDs[$Key]);
         }
         if(($Key = array_search(C('Garden.Registration.ConfirmEmailRole'), $AllowedRoleIDs)) !== false) {
            unset($AllowedRoleIDs[$Key]);
         }

         // Build permissions for the new category
         $Permissions = array();
         $AllRoles = array_column(RoleModel::Roles(), 'RoleID');
         foreach ($AllRoles as $RoleID) {
            $IsModerator =  (in_array($RoleID, $ModeratorRoleIDs)) ? 1 : 0;
            $IsAllowed = (in_array($RoleID, $AllowedRoleIDs)) ? 1 : 0;
            $Permissions[] = array(
               'RoleID' => $RoleID,
               'JunctionTable' => 'Category',
               'JunctionColumn' => 'PermissionCategoryID',
               'JunctionID' => $CategoryID,
               'Vanilla.Discussions.View' => $IsModerator,
               'Vanilla.Discussions.Add' => $IsAllowed,
               'Vanilla.Comments.Add' => $IsAllowed
            );
         }

         // Set category permission & mark it custom
         Gdn::PermissionModel()->SaveAll($Permissions, array('JunctionID' => $CategoryID, 'JunctionTable' => 'Category'));
         $CategoryModel->SetField($CategoryID, 'PermissionCategoryID', $CategoryID);
      }

      // Turn off Flagging & Reporting plugins (upgrade)
      RemoveFromConfig('EnabledPlugins.Flagging');
      RemoveFromConfig('EnabledPlugins.Reporting');
   }

   /**
    * Generates the 'Report' button in the Reactions Flag menu.
    *
    * @param $Row
    * @param $RecordType
    * @param $RecordID
    * @return string
    */
   public function ReportButton($Row, $RecordType, $RecordID) {
      $Result = Anchor(
         '<span class="ReactSprite ReactFlag"></span> '.T('Report'),
         '/report/'.$RecordType.'/'.$RecordID,
         'ReactButton ReactButton-Report Popup',
          array('title'=>t('Report'), 'rel'=>"nofollow")
      );
      return $Result;
   }

   /// Controller ///

   /**
    * Set up optional default reasons.
    */
   public function SettingsController_Reporting_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');

      $Conf = new ConfigurationModule($Sender);
      $ConfItems = array(
         'Plugins.Reporting2.Reasons' => array(
            'Description' => 'Optionally add pre-defined reasons a user must select from to report content. One reason per line.',
            'Options' => array('MultiLine' => TRUE, 'Class' => 'TextBox'))
      );
      $Conf->Initialize($ConfItems);

      $Sender->AddSideMenu();
      $Sender->SetData('Title', 'Reporting Settings');
      $Sender->ConfigurationModule = $Conf;
      $Conf->RenderAll();
   }

   /**
    * Handles report actions.
    *
    * @param $Sender
    * @param $RecordType
    * @param $ID
    * @throws Gdn_UserException
    */
   public function RootController_Report_Create($Sender, $RecordType, $ID) {
      if (!Gdn::Session()->IsValid())
         throw new Gdn_UserException(T('You need to sign in before you can do this.'), 403);

      $Sender->Form = new Gdn_Form();
      $ReportModel = new ReportModel();
      $Sender->Form->SetModel($ReportModel);

      $Sender->Form->SetFormValue('RecordID', $ID);
      $Sender->Form->SetFormValue('RecordType', $RecordType);
      $Sender->Form->SetFormValue('Format', 'TextEx');

      $Sender->SetData('Title', sprintf(T('Report %s'), T($RecordType), 'Report'));

      // Set up data for Reason dropdown
      $Sender->SetData('Reasons', FALSE);
      if ($Reasons = C('Plugins.Reporting2.Reasons', FALSE)) {
         $Reasons = explode("\n",$Reasons);
         $Sender->SetData('Reasons', array_combine($Reasons,$Reasons));
      }

      // Handle form submission / setup
      if ($Sender->Form->AuthenticatedPostBack()) {
         // Temporarily disable length limit on comments
         SaveToConfig('Vanilla.Comment.MaxLength', 0, FALSE);

         // If optional Reason field is set, prepend it to the Body with labels
         if ($Reason = $Sender->Form->GetFormValue('Reason')) {
            $Body = 'Reason: '.$Reason."\n".'Notes: '.$Sender->Form->GetFormValue('Body');
            $Sender->Form->SetFormValue('Body', $Body);
         }

         if ($Sender->Form->Save())
            $Sender->InformMessage(T('FlagSent', "Your complaint has been registered."));
      }
      else {
         // Create excerpt to show in form popup
         $Row = GetRecord($RecordType, $ID);
         $Row['Body'] = SliceString(Gdn_Format::PlainText($Row['Body'], $Row['Format']), 150);
         $Sender->SetData('Row', $Row);
      }

      $Sender->Render('report', '', 'plugins/Reporting2');
   }

   /// Event Handlers ///

   /**
    * Make sure Reactions' flags are triggered.
    */
   public function Base_BeforeFlag_Handler($Sender, $Args) {
      if (Gdn::Session()->CheckPermission('Garden.SignIn.Allow')) {
         $Args['Flags']['Report'] = array($this, 'ReportButton');
      }
   }

   /**
    * Adds "Reported Posts" to MeModule menu.
    */
   public function MeModule_FlyoutMenu_Handler($Sender) {
      if (CheckPermission('Garden.Moderation.Manage')) {
         $Category = ReportModel::GetReportCategory();
         $ReportCount = ReportModel::GetUnreadReportCount();
         $CReport = $ReportCount > 0 ? ' '.Wrap($ReportCount, 'span class="Alert"') : '';
         echo Wrap(Anchor(Sprite('SpReport').' '.htmlspecialchars(T($Category['Name'])).$CReport, CategoryUrl($Category)), 'li', array('class' => 'ReportCategory'));
      }
   }

   /**
    * Adds counter for Reported Posts to MeModule's Dashboard menu.
    */
   public function MeModule_BeforeFlyoutMenu_Handler($Sender, $Args) {
      if (CheckPermission('Garden.Moderation.Manage'))
         $Args['DashboardCount'] = $Args['DashboardCount'] + ReportModel::GetUnreadReportCount();
   }

}

if (!function_exists('FormatQuote')):

/**
 * Build our flagged content quote for the new discussion.
 *
 * @param $Body
 * @return string
 */
function FormatQuote($Body) {
   if (is_object($Body)) {
      $Body = (array)$Body;
   } elseif (is_string($Body)) {
      return $Body;
   }

   $User = Gdn::UserModel()->GetID(GetValue('InsertUserID', $Body));
   if ($User) {
      $Result = '<blockquote class="Quote UserQuote Media">'.
         '<div class="Img QuoteAuthor">'.UserPhoto($User).'</div>'.
         '<div class="Media-Body QuoteText">'.
            '<div>'.UserAnchor($User).' - '.Gdn_Format::DateFull($Body['DateInserted'],'html').'</div>'.
            Gdn_Format::To($Body['Body'], $Body['Format']).
         '</div>'.
         '</blockquote>';
   } else {
      $Result = '<blockquote class="Quote">'.
         Gdn_Format::To($Body['Body'], $Body['Format']).
         '</blockquote>';
   }

   return $Result;
}

endif;

if (!function_exists('Quote')):

   function Quote($Body) {
      return FormatQuote($Body);
   }

endif;

if (!function_exists('ReportContext')):
   /**
    * Create a linked sentence about the context of the report.
    *
    * @param $Context array or object being reported.
    * @return string Html message to direct moderators to the content.
    */
   function ReportContext($Context) {
      if (is_object($Context)) {
         $Context = (array)$Context;
      }

      if ($ActivityID = GetValue('ActivityID', $Context)) {
         // Point to an activity
         $Type = GetValue('ActivityType', $Context);
         if ($Type == 'Status') {
            // Link to author's wall
            $ContextHtml = sprintf(T('Report Status Context', '%1$s by <a href="%2$s">%3$s</a>'),
               T('Activity Status', 'Status'),
               UserUrl($Context, 'Activity').'#Activity_'.$ActivityID,
               Gdn_Format::Text($Context['ActivityName'])
            );
         }
         elseif ($Type == 'WallPost') {
            // Link to recipient's wall
            $ContextHtml = sprintf(T('Report WallPost Context', '<a href="%1$s">%2$s</a> from <a href="%3$s">%4$s</a> to <a href="%5$s">%6$s</a>'),
               UserUrl($Context, 'Regarding').'#Activity_'.$ActivityID, // Post on recipient's wall
               T('Activity WallPost', 'Wall Post'),
               UserUrl($Context, 'Activity'), // Author's profile
               Gdn_Format::Text($Context['ActivityName']),
               UserUrl($Context, 'Regarding'), // Recipient's profile
               Gdn_Format::Text($Context['RegardingName'])
            );
         }
      }
      elseif (GetValue('CommentID', $Context)) {
         // Point to comment & its discussion
         $DiscussionModel = new DiscussionModel();
         $Discussion = (array)$DiscussionModel->GetID(GetValue('DiscussionID', $Context));
         $ContextHtml = sprintf(T('Report Comment Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
            CommentUrl($Context),
            T('Comment'),
            strtolower(T('Discussion')),
            DiscussionUrl($Discussion),
            Gdn_Format::Text($Discussion['Name'])
         );
      }
      elseif (GetValue('DiscussionID', $Context)) {
         // Point to discussion & its category
         $Category = CategoryModel::Categories($Context['CategoryID']);
         $ContextHtml = sprintf(T('Report Discussion Context', '<a href="%1$s">%2$s</a> in %3$s <a href="%4$s">%5$s</a>'),
            DiscussionUrl($Context),
            T('Discussion'),
            strtolower(T('Category')),
            CategoryUrl($Category),
            Gdn_Format::Text($Category['Name']),
            Gdn_Format::Text($Context['Name']) // In case folks want the full discussion name
         );
      }
      else {
         throw new Exception(T("You cannot report this content."));
      }

      return $ContextHtml;
   }

endif;
