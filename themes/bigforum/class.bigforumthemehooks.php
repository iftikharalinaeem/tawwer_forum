<?php if (!defined('APPLICATION')) exit();

class BigForumThemeHooks implements Gdn_IPlugin {
	
	public function DiscussionController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	public function DiscussionsController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	public function CategoriesController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	public function DraftsController_Render_Before($Sender) {
		$Sender->AddModule('DiscussionFilterModule');
	}
	
   /*
   public function __construct() {
      SaveToConfig(array(
         'Garden.Thumbnail.Size' => '80',
         'Plugins.Gravatar.DefaultAvatar' => 'themes/pennyarcade/design/images/generic_user.png'
      ), NULL, FALSE);
   }
	
	public function Gdn_Dispatcher_BeforeDispatch_Handler($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
		Gdn::Locale()->SetTranslation('Apply for Membership', 'Sign Up');
//		Gdn::Locale()->SetTranslation('Apply', 'Sign Up');
		Gdn::Locale()->SetTranslation('Bookmarked Discussions', 'Bookmarked Threads');
		Gdn::Locale()->SetTranslation('My Discussions', 'My Threads');
		Gdn::Locale()->SetTranslation('All Discussions', 'All Threads');
		Gdn::Locale()->SetTranslation('Recent Discussions', 'Recent Threads');
		Gdn::Locale()->SetTranslation('Write Comment', 'Write Reply');
		Gdn::Locale()->SetTranslation('Post Comment', 'Post Reply');
		Gdn::Locale()->SetTranslation('Post Discussion', 'Post Thread');
		Gdn::Locale()->SetTranslation('Discussion Title', 'Thread Title');
		Gdn::Locale()->SetTranslation('Start a New Discussion', 'Start a New Thread');
		Gdn::Locale()->SetTranslation('Moderators', '<span class="ModeratorsIcon"></span>Moderators');
		Gdn::Locale()->SetTranslation('%s said:', '%s said: <span class="PaSprite GreenArrow"></span>');
	}
		
	public function Base_Render_Before($Sender) {
		// Remove New Discussion module from the panel
		if (array_key_exists('Panel', $Sender->Assets)) {
			if (array_key_exists('NewDiscussionModule', $Sender->Assets['Panel']))
				unset($Sender->Assets['Panel']['NewDiscussionModule']);
		}

		// Add the fade.js if we're on the default master
		if ($Sender->MasterView == '')
			$Sender->AddJsFile('themes/pennyarcade/js/fade.js');

		// Set the favicon if there is a head module
		if (property_exists($Sender, 'Head') && is_object($Sender->Head))
			$Sender->Head->SetFavIcon(Asset('themes/pennyarcade/design/images/favicon.ico'));
	}

	public function CategoriesController_BeforeCategoryItem_Handler($Sender) {
		$NumRows = GetValue('NumRows', $Sender->EventArguments, 0);
		$Counter = GetValue('Counter', $Sender->EventArguments, -1);
		$Category = GetValue('Category', $Sender->EventArguments);
		if (!is_object($Category))
			return;
		
		if ($Counter == -1)
			$Sender->EventArguments['Counter'] = 1;
		else
			$Sender->EventArguments['Counter']++;
			
		if ($Category->Depth == 1 && $Counter > 1)
			$Sender->EventArguments['CatList'] .= '</ul><ul class="DataList CategoryList CategoryListWithHeadings">';
	}
	*/
	// Make sure that the discussion query pulls enough information to show & gravatar icons.
//	public function DiscussionModel_AfterDiscussionSummaryQuery_Handler($Sender) {
//		$Sender->SQL
//			->Select('iu.About', '', 'InsertStatus')
//			->Select('iu.Email', '', 'FirstEmail')
//			->Select('lcu.Photo', '', 'LastPhoto')
//			->Select('lcu.Email', '', 'LastEmail');
//	}
	
	// Grab some extra information about the user for views
//	public function CommentModel_AfterCommentQuery_Handler($Sender) {
//		$Sender->SQL
//			->Select('iu.About', '', 'InsertStatus')
//			->Select('iu.Jailed', '', 'InsertJailed')
//			->Select('iu.Banned', '', 'InsertBanned');
//	}
//	public function DiscussionModel_BeforeGetID_Handler($Sender) {
//		$Sender->SQL
//			->Select('iu.About', '', 'InsertStatus')
//			->Select('iu.Jailed', '', 'InsertJailed')
//			->Select('iu.Banned', '', 'InsertBanned');
//	}
	
	/*
	// Add the insert user's roles to the comment data so we can visually identify different roles in the view
	public function DiscussionController_Render_Before($Sender) {
		$Session = Gdn::Session();
		if ($Session->IsValid()) {
			$JoinUser = array($Session->User);
			RoleModel::SetUserRoles($JoinUser, 'UserID');
		}
		if (property_exists($Sender, 'Discussion')) {
			$JoinDiscussion = array($Sender->Discussion);
			RoleModel::SetUserRoles($JoinDiscussion, 'InsertUserID');
			RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
		}
		
		// Add moderator module to the page
//		$ModeratorsModule = new CategoryModeratorsModule($Sender);
//		if (!property_exists($Sender, 'Category') || !is_object($Sender->Category)) {
//			$CategoryModel = new CategoryModel();
//			$Sender->Category = $CategoryModel->GetID($Sender->CategoryID);
//		}
//		if ($Sender->Category) {
//			$ModeratorsModule->GetData($Sender->Category);
//			$Sender->AddModule($ModeratorsModule);
//		}
	}
	
	public function CategoriesController_Render_Before($Sender) {
		// Add moderator module to the page if a category is defined
//		if (property_exists($Sender, 'Category') && is_object($Sender->Category)) {
//			$ModeratorsModule = new CategoryModeratorsModule($Sender);
//			$ModeratorsModule->GetData($Sender->Category);
//			$Sender->AddModule($ModeratorsModule, 'Content');
//		}
	}
	*/
	
   /**
    *
    * @param NBBCPlugin $Sender 
   public function NBBCPlugin_AfterNBBCSetup_Handler($Sender, $Args) {
      $BBCode = $Args['BBCode'];
      $UseAutoStatic = C('VanillaForums.AutoStatic.Enabled', TRUE);
      
      if ($UseAutoStatic) {
         $BBCode->smiley_url = Gdn::PluginManager()->GetPluginInstance('vfcom', Gdn_PluginManager::ACCESS_PLUGINNAME)->MakeAutoStatic('/themes/pennyarcade/design/images');
         $BBCode->smiley_dir = PATH_ROOT.'/themes/pennyarcade/design/images/';
      } else {
         $BBCode->smiley_url = '/themes/pennyarcade/design/images/';
      }
      
      // Use the same smileys as emotify.
      $BBCode->smileys = array(
      ':winky:'=>'winky.gif',
      ':twisted:'=>'icon_twisted.gif',
      ':evil:'=>'icon_evil.gif',
      ':cry:'=>'icon_cry.gif',
      ':oops:'=>'icon_redface.gif',
      ':P'=>'icon_razz.gif',
      ':x'=>'icon_mad.gif',
      ':lol:'=>'icon_lol.gif',
      '8-)'=>'icon_cool.gif',
      ':?'=>'icon_confused.gif',
      ':shock:'=>'icon_eek.gif',
      ':o'=>'icon_surprised.gif',
      ':('=>'icon_sad.gif',
      ':)'=>'icon_smile.gif',
      ':D'=>'icon_biggrin.gif',
      ':wink:'=>'icon_wink.gif',';-)'=>'icon_wink.gif',
      ':zzz:'=>'sleepy.gif',
      ':...:'=>'ellipses.gif',
      ':whistle:'=>'whistle.gif',
      ':!!:'=>'exclamation.gif',
      'O_o'=>'icon_eh2.gif',
      '<3'=>'icon_heartbeat.gif',
      ':v:'=>'icon_down.gif',
      ':^:'=>'icon_up.gif',
      ':rotate:'=>'icon_rotate.gif',
      ':mrgreen:'=>'icon_mrgreen.gif',
      'o_O'=>'icon_eh.gif',
      ':arrow:'=>'icon_arrow.gif',
      ':?:'=>'icon_question.gif',
      ':!:'=>'icon_exclaim.gif',
      'D:'=>'bigfrown.gif',
      ':bz'=> '115.gif',
      ':ar!'=> 'pirate.gif',
       '8->'=> '105.gif','8-&gt;'=>'105.gif',
       '(*)'=> '79.gif'
      );
      
//      foreach ($BBCode->smileys as $Smiley => $Img) {
//         echo $Smiley, ' ';
//      }
//      die();
   }
	    */
/*
	public function PostController_Render_Before($Sender) {
		if (property_exists($Sender, 'CommentData') && is_object($Sender->CommentData)) {
			RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
//			if (Gdn::Session()->IsValid()) {
//				$Results = &$Sender->CommentData->Result();
//				foreach ($Results as &$Result) {
//					$User = Gdn::UserModel()->GetID($Result->InsertUserID, DATASET_TYPE_ARRAY);
//					$Result->InsertEmail = $User['Email'];
//					$Result->InsertStatus = $User['About'];
//				}
//			}
		}
	}
*/

	/**
	 * Make depth1 categories disabled in the category dropdown on the discussion form.
	public function PostController_AfterCategoryItem_Handler($Sender) {
		$Category = GetValue('Category', $Sender->EventArguments);
		if (is_object($Category) && $Category->Depth == 1 && array_key_exists($Category->CategoryID, $Sender->EventArguments['aCategoryData'])) {
			$Sender->EventArguments['aCategoryData'][$Category->CategoryID] = array('Text' => $Category->Name, 'disabled' => 'disabled');
		} else if (is_object($Category) && array_key_exists($Category->CategoryID, $Sender->EventArguments['aCategoryData'])) {
			$Sender->EventArguments['aCategoryData'][$Category->CategoryID] = str_replace('↳', '', $Sender->EventArguments['aCategoryData'][$Category->CategoryID]);
		}
	}
	 */
   
   /**
    *
    * @param Gdn_Controller $Sender
    * @param type $Args 
   public function SearchController_Render_Before($Sender, $Args) {
      $Sender->AddDefinition('NoJump', 1);
   }
    */
   public function Setup() {
/*
		// They don't want to use their panel, so hide all modules that might show up there to reduce html & query load.
		SaveToConfig('Vanilla.Categories.HideModule', TRUE);
		SaveToConfig('Garden.Modules.ShowGuestModule', FALSE);
		SaveToConfig('Garden.Modules.ShowSignedInModule', FALSE);
		SaveToConfig('Garden.Modules.ShowRecentUserModule', FALSE);
		SaveToConfig('Vanilla.Modules.ShowBookmarkedModule', FALSE);
*/
		return TRUE;
   }
   public function OnDisable() {
      return TRUE;
   }
	
	
}