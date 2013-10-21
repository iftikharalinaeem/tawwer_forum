<?php if (!defined('APPLICATION')) exit();

/**
 * 
 * Changes:
 *  1.0     Release
 *  1.2.3   Allow ReactionModel() to react from any source user.
 *  1.2.4   Allow some reactions to be protected so that users can't flag moderator posts.
 * 
 * 
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Reactions'] = array(
   'Name' => 'Reactions',
   'Description' => "Adds reaction options to discussions & comments.",
   'Version' => '1.2.11',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => TRUE
);

class ReactionsPlugin extends Gdn_Plugin {
   
   const RECORD_REACTIONS_DEFAULT = 'popup';
   
   /**
    * Include ReactionsController for /reactions requests
    * 
    * Manually detect and include reactions controller when a request comes in
    * that probably uses it.
    * 
    * @param Gdn_Dispatcher $Sender
    */
   public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender, $Args) {
      if (!isset($Args['Request']))
        return;
      
      $Path = $Args['Request']->Path();
      if (preg_match('`^/?reactions`i', $Path)) {
         require_once($this->GetResource('class.reactionscontroller.php'));
      }
   }
   
   /**
    * Add mapper methods
    * 
    * @param SimpleApiPlugin $Sender
    */
   public function SimpleApiPlugin_Mapper_Handler($Sender) {
      switch ($Sender->Mapper->Version) {
         case '1.0':
            $Sender->Mapper->AddMap(array(
               'reactions/list'        => 'reactions',
               'reactions/get'         => 'reactions/get',
               'reactions/add'         => 'reactions/add',
               'reactions/edit'        => 'reactions/edit',
               'reactions/toggle'      => 'reactions/toggle'
            ));
            break;
      }
   }
   
   private function AddJs($Sender) {
      $Sender->AddJsFile('jquery-ui.js');
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
   
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
   }
   
   /**
    * 
    * @param ActivityController $Sender
    */
   public function ActivityController_Render_Before($Sender) {
      if ($Sender->DeliveryMethod() == DELIVERY_METHOD_XHTML || $Sender->DeliveryType() == DELIVERY_TYPE_VIEW) {
         $this->AddJs($Sender);
         include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
      }
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
      $Menu->AddLink('Reputation', T('Reactions'), 'reactions', 'Garden.Settings.Manage');
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
   
   /* New Html method of adding to discussion filters */
   public function Base_AfterDiscussionFilters_Handler($Sender) {
      echo '<li class="Reactions-BestOf">'
			.Anchor(Sprite('SpBestOf').' '.T('Best Of...'), '/bestof/everything', '')
		.'</li>';
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
//         $Sender->AddCssFile('reactions-1.css', 'plugins/Reactions');
      } else {
//         $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
         $this->AddJs($Sender);
      }
      
      $ReactionModel = new ReactionModel();
      if (C('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars') {
         $ReactionModel->JoinUserTags($Sender->Data['Discussion'], 'Discussion');
         $ReactionModel->JoinUserTags($Sender->Data['Comments'], 'Comment');
         
         if (isset($Sender->Data['Answers']))
            $ReactionModel->JoinUserTags($Sender->Data['Answers'], 'Comment');
      }
      
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }
   
   public function CommentModel_BeforeUpdateCommentCount_Handler($Sender, $Args) {
      if (!isset($Args['Discussion']))
         return;
      
      // A discussion with a low score counts as sunk.
      $Discussion =& $Args['Discussion'];
      if ((int)GetValue('Score', $Discussion) <= -5) {
         Gdn::Controller()->SetData('Score', GetValue('Score', $Discussion));
         SetValue('Sink', $Discussion, TRUE);
      }
   }
   
   public function Base_BeforeCommentRender_Handler($Sender) {
      include_once $Sender->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
   }

   // Moved to core w/ stub
/*   public function ActivityController_AfterActivityBody_Handler($Sender, $Args) {
      $Activity = $Args['Activity'];
      if (in_array(GetValue('ActivityType', $Activity), array('Status', 'WallPost'))) {
         WriteReactions($Activity);
      }
   }*/
   
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
   public function ProfileController_Reactions_Create($Sender, $UserID, $Username = '', $Reaction = '', $Page = '') {
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
      if (C('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) === 'avatars')
         $ReactionModel->JoinUserTags($Data);
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
   
   public function ProfileController_Render_Before($Sender) {
      if (!$Sender->Data('Profile'))
         return;
      
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
             'Url' => Url(UserUrl($Sender->Data('Profile'), '', 'reactions').'?reaction='.urlencode($Code), TRUE), 
             'Total' => 0);
         
         if (isset($Data[$Type['TagID']])) {
            $Row['Total'] = $Data[$Type['TagID']]['Total'];
         }
         $Counts[$Type['Name']] = $Row;
      }
      $Sender->SetData('Counts', $Counts);
      
//      $Sender->AddCssFile('reactions.css', 'plugins/Reactions');
      $this->AddJs($Sender);
   }
   
   /**
    * Handle user reactions.
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
      
      if (!$Sender->Request->IsPostBack())
         throw PermissionException('Javascript');
      
      $ReactionType = ReactionModel::ReactionTypes($Reaction);

      // Only allow enabled reactions
      if (!GetValue('Active', $ReactionType)) {
         throw ForbiddenException("@You may not use that Reaction.");
      }

      // Check reaction's permission if one is applied
      if ($Permission = GetValue('Permission', $ReactionType)) {
         $Sender->Permission($Permission);
      }

      $ReactionModel = new ReactionModel();
      $ReactionModel->React($RecordType, $ID, $Reaction);
      
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /** 
    * Add the "Best Of..." link to the main menu.
    */
   public function Base_Render_Before($Sender) {
      if (is_object($Menu = GetValue('Menu', $Sender))) {
         $Menu->AddLink('BestOf', T('Best Of...'), '/bestof/everything', FALSE, array('class' => 'BestOf'));
      }
      if (!IsMobile())
         $Sender->AddDefinition('ShowUserReactions', C('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT));
   }
      
   
   /** 
    * Add a "Best Of" view for reacted content.
    * 
    * @param type $Sender Controller firing the event.
    * @param string $ReactionType Type of reaction content to show
    * @param int $Page The current page of content
    */
   public function RootController_BestOfOld_Create($Sender, $Reaction = 'everything') {
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
      $Page = 'p'.GetIncomingValue('Page', 1);
      $Limit = C('Plugins.Reactions.BestOfPerPage', 30);
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
      if (C('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars')
         $ReactionModel->JoinUserTags($Data);
      $Sender->SetData('Data', $Data);

      // Set up head
      $Sender->Head = new HeadModule($Sender);
      $Sender->AddJsFile('jquery.js');
      $Sender->AddJsFile('jquery.livequery.js');
      $Sender->AddJsFile('global.js');
      $Sender->AddJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions'); // I customized this to get proper callbacks.
      $Sender->AddJsFile('library/jQuery-Wookmark/jquery.imagesloaded.js', 'plugins/Reactions');
      $Sender->AddJsFile('library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js', 'plugins/Reactions');
      $Sender->AddJsFile('tile.js', 'plugins/Reactions');
      $Sender->AddCssFile('style.css');
      // Set the title, breadcrumbs, canonical
      $Sender->Title(T('Best Of'));
      $Sender->SetData('Breadcrumbs', array(array('Name' => T('Best Of'), 'Url' => '/bestof/everything')));
      $Sender->CanonicalUrl(
         Url(
            ConcatSep('/', 'bestof/'.$Reaction, PageNumber($Offset, $Limit, TRUE, Gdn::Session()->UserID != 0)), 
            TRUE), 
         Gdn::Session()->UserID == 0
      );
      
      // Modules
      $Sender->AddModule('GuestModule');
      $Sender->AddModule('SignedInModule');
      $Sender->AddModule('BestOfFilterModule');

      // Render the page.
      if (class_exists('LeaderBoardModule')) {
         $Sender->AddModule('LeaderBoardModule');

         $Module = new LeaderBoardModule();
         $Module->SlotType = 'a';
         $Sender->AddModule($Module);
      }
      
      // Render the page (or deliver the view)
      $Sender->Render('bestof_old', '', 'plugins/Reactions');
   }
   
   /** 
    * Add a "Best Of" view for reacted content.
    * 
    * @param type $Sender Controller firing the event.
    * @param string $ReactionType Type of reaction content to show
    * @param int $Page The current page of content
    */
   public function RootController_BestOf_Create($Sender, $Reaction = 'everything') {
      Gdn_Theme::Section('BestOf');
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
      $Page = 'p'.GetIncomingValue('Page', 1);
      $Limit = C('Plugins.Reactions.BestOfPerPage', 10);
      //      $OffsetProvided = $Page != '';
      list($Offset, $Limit) = OffsetLimit($Page, $Limit);
      
      $Sender->SetData('_Limit', $Limit + 1);
      
      $ReactionModel = new ReactionModel();
      SaveToConfig('Plugins.Reactions.ShowUserReactions', FALSE, FALSE);
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
      
      if (C('Plugins.Reactions.BestOfStyle', 'Tiles') == 'Tiles') {
         $Sender->AddJsFile('plugins/Reactions/library/jQuery-Masonry/jquery.masonry.js'); // I customized this to get proper callbacks.
         $Sender->AddJsFile('plugins/Reactions/library/jQuery-Wookmark/jquery.imagesloaded.js');
         $Sender->AddJsFile('plugins/Reactions/library/jQuery-InfiniteScroll/jquery.infinitescroll.min.js');
         $Sender->AddJsFile('tile.js', 'plugins/Reactions');
         $Sender->CssClass .= ' NoPanel';
         $View = $Sender->DeliveryType() == DELIVERY_TYPE_VIEW ? 'tile_items' : 'tiles';
      } else {
         $View = 'BestOf';
         $Sender->AddModule('GuestModule');
         $Sender->AddModule('SignedInModule');
         $Sender->AddModule('BestOfFilterModule');
      }
      
      $Sender->AddCssFile('style.css');
      // Set the title, breadcrumbs, canonical
      $Sender->Title(T('Best Of'));
      $Sender->SetData('Breadcrumbs', array(array('Name' => T('Best Of'), 'Url' => '/bestof/everything')));
      $Sender->CanonicalUrl(
         Url(
            ConcatSep('/', 'bestof/'.$Reaction, PageNumber($Offset, $Limit, TRUE, Gdn::Session()->UserID != 0)), 
            TRUE), 
         Gdn::Session()->UserID == 0
      );
      
      // Render the page (or deliver the view)
      $Sender->Render($View, '', 'plugins/Reactions');
   }   
   
   /**
	 * Sort the comments by score if necessary
    * @param CommentModel $CommentModel
	 */
   public function CommentModel_AfterConstruct_Handler($CommentModel) {
		if (!C('Plugins.Reactions.CommentSortEnabled'))
			return;

      $Sort = self::CommentSort();
      switch (strtolower($Sort)) {
         case 'score':
            $CommentModel->OrderBy(array('coalesce(c.Score, 0) desc', 'c.CommentID'));
            break;
         case 'date':
         default:
            $CommentModel->OrderBy('c.DateInserted');
            break;
      }
   }
   
   public function SettingsController_AddEditCategory_Handler($Sender) {
      $Sender->ShowCustomPoints = TRUE;
   }

   /** 
    * Get the user's preference for comment sorting (if enabled).
    */
   protected static $_CommentSort;
   public static function CommentSort() {
		if (!C('Plugins.Reactions.CommentSortEnabled'))
			return;

      if (self::$_CommentSort)
         return self::$_CommentSort;
      
      $Sort = GetIncomingValue('Sort', '');
      if (Gdn::Session()->IsValid()) {
         if ($Sort == '') {
            // No sort was specified so grab it from the user's preferences.
            $Sort = Gdn::Session()->GetPreference('Plugins.Reactions.CommentSort', 'score');
         } else {
            // Save the sort to the user's preferences.
            Gdn::Session()->SetPreference('Plugins.Reactions.CommentSort', $Sort == 'score' ? 'score' : $Sort);
         }
      }

      if (!in_array($Sort, array('score', 'date')))
         $Sort = 'date';
      
      self::$_CommentSort = $Sort;
      return $Sort;
   }   
   
   /**
	 * Allow comments to be sorted by score?
	 */
	public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
		if (!C('Plugins.Reactions.CommentSortEnabled'))
			return;

		if (
          GetValue('Type', $Sender->EventArguments, 'Comment') == 'Comment' 
          && !GetValue('VoteHeaderWritten', $this)
         ):
         ?>
         <li class="Item">
            <span class="NavLabel"><?php echo T('Sort by'); ?></span>
            <span class="DiscussionSort NavBar">
               <?php
               $Query = $_GET;
               $Query['Sort'] = 'score';
               echo Anchor('Points', Url('?'.http_build_query($Query), TRUE), 'NoTop Button'.(self::CommentSort() == 'score' ? ' Active' : ''), array('rel' => 'nofollow', 'alt' => T('Sort by reaction points')));
               $Query['Sort'] = 'date';
               echo Anchor('Date Added', Url('?'.http_build_query($Query), TRUE), 'NoTop Button'.(self::CommentSort() == 'date' ? ' Active' : ''), array('rel' => 'nofollow', 'alt' => T('Sort by date added')));
            ?>
            </span>
         </li>
         <?php
         $this->VoteHeaderWritten = TRUE;
		endif;		
	}
}

if (!function_exists('WriteReactions')):
   function WriteReactions($Row) {
      $Attributes = GetValue('Attributes', $Row);
      if (is_string($Attributes)) {
         $Attributes = @unserialize($Attributes);
         SetValue('Attributes', $Row, $Attributes);
      }

      static $Types = NULL;
      if ($Types === NULL)
         $Types = ReactionModel::GetReactionTypes(array('Class' => array('Good', 'Bad'), 'Active' => 1));
      Gdn::Controller()->EventArguments['ReactionTypes'] = $Types;

      if ($ID = GetValue('CommentID', $Row)) {
         $RecordType = 'comment';
      } elseif ($ID = GetValue('ActivityID', $Row)) {
         $RecordType = 'activity';
      } else {
         $RecordType = 'discussion';
         $ID = GetValue('DiscussionID', $Row);
      }
      Gdn::Controller()->EventArguments['RecordType'] = $RecordType;
      Gdn::Controller()->EventArguments['RecordID'] = $ID;


      if (C('Plugins.Reactions.ShowUserReactions', ReactionsPlugin::RECORD_REACTIONS_DEFAULT) == 'avatars')
         WriteRecordReactions($Row);

      echo '<div class="Reactions">';
      Gdn_Theme::BulletRow();

      // Write the flags.
      static $Flags = NULL, $FlagCodes = NULL;
      if ($Flags === NULL) {
         $Flags = ReactionModel::GetReactionTypes(array('Class' => 'Flag', 'Active' => 1));
         $FlagCodes = array();
         foreach ($Flags as $Flag) {
            $FlagCodes[] = $Flag['UrlCode'];
         }
         Gdn::Controller()->EventArguments['Flags'] = &$Flags;
         Gdn::Controller()->FireEvent('Flags');
      }

      // Allow addons to work with flags
      Gdn::Controller()->EventArguments['Flags'] = &$Flags;
      Gdn::Controller()->FireEvent('BeforeFlag');

      if (!empty($Flags)) {
         echo Gdn_Theme::BulletItem('Flags');

         echo ' <span class="FlagMenu ToggleFlyout">';
         // Write the handle.
         echo ReactionButton($Row, 'Flag', array('LinkClass' => 'FlyoutButton'));
//            echo Sprite('SpFlyoutHandle', 'Arrow');
         echo '<ul class="Flyout MenuItems Flags" style="display: none;">';
         foreach ($Flags as $Flag) {
            if (is_callable($Flag))
               echo '<li>'.call_user_func($Flag, $Row, $RecordType, $ID).'</li>';
            else
               echo '<li>'.ReactionButton($Row, $Flag['UrlCode']).'</li>';
         }
         Gdn::Controller()->FireEvent('AfterFlagOptions');
         echo '</ul>';
         echo '</span> ';
      }
      Gdn::Controller()->FireEvent('AfterFlag');

      $Score = FormatScore(GetValue('Score', $Row));
      echo '<span class="Column-Score Hidden">'.$Score.'</span>';



      // Write the reactions.
      echo Gdn_Theme::BulletItem('Reactions');
      echo '<span class="ReactMenu">';
      echo '<span class="ReactButtons">';
      foreach ($Types as $Type) {
         echo ' '.ReactionButton($Row, $Type['UrlCode']).' ';
      }
      echo '</span>';
      echo '</span>';

      if (Gdn::Session()->CheckPermission(array('Garden.Moderation.Manage', 'Moderation.Reactions.Edit'), FALSE)) {
         echo Gdn_Theme::BulletItem('ReactionsMod').
            Anchor(T('Log'), "/reactions/log/{$RecordType}/{$ID}", 'Popup ReactButton');
      }

      Gdn::Controller()->FireEvent('AfterReactions');

      echo '</div>';
      Gdn::Controller()->FireAs('DiscussionController')->FireEvent('Replies');
   }

endif;
