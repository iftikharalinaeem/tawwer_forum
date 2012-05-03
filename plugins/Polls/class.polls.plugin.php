<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Polls'] = array(
   'Name' => 'Polls',
   'Description' => "Allow users to create and vote on polls.",
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca'
);

class PollsPlugin extends Gdn_Plugin {
   /// Methods ///
   
//   private function AddJs($Sender) {
//      $Sender->AddJsFile('jquery-ui-1.8.17.custom.min.js');
//      $Sender->AddJsFile('reactions.js', 'plugins/Reactions');
//   }
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   /** 
    * Add the poll form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($Sender) {
      $Forms = $Sender->Data('Forms');
      $Forms[] = array('Name' => 'Poll', 'Label' => Sprite('SpNewPoll').T('New Poll'), 'Url' => 'post/poll');
		$Sender->SetData('Forms', $Forms);
   }
   
   /** 
    * Create the new poll method on post controller.
    */
   public function PostController_Poll_Create($Sender) {
      // Override CategoryID if categories are disabled
      $Sender->CategoryID = GetValue(0, $Sender->RequestArgs);
      $UseCategories = $Sender->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      if (!$UseCategories) 
         $Sender->CategoryID = 0;

      $Sender->Category = CategoryModel::Categories($Sender->CategoryID);
      if (!is_object($Sender->Category))
         $Sender->Category = NULL;
      
      if ($UseCategories)
			$CategoryData = CategoryModel::Categories();

      // Check permission 
      $Sender->Permission('Vanilla.Discussions.Add');
      
      // Set the model on the form
      $Sender->Form->SetModel($Sender->DiscussionModel);
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         if ($Sender->Category !== NULL)
            $Sender->Form->SetData(array('CategoryID' => $Sender->Category->CategoryID));
      } else { // Form was submitted
         // Save as a draft?
         $FormValues = $this->Form->FormValues();
         $FormValues = $this->DiscussionModel->FilterForm($FormValues);
         $Sender->DeliveryType(GetIncomingValue('DeliveryType', $this->_DeliveryType));
         if (!is_object($Sender->Category) && isset($FormValues['CategoryID']))
            $Sender->Category = $CategoryData[$FormValues['CategoryID']];

         if (is_object($Sender->Category)) {
            // Check category permissions.
            if ($Sender->Form->GetFormValue('Announce', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $Sender->Category->PermissionCategoryID))
               $Sender->Form->AddError('You do not have permission to announce in this category', 'Announce');

            if ($Sender->Form->GetFormValue('Close', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $Sender->Category->PermissionCategoryID))
               $Sender->Form->AddError('You do not have permission to close in this category', 'Close');

            if ($Sender->Form->GetFormValue('Sink', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $Sender->Category->PermissionCategoryID))
               $Sender->Form->AddError('You do not have permission to sink in this category', 'Sink');

            if (!$Session->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', $Sender->Category->PermissionCategoryID))
               $Sender->Form->AddError('You do not have permission to start discussions in this category', 'CategoryID');
         }

         // Make sure that the title will not be invisible after rendering
         $Name = trim($Sender->Form->GetFormValue('Name', ''));
         if ($Name != '' && Gdn_Format::Text($Name) == '')
            $Sender->Form->AddError(T('You have entered an invalid discussion title'), 'Name');
         else {
            // Trim the name.
            $FormValues['Name'] = $Name;
            $Sender->Form->SetFormValue('Name', $Name);
         }

         if ($Sender->Form->ErrorCount() == 0) {
            $DiscussionID = $Sender->DiscussionModel->Save($FormValues, $Sender->CommentModel);
            $Sender->Form->SetValidationResults($Sender->DiscussionModel->ValidationResults());
            if ($DiscussionID == SPAM) {
               $Sender->StatusMessage = T('Your post has been flagged for moderation.');
               $Sender->Render('Spam');
               return;
            }
         }
         if ($Sender->Form->ErrorCount() > 0) {
            // Return the form errors
            $Sender->ErrorMessage($Sender->Form->Errors());
         } else if ($DiscussionID > 0) {
            // Make sure that the ajax request form knows about the newly created discussion or draft id
            $Sender->SetJson('DiscussionID', $DiscussionID);
            
            // Redirect to the new discussion
            $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
            $Sender->EventArguments['Discussion'] = $Discussion;
            $Sender->FireEvent('AfterDiscussionSave');

            if ($Sender->_DeliveryType == DELIVERY_TYPE_ALL) {
               Redirect(DiscussionUrl($Discussion));
            } else {
               $Sender->RedirectUrl = DiscussionUrl($Discussion, '', TRUE);
            }
         }
      }
      
      // Set up the page and render
      $Sender->FireEvent('BeforeDiscussionRender');
		$Sender->SetData('Breadcrumbs', array(array('Name' => $Sender->Data('Title'), 'Url' => '/post/poll')));
      $Sender->Title(T('New Poll'));
      $Sender->AddJsFile('jquery.duplicate.js');
      $Sender->Render('add', '', 'plugins/Polls');
   }
   
   // var $Answers = array('Starbucks', 'Second Cup', 'Tim Hortons', 'Lilly &amp; Ollie', 'Nicaragua', 'Juan Valdez', 'Home', 'No coffee for me!', 'DOWN WITH COMMUNISM!', 'Other');
   // var $Answers = array('Starbucks', 'Second Cup', 'Tim Hortons', 'Other', "Don't care!");
   var $Answers = array(
       'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
       'Proin ac congue nisl',
       'Maecenas id lectus vitae turpis ullamcorper rhoncus vel eu diam',
       'Vivamus consequat magna luctus lacus pharetra eget porttitor quam dictum',
       'Pellentesque venenatis vulputate lobortis',
       'Donec vel mi ut ante sollicitudin blandit',
       'Mauris eget velit eu nisi scelerisque pulvinar quis eget diam. Morbi et sapien eu sapien rhoncus porta',
       'Duis eu mi mauris, in bibendum erat'
   );
   
   /** 
    * Display the poll on the discussion. 
    * @param type $Sender 
    */
   public function DiscussionController_BeforeDiscussionBody_Handler($Sender) {
      echo Gdn_Theme::Module('PollModule');
   }
   
   /** 
    * Display a user's vote in their author info. 
    * @param type $Sender 
    */
   public function DiscussionController_BeforeCommentBody_Handler($Sender) {
      $Sender->SetData('Answers', $this->Answers);
      $Count = count($this->Answers);
      $Vote = rand(0, $Count+1);
      $CssClass = 'PollColor PollColor'.($Vote+1);
      if ($Vote <= $Count)
         echo '<div class="PollVote"><span class="'.$CssClass.'"></span> '.GetValue($Vote, $this->Answers).'</div>';
         // echo '<div class="PollVote"><span class="'.$CssClass.'"></span> '.SliceString(GetValue($Vote, $this->Answers), 30).'</div>';
   }
   
   public function DiscussionController_Render_Before($Sender) {
      $Sender->SetData('Answers', $this->Answers);
      $Sender->AddCssFile('plugins/Polls/design/style.css');
   }
}