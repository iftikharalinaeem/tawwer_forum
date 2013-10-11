<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['Reporting2'] = array(
   'Name' => 'Reporting',
   'Description' => 'Allows users to report posts to moderators for abuse, terms of service violations etc.',
   'Version' => '2.0a',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'SettingsUrl' => '/settings/reporting',
   'SettingsPermission' => 'Garden.Users.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class Reporting2Plugin extends Gdn_Plugin {
   /// Methods ///
   public function Setup()  {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::Structure()->Table('Category')
         ->Column('Type', 'varchar(20)')
         ->Set();
      
      // Try and find the category by type.
      $CategoryModel = new CategoryModel();
      $Category = $CategoryModel->GetWhereCache(array('Type' => 'Reporting'));
      if (empty($Category)) {
         $Row = array(
            'Name' => 'Reported Posts',
            'UrlCode' => 'reported-posts',
            'HideAllDiscussions' => 1,
            'DisplayAs' => 'Discussions',
            'Type' => 'Reporting',
            'AllowDiscussions' => 1,
            'Sort' => 1000);
         $ID = $CategoryModel->Save($Row);
      }

      // Turn off Flagging & Reporting plugins (upgrade)
      RemoveFromConfig('EnabledPlugins.Flagging');
      RemoveFromConfig('EnabledPlugins.Reporting');
   }

   /// Controller ///

   /**
    * Handles report actions.
    *
    * @param $Sender
    * @param $RecordType
    * @param $ReportType
    * @param $ID
    * @throws Gdn_UserException
    */
   public function RootController_Report_Create($Sender, $RecordType, $ReportType, $ID) {
      if (!Gdn::Session()->IsValid())
         throw new Gdn_UserException(T('You need to sign in before you can do this.'), 403);

      $Sender->Form = new Gdn_Form();
      $ReportModel = new ReportModel();
      $Sender->Form->SetModel($ReportModel);

      $Sender->Form->SetFormValue('RecordID', $ID);
      $Sender->Form->SetFormValue('RecordType', $RecordType);
      $Sender->Form->SetFormValue('Format', 'TextEx');

      $Sender->SetData('Title', sprintf(T('Report %1s %2s'), $ReportType, $RecordType));

      if ($Sender->Form->AuthenticatedPostBack()) {
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
    * Make sure Reactions' flags are triggered, but remove Spam if present.
    */
   public function Base_BeforeFlag_Handler($Sender, $Args) {
      if (empty($Args['Flags']))
         $Args['Flags'] = TRUE;
      elseif (isset($Args['Flags']['spam']))
         unset($Args['Flags']['spam']);
   }

   /**
    * Add reporting options to discussions & comments under Flag menu.
    */
   public function Base_AfterFlagOptions_Handler($Sender, $Args) {
      $Options = array('Spam', 'Inappropriate');
      foreach ($Options as $Name) {
         $Text = Sprite('React'.$Name, 'ReactSprite').' '.Wrap(T($Name), 'span', array('class' => 'ReactLabel'));
         echo Wrap(Anchor($Text, 'report/'.$Args['RecordType'].'/'.strtolower($Name).'/'.$Args['RecordID'],
            'Popup ReactButton ReactButton-'.$Name, array('title'=>$Name, 'rel'=>"nofollow")), 'li');
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
         echo Wrap(Sprite('SpReport').' '.Anchor($Category['Name'].$CReport, CategoryUrl($Category)), 'li', array('class' => 'ReportCategory'));
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

function FormatQuote($Body) {
   if (is_object($Body)) {
      $Body = (array)$Body;
   } elseif (is_string($Body)) {
      return $Body;
   }
   
   $User = Gdn::UserModel()->GetID(GetValue('InsertUserID', $Body));
   if ($User) {
      $Result = '<blockquote class="Quote Media">'.
         '<div class="Img">'.UserPhoto($User).'</div>'.
         '<div class="Media-Body">'.
            '<div>'.UserAnchor($User).'</div>'.
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