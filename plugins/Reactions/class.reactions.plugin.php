<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Reactions'] = array(
   'Name' => 'Reactions',
   'Description' => "Adds reaction options to discussions & comments.",
   'Version' => '1.0.3',
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
//      die();
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
      include dirname(__FILE__).'/structure.php';
   }
   
   public function ActivityController_Render_Before($Sender) {
      $this->AddJs($Sender);
      $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }
   
   /// Event Handlers ///
   
   /**
    * Adds items to Dashboard menu.
    * 
    * @since 1.0.0
    * @param object $Sender DashboardController.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Reputation', T('Reactions'), 'settings/reactiontypes', 'Garden.Settings.Manage');
   }
   
   /**
    *
    * @param CommentModel $Sender
    * @param array $Args 
    */
//   public function CommentModel_AfterConstruct_Handler($Sender, $Args) {
//      $OrderBy = self::CommentOrder($Sender);
//      $Sender->OrderBy($OrderBy);
//   }
   
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
   public function PostController_Render_Before($Sender) {
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }
   
   public function ActivityController_AfterActivityBody_Handler($Sender, $Args) {
      $Activity = $Args['Activity'];
      if (in_array(GetValue('ActivityType', $Activity), array('Status', 'WallPost'))) {
         WriteReactions($Activity);
      }
   }
   
//   public function DiscussionController_CommentHeading_Handler($Sender, $Args) {
//      WriteOrderByButtons();
//   }
   
   public function Base_AfterUserInfo_Handler($Sender, $Args) {
      // Fetch the view helper functions.
      include_once Gdn::Controller()->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
      echo '<div class="ReactionsWrap">';
         echo '<h2 class="H">'.T('Reactions').'</h2>';
         WriteProfileCounts();
      echo '</div>';
   }
   
   public function Base_BeforeCommentDisplay_Handler($Sender, $Args) {
      $CssClass = ScoreCssClass($Args['Object']);
      if ($CssClass) {
         $Args['CssClass'] .= ' '.$CssClass;
         SetValue('_CssClass', $Args['Object'], $CssClass);
      }
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
             'Url' => Url(CombinePaths(array(UserUrl($Sender->Data('Profile'), '', 'reactions'), rawurlencode($Code))), TRUE), 
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
      
      if ($Permission = GetValue('Permission', $ReactionType)) {
         $Sender->Permission($Permission);
      }
      
      $ReactionModel = new ReactionModel();
      $ReactionModel->React($RecordType, $ID, $Reaction);
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    *
    * @param SettingsController $Sender
    * @param type $Type
    * @param type $Active 
    */
   public function SettingsController_ActivateReactionType_Create($Sender, $Type, $Active) {
      $Sender->Permission('Garden.Settings.Manage');
      
      $Sender->Form->InputPrefix = '';
      if (!$Sender->Form->IsMyPostBack()) {
         throw PermissionException('PostBack');
      }
      
      $ReactionType = ReactionModel::ReactionTypes($Type);
      if (!$ReactionType)
         throw NotFoundException('Reaction Type');
      
      $ReactionModel = new ReactionModel();
      $ReactionType['Active'] = $Active;
      $Set = ArrayTranslate($ReactionType, array('UrlCode', 'Active'));
      $ReactionModel->DefineReactionType($Set);
      
      // Send back the new button.
      include_once $Sender->FetchViewLocation('settings_functions', '', 'plugins/Reactions');
      $Sender->DeliveryType(DELIVERY_METHOD_JSON);
      
      $Sender->JsonTarget("#ReactionType_{$ReactionType['UrlCode']} .ActivateSlider", ActivateButton($ReactionType), 'ReplaceWith');
      
      $Sender->JsonTarget("#ReactionType_{$ReactionType['UrlCode']}", 'InActive', $ReactionType['Active'] ? 'RemoveClass' : 'AddClass');      
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   public function SettingsController_ReactionTypes_Create($Sender) {
      // Grab all of the reaction types.
      $ReactionModel = new ReactionModel();
      $ReactionTypes = ReactionModel::GetReactionTypes();
      
      $Sender->SetData('ReactionTypes', $ReactionTypes);
      include_once $Sender->FetchViewLocation('settings_functions', '', 'plugins/Reactions');
      $Sender->Title(T('Reaction Types'));
      $Sender->AddSideMenu();
      $Sender->Render('ReactionTypes', '', 'plugins/Reactions');
   }
   
   /** 
    * Add the "Best Of..." link to the main menu.
    */
   public function Base_Render_Before($Sender) {
      if (is_object($Menu = GetValue('Menu', $Sender))) {
         $Menu->AddLink('BestOf', T('Best Of...'), '/bestof/everything');
      }
      
   }
      
   
   /** 
    * Add a "Best Of" view for reacted content.
    * 
    * @param type $Sender Controller firing the event.
    * @param string $ReactionType Type of reaction content to show
    * @param int $Page The current page of content
    */
   public function RootController_BestOf_Create($Sender, $Reaction = 'everything', $Page = '') {
      // Load all of the reaction types.
      try {
         $ReactionModel = new ReactionModel();
         $ReactionTypes = ReactionModel::GetReactionTypes(array('Class' => 'Good', 'Active' => 1));
         
         $Sender->SetData('ReactionTypes', $ReactionTypes);
//         $ReactionTypes = array_merge($ReactionTypes, ConsolidateArrayValuesByKey($ReactionTypeData, 'UrlCode'));
//         array_map('strtolower', $ReactionTypes);
      } catch (Exception $ex) {
         $Sender->SetData('ReactionTypes', array());
      }
      if (!isset($ReactionTypes[$Reaction])) {
         $Reaction = 'everything';
      }
      $Sender->SetData('CurrentReaction', $Reaction);

      // Define the query offset & limit.
      $Limit = C('Vanilla.Comments.PerPage', 30);
      //      $OffsetProvided = $Page != '';
      list($Offset, $Limit) = OffsetLimit($Page, $Limit);
      
      $Sender->SetData('_Limit', $Limit + 1);
      
      $ReactionModel = new ReactionModel();
      if ($Reaction == 'everything') {
         $PromotedTagID = $ReactionModel->DefineTag('Promoted', 'BestOf');
         $Data = $ReactionModel->GetRecordsWhere(
            array('TagID' => $PromotedTagID, 'RecordType' => array('Discussion', 'Comment')),
            'DateInserted', 'desc',
            $Limit + 1, $Offset);
      } else {
         $ReactionType = $ReactionTypes[$Reaction];
         $Data = $ReactionModel->GetRecordsWhere(
            array('TagID' => $ReactionType['TagID'], 'RecordType' => array('Discussion-Total', 'Comment-Total'), 'Total >=' => 1),
            'DateInserted', 'desc',
            $Limit + 1, $Offset);
      }
      
      $Sender->SetData('_CurrentRecords', count($Data));
      if (count($Data) > $Limit) {
         array_pop($Data);
      }
      $Sender->SetData('Data', $Data);

      // Set up head
      $Sender->Head = new HeadModule($Sender);
      $Sender->AddJsFile('jquery.js');
      $Sender->AddJsFile('jquery.livequery.js');
      $Sender->AddJsFile('global.js');
      $Sender->AddCssFile('style.css');
      $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
      
      // Set the title, breadcrumbs, canonical
      $Sender->Title(T('Best Of'));
      $Sender->SetData('Breadcrumbs', array(array('Name' => T('Best Of'), 'Url' => '/bestof/everything')));
      $Sender->CanonicalUrl(
         Url(
            ConcatSep('/', 'bestof/'.$Reaction.'/', PageNumber($Offset, $Limit, TRUE, Gdn::Session()->UserID != 0)), 
            TRUE), 
         Gdn::Session()->UserID == 0
      );
      
      // Modules
      $Sender->AddModule('GuestModule');
      $Sender->AddModule('SignedInModule');
      

      // Render the page.
      if (class_exists('LeaderBoardModule')) {
         $Sender->AddModule('LeaderBoardModule');

         $Module = new LeaderBoardModule();
         $Module->SlotType = 'a';
         $Sender->AddModule($Module);
      }
      $Sender->Render('bestof', '', 'plugins/Reactions');
   }
}