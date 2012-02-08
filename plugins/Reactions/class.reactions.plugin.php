<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Reactions'] = array(
   'Name' => 'Reactions',
   'Description' => "Adds reaction options to discussions & comments.",
   'Version' => '1.1b',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class ReactionsPlugin extends Gdn_Plugin {
   /// Methods ///
   
   private function AddJs($Sender) {
      $Sender->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $Sender->AddJsFile('reactions.js', 'plugins/Reactions');
   }
   
   protected static $_CommentOrder;
   public static function CommentOrder() {
      if (!self::$_CommentOrder) {
         $SetPreference = FALSE;
         
         if (!Gdn::Session()->IsValid()) {
            if (Gdn::Controller() != NULL && strcasecmp(Gdn::Controller()->RequestMethod, 'embed') == 0)
               $OrderColumn = C('Plugins.Reactions.DefaultEmbedOrderBy', 'Score');
            else
               $OrderColumn = C('Plugins.Reactions.DefaultOrderBy', 'DateInserted');
         } else {
            $DefaultOrderParts = array('DateInserted', 'asc');
            
            $OrderBy = Gdn::Request()->Get('orderby', '');
            if ($OrderBy) {
               $SetPreference = TRUE;
            } else {
               $OrderBy = Gdn::Session()->GetPreference('Comments.OrderBy');
            }
            $OrderParts = explode(' ', $OrderBy);
            $OrderColumn = GetValue(0, $OrderParts, $DefaultOrderParts[0]);
            

            // Make sure the order is correct.
            if (!in_array($OrderColumn, array('DateInserted', 'Score')))
               $OrderColumn = 'DateInserted';
            

            if ($SetPreference) {
               Gdn::Session()->SetPreference('Comments.OrderBy', $OrderColumn);
            }
         }
         $OrderDirection = $OrderColumn == 'Score' ? 'desc' : 'asc';
         
         $CommentOrder = array('c.'.$OrderColumn.' '.$OrderDirection);
         
         // Add a unique order if we aren't ordering by a unique column.
         if (!in_array($OrderColumn, array('DateInserted', 'CommentID'))) {
            $CommentOrder[] = 'c.DateInserted asc';
         }

         self::$_CommentOrder = $CommentOrder;
      }
      
      return self::$_CommentOrder;
   }

   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include_once dirname(__FILE__).'/class.reactionmodel.php';
      
      $St = Gdn::Structure();
      $Sql = Gdn::SQL();
      
      $St->Table('ReactionType')
         ->Column('UrlCode', 'varchar(20)', FALSE, 'primary')
         ->Column('Name', 'varchar(20)')
         ->Column('Description', 'text', TRUE)
         ->Column('TagID', 'int')
         ->Column('Attributes', 'text', TRUE)
         ->Column('Active', 'tinyint(1)', 1)
         ->Set();
      
      $St->Table('UserTag')
         ->Column('RecordType', array('Discussion', 'Discussion-Total', 'Comment', 'Comment-Total', 'User', 'User-Total', 'Activity', 'Activity-Total', 'ActivityComment', 'ActivityComment-Total'), FALSE, 'primary')
         ->Column('RecordID', 'int', FALSE, 'primary')
         ->Column('TagID', 'int', FALSE, 'primary')
         ->Column('UserID', 'int', FALSE, array('primary', 'key'))
         ->Column('DateInserted', 'datetime')
         ->Column('Total', 'int', 0)
         ->Set();
      
      $Rm = new ReactionModel();
      
      // Insert some default tags.
      $Rm->DefineReactionType(array('UrlCode' => 'Spam', 'Name' => 'Spam', 'Log' => 'Spam', 'LogThreshold' => 5, 'RemoveThreshold' => 5, 'ModeratorInc' => 5));
      $Rm->DefineReactionType(array('UrlCode' => 'Abuse', 'Name' => 'Abuse', 'Log' => 'Moderation', 'LogThreshold' => 5, 'RemoveThreshold' => 10, 'ModeratorInc' => 5));
      $Rm->DefineReactionType(array('UrlCode' => 'Troll', 'Name' => 'Troll', 'Log' => 'Moderation', 'LogThreshold' => 5, 'ModeratorInc' => 5));
      
      $Rm->DefineReactionType(array('UrlCode' => 'Agree', 'Name' => 'Agree', 'IncrementColumn' => 'Score', 'Points' => 1));
      $Rm->DefineReactionType(array('UrlCode' => 'Disagree', 'Name' => 'Disagree'));
      $Rm->DefineReactionType(array('UrlCode' => 'Awesome', 'Name' => 'Awesome', 'IncrementColumn' => 'Score', 'Points' => 1));
      $Rm->DefineReactionType(array('UrlCode' => 'OffTopic', 'Name' => 'Off Topic'));
   }
   
   public function ActivityController_Render_Before($Sender) {
      $this->AddJs($Sender);
      $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }
   
   /// Event Handlers ///
   
   /**
    *
    * @param CommentModel $Sender
    * @param array $Args 
    */
   public function CommentModel_AfterConstruct_Handler($Sender, $Args) {
      $OrderBy = self::CommentOrder($Sender);
      $Sender->OrderBy($OrderBy);
   }
   
   /**
    * 
    * @param Gdn_Controller $Sender 
    */
   public function DiscussionController_Render_Before($Sender) {
      $Sender->ReactionsVersion = 2;
      
      $OrderBy = self::CommentOrder();
      list($OrderColumn, $OrderDirection) = explode(' ', GetValue('0', self::CommentOrder()));
      $OrderColumn = StringBeginsWith($OrderColumn, 'c.', TRUE, TRUE);
      
      $Sender->SetData('CommentOrder', array('Column' => $OrderColumn, 'Direction' => $OrderDirection));
      
      if ($Sender->ReactionsVersion == 1) {
         $Sender->AddCssFile('reactions-1.css', 'plugins/Reactions');
      } else {
         $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
         $this->AddJs($Sender);
      }
      
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }
   
   public function ActivityController_AfterActivityBody_Handler($Sender, $Args) {
      $Activity = $Args['Activity'];
      if (in_array(GetValue('ActivityType', $Activity), array('Status', 'WallPost'))) {
         WriteReactionBar($Activity);
      }
   }
   
   public function DiscussionController_AfterDiscussionBody_Handler($Sender, $Args) {
      WriteReactionBar($Args['Discussion']);
   }
   
   public function DiscussionController_AfterCommentBody_Handler($Sender, $Args) {
      if (!isset($Args['Comment']))
         return;
      
      WriteReactionBar($Args['Comment']);
   }
   
//   public function DiscussionController_CommentHeading_Handler($Sender, $Args) {
//      WriteOrderByButtons();
//   }
   
   public function Base_AfterUserInfo_Handler($Sender, $Args) {
      // Fetch the view helper functions.
      include_once Gdn::Controller()->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
      
      echo '<h2>'.T('Reactions').'</h2>';
      WriteProfileCounts();
   }
   
   /**
    *
    * @param ProfileController $Sender
    * @param type $Args 
    */
   public function ProfileController_Reactions_Create($Sender, $UserID, $Username, $Reaction, $Page = '') {
      $Sender->Permission('Garden.Profiles.View');
      
      $ReactionType = ReactionModel::ReactionTypes($Reaction);
      if (!$ReactionType)
         throw NotFoundException();
      
      list($Offset, $Limit) = OffsetLimit($Page, 5);
      
      $Sender->SetData('_Limit', $Limit + 1);
      
      $ReactionModel = new ReactionModel();
      $Data = $ReactionModel->GetRecordsWhere(array('TagID' => $ReactionType['TagID'], 'RecordType' => array('Discussion-Total', 'Comment-Total'), 'UserID' => $UserID, 'Total >' => 0),
         'DateInserted', 'desc',
         $Limit + 1, $Offset);
      
      $Sender->SetData('_CurrentRecords', count($Data));
      if (count($Data) > $Limit) {
         array_pop($Data);
      }
      
      $Sender->SetData('Data', $Data);
      $Sender->SetData('EditMode', FALSE, TRUE);
      $Sender->GetUserInfo($UserID, $Username);
      $Sender->_SetBreadcrumbs($ReactionType['Name'], $Sender->CanonicalUrl());
      $Sender->SetTabView('Reactions', 'DataList', '', 'plugins/Reactions');
      $this->AddJs($Sender);
      $Sender->AddJsFile('jquery.expander.js');
      $Sender->AddDefinition('ExpandText', T('(more)'));
      $Sender->AddDefinition('CollapseText', T('(less)'));
      
      $Sender->Render();
   }
   
   public function ProfileController_Render_Before($Sender, $Args) {
      // Grab all of the counts for the user.
      $Data = Gdn::SQL()
         ->GetWhere('UserTag', array('RecordID' => $Sender->Data('Profile.UserID'), 'RecordType' => 'User', 'UserID' => ReactionModel::USERID_OTHER))->ResultArray();
      $Data = Gdn_DataSet::Index($Data, array('TagID'));
      
      $Counts = $Sender->Data('Counts');
      foreach (ReactionModel::ReactionTypes() as $Code => $Type) {
         if (!$Type['Active'])
            continue;
         
         $Row = array(
             'Name' => $Type['Name'], 
             'Url' => Url("/profile/reactions/".$Sender->Data('Profile.UserID').'/'.rawurlencode($Sender->Data('Profile.Name')).'/'.rawurlencode($Code), TRUE), 
             'Total' => 0);
         
         if (isset($Data[$Type['TagID']])) {
            $Row['Total'] = $Data[$Type['TagID']]['Total'];
         }
         $Counts[$Type['Name']] = $Row;
      }
      $Sender->SetData('Counts', $Counts);
      
      $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
      $this->AddJs($Sender);
   }
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param string $RecordType
    * @param string $ReactionType
    * @param int $ID
    * @param bool $Undo 
    */
   public function RootController_React_Create($Sender, $RecordType, $Reaction, $ID) {
      if (!Gdn::Session()->IsValid()) {
         throw new Gdn_UserException(T('You need to sign in before you can do this.'), 403);
      }
      
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
      
      if (count($Sender->Request->Post()) == 0)
         throw PermissionException('Javascript');
      
      $ReactionType = ReactionModel::ReactionTypes($Reaction);
      
      $ReactionModel = new ReactionModel();
      $ReactionModel->React($RecordType, $ID, $Reaction);
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
}