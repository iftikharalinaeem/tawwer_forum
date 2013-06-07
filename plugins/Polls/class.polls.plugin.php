<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Polls'] = array(
   'Name' => 'Polls',
   'Description' => "Allow users to create and vote on polls.",
   'Version' => '1.0.5',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'MobileFriendly' => TRUE,
   'RegisterPermissions' => array('Plugins.Polls.Add' => 'Garden.Profiles.Edit')
);

class PollsPlugin extends Gdn_Plugin {
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   /** 
    * Add the "new poll" button after the new discussion button. 
    */
   public function Base_BeforeNewDiscussionButton_Handler($Sender) {
      $NewDiscussionModule = &$Sender->EventArguments['NewDiscussionModule'];
      // Polls is currently incompatible with pre-moderation
      $DisablePolls = (CheckRestriction('Vanilla.Approval.Require') && !GetValue('Verified', Gdn::Session()->User));
      if (Gdn::Session()->CheckPermission('Plugins.Polls.Add') && !$DisablePolls) {
         $UrlCode = GetValue('UrlCode', GetValue('Category', $Sender->Data), '');
         $NewDiscussionModule->AddButton(T('New Poll'), '/post/poll/'.$UrlCode);
      }
   }
   
   /** 
    * Display a user's vote in their author info. 
    * @param type $Sender 
    */
   public function Base_BeforeCommentBody_Handler($Sender) {
      $Comment = GetValue('Comment', $Sender->EventArguments);
      $PollVote = GetValue('PollVote', $Comment);
      if ($PollVote) {
         echo '<div class="PollVote">';
         // Use the sort as the color indicator (it should match up)
         echo '<span class="PollColor PollColor'.GetValue('Sort', $PollVote).'"></span>';
         echo '<span class="PollVoteAnswer">'.Gdn_Format::To(GetValue('Body', $PollVote), GetValue('Format', $PollVote)).'</span>';
         echo '</div>';
      }
   }

   /** 
    * Allows users to vote on a poll. Redirects them back to poll discussion, or 
    * returns the module html if ajax request.
    */
   public function DiscussionController_PollVote_Create($Sender) {
      $Session = Gdn::Session();
      $Form = new Gdn_Form();
      $PollModel = new PollModel();
      $PollOptionModel = new Gdn_Model('PollOption');
      $PollVoteModel = new Gdn_Model('PollVote');

      // Get values from the form
      $PollID = $Form->GetFormValue('PollID', 0);
      $PollOptionID = $Form->GetFormValue('PollOptionID', 0);
      $PollOption = $PollOptionModel->GetID($PollOptionID);
      $VotedForPollOptionID = 0;
      // If this is a valid form postback, poll, poll option, and user session, record the vote.
      if ($Form->AuthenticatedPostback() && $PollOption && $Session->IsValid())
         $VotedForPollOptionID = $PollModel->Vote($PollOptionID);

      if ($VotedForPollOptionID == 0)
         $Sender->InformMessage(T("You didn't select an option to vote for!"));

      // What should we return?
      $Return = '/';
      if ($PollID > 0) {
         $Poll = $PollModel->GetID($PollID);
         $Discussion = $Sender->DiscussionModel->GetID(GetValue('DiscussionID', $Poll));
         if ($Discussion)
            $Return = DiscussionUrl($Discussion);
      }
      
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
         Redirect($Return);
      
      // Otherwise get the poll html & return it.
      $PollModule = new PollModule();
      $Sender->SetData('PollID', $PollID);
      $Sender->SetJson('PollHtml', $PollModule->ToString());
      $Sender->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /** 
    * Load comment votes on discussion.
    * @param type $Sender 
    */
   public function DiscussionController_Render_Before($Sender) {
      $this->_LoadVotes($Sender);
   }

   /** 
    * Display the Poll label on the discussion list.
    */
   public function Base_BeforeDiscussionMeta_Handler($Sender) {
      $Discussion = $Sender->EventArguments['Discussion'];
      
      if (strcasecmp(GetValue('Type', $Discussion), 'Poll') == 0)
         echo Tag($Discussion, 'Type', 'Poll');
   }
   
   /** 
    * Add a css class to discussions in the discussion list if they have polls attached. 
    */
   public function DiscussionsController_Render_Before($Sender) {
      $this->_AddCss($Sender);
   }
   public function CategoriesController_Render_Before($Sender) {
      $this->_AddCss($Sender);
   }
   
   protected function _AddCss($Sender) {
      
      $Discussions = &$Sender->Data('Discussions');
      if ($Discussions) {
         foreach ($Discussions as &$Row) {
            if (strtolower(GetValue('Type', $Row)) == 'poll')
               SetValue('_CssClass', $Row, trim(GetValue('_CssClass', $Row).' ItemPoll'));
         }         
      }
   }
   
   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('polls.css', 'plugins/Polls');
   }
   /** 
    * Add the poll form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($Sender) {
      $Forms = $Sender->Data('Forms');
      $Forms[] = array('Name' => 'Poll', 'Label' => Sprite('SpPoll').T('New Poll'), 'Url' => 'post/poll');
		$Sender->SetData('Forms', $Forms);
   }
   
   /** 
    * Create the new poll method on post controller.
    */
   public function PostController_Poll_Create($Sender) {
      $PollModel = new PollModel();
      $UseCategories = $Sender->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      $CategoryUrlCode = GetValue(0, $Sender->RequestArgs);
      $Category = FALSE;
      if ($CategoryUrlCode != '') {
         $CategoryModel = new CategoryModel();
         $Category = $CategoryModel->GetByCode($CategoryUrlCode);
         $Sender->CategoryID = $Category->CategoryID;
      }
      if ($Category && $UseCategories)
         $Sender->Category = (object)$Category;
      else {
         $Sender->CategoryID = 0;
         $Sender->Category = NULL;
      }      
      
      if ($UseCategories)
			$CategoryData = CategoryModel::Categories();

      // Check permission 
      $Sender->Permission('Vanilla.Discussions.Add');
      $Sender->Permission('Plugins.Polls.Add');
      
      // Polls are not compatible with pre-moderation
      if (CheckRestriction('Vanilla.Approval.Require') && !GetValue('Verified', Gdn::Session()->User))
         throw PermissionException();
      
      // Set the model on the form
      $Sender->Form->SetModel($PollModel);
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         if ($Sender->Category !== NULL)
            $Sender->Form->SetData(array('CategoryID' => $Sender->Category->CategoryID));
      } else { // Form was submitted
         $FormValues = $Sender->Form->FormValues();
         $DiscussionID = $PollModel->Save($FormValues, $Sender->CommentModel);
         $Sender->Form->SetValidationResults($PollModel->ValidationResults());
         if ($Sender->Form->ErrorCount() == 0) {
            $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);            
            Redirect(DiscussionUrl($Discussion));
         }
      }
      
      // Set up the page and render
      $Sender->Title(T('New Poll'));
		$Sender->SetData('Breadcrumbs', array(array('Name' => $Sender->Data('Title'), 'Url' => '/post/poll')));
      $Sender->SetData('_AnonymousPolls', C('Plugins.Polls.AnonymousPolls'));
      $Sender->AddJsFile('jquery.duplicate.js');
      $this->_AddCss($Sender);
      $Sender->Render('add', '', 'plugins/Polls');
   }
   
   public function PromotedContentModule_AfterBody_Handler($Sender, $Args) {
      $Type = GetValueR('Content.Type', $Sender->EventArgs);
      
      if (strcasecmp($Type, 'poll') === 0 && strlen(GetValueR('Content.Body', $Sender->EventArgs)) === 0) {
         echo ' '.Anchor(T('Click here to vote.'), $Sender->EventArgs['ContentUrl']).' ';
         
      }
   }
   
   /** 
    * Display the poll on the discussion. 
    * @param type $Sender 
    */
   public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
      $Discussion = $Sender->Data('Discussion');
      if (strtolower(GetValue('Type', $Discussion)) == 'poll')
         echo Gdn_Theme::Module('PollModule');
   }
   
   /** 
    * Load user votes data based on the discussion in the controller data.
    * @param type $Sender
    * @return type 
    */
   private function _LoadVotes($Sender) {
      // Does this discussion have an associated poll?
      $Discussion = $Sender->Data('Discussion');
      if (!$Discussion)
         $Discussion = GetValue('Discussion', $Sender->EventArguments);
      if (!$Discussion)
         $Discussion = GetValue('Discussion', $Sender);
      
      if (strtolower(GetValue('Type', $Discussion)) == 'poll') {
         // Load css/js files
         $Sender->AddJsFile('polls.js', 'plugins/Polls');

         // Load the poll based on the discussion id.
         $PollModel = new PollModel();
         $Poll = $PollModel->GetByDiscussionID(GetValue('DiscussionID', $Discussion));
         if (!$Poll)
            return;
         
         // Don't get user votes if this poll is anonymous.
         if (GetValue('Anonymous', $Poll) || C('Plugins.Polls.AnonymousPolls'))
            return;
         
         // Look at all of the users in the comments, and load their associated 
         // poll vote for displaying on their comments.
         $Comments = $Sender->Data('Comments');
         if ($Comments) {
            // Grab all of the user fields that need to be joined.
            $UserIDs = array();
            foreach ($Comments as $Row) {
               $UserIDs[] = GetValue('InsertUserID', $Row);
            }

            // Get the user votes.
            $Votes = $PollModel->GetVotesByUserID($Poll->PollID, $UserIDs);

            // Place the user votes on the comment data.
            foreach ($Comments as &$Row) {
               $UserID = GetValue('InsertUserID', $Row);
               SetValue('PollVote', $Row, GetValue($UserID, $Votes));
            }
         }
      }
   }   
}