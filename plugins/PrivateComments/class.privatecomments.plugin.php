<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright Copyright 2011 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
$PluginInfo['PrivateComments'] = array(
   'Name' => 'Private Comments',
   'Description' => 'Any comment made while discussion is in "private" mode will remain invisible to non-privileged users.',
   'Version' => '1.0',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RegisterPermissions' => array('Plugins.PrivateCommments.View')
);

/*
 * Created custom for Headway Themes. I don't approve of this workflow. -Lincoln
 *
 * It's important the admin/privileged users NOT comment while in private mode.
 * @todo Known deficiency: Comment count will not increment for non-privileged user making private comments.
 */
class PrivateCommentsPlugin extends Gdn_Plugin {
   /**
    * Add 'Private' option to new discussion form.
    */
   public function PostController_DiscussionFormOptions_Handler($Sender) {
      if (CheckPermission('Plugins.PrivateCommments.View'))
         $Sender->EventArguments['Options'] .= '<li>'.$Sender->Form->CheckBox('Private', T('Private'), array('value' => '1')).'</li>';
   }
   
   /**
    * Append '[Private Mode]' to discussion titles with privacy enabled.
    */
   public function DiscussionController_BeforeDiscussionTitle_Handler($Sender) {
      if ($Sender->Discussion->Private)
         $Sender->EventArguments['DiscussionName'] .= ' ' . T('[PRIVATE MODE]');
   }
   
   /**
    * Toggle 'Private' property of a discussion.
    */
   public function DiscussionController_Private_Create($Sender, $Args = array()) {
     if (CheckPermission('Plugins.PrivateCommments.View') && isset($Args[0], $Args[1])) {
         if (Gdn::Session()->ValidateTransientKey($Args[1])) {
            $DiscussionModel = new DiscussionModel();
            $DiscussionID = $Args[0];
            if ($Discussion = $DiscussionModel->GetID($DiscussionID)) {
               $Private = ($Discussion->Private) ? 0 : 1; // Reverse it
               $DiscussionModel->Update(array('Private' => $Private), array('DiscussionID' => $DiscussionID));
            }
         }
      }
      // Redirect to the front page
      if ($Sender->DeliveryType() === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', 'discussions');
         Redirect($Target);
      }
   }
   
   /**
    * Exclude private comments from a discussion.
    */
   public function CommentModel_BeforeGet_Handler($Sender) {
      $this->ExcludePrivateComments($Sender);
   }
   public function CommentModel_BeforeGetCount_Handler($Sender) {
      $this->ExcludePrivateComments($Sender);
   }
   public function CommentModel_BeforeGetIDData_Handler($Sender) {
      $this->ExcludePrivateComments($Sender);
   }
   public function CommentModel_BeforeGetNew_Handler($Sender) {
      $this->ExcludePrivateComments($Sender);
   }
   protected function ExcludePrivateComments($Sender) {
      if (!CheckPermission('Plugins.PrivateCommments.View')) {
         // Add SQL condition to CommentModel::Get
         $Sender->SQL
            ->BeginWhereGroup()
            ->Where(array('Private' => 0))
            ->OrWhere(array('Private is null' => ''))
            ->OrWhere(array('InsertUserID' => Gdn::Session()->UserID))
            ->EndWhereGroup();
      }
   }
   
   /**
    * Subtract private comments from counts.
    */
   public function DiscussionModel_AfterAddColumns_Handler($Sender) {
      if (CheckPermission('Plugins.PrivateCommments.View'))
         return;
      
      $Result = &$Sender->EventArguments['Data']->Result();
      foreach($Result as &$Discussion) {
         $Discussion->CountUnreadComments -= $Discussion->PrivateCountComments;
         if ($Discussion->CountUnreadComments < 0)
            $Discussion->CountUnreadComments = 0;
         $Discussion->CountComments -= $Discussion->PrivateCountComments;
      }
   }
   public function DiscussionModel_AfterDiscussionSummaryQuery_Handler($Sender) {
      $Sender->SQL->Select('d.PrivateCountComments');
   }
   
   /**
    * Set 'Private' flag on comments made while discussion is private.
    */
   public function CommentModel_BeforeSaveComment_Handler($Sender) {
      $DiscussionID = GetValue('DiscussionID', $Sender->EventArguments['FormPostValues']);
      $DiscussionModel = new DiscussionModel();
      $Discussion = $DiscussionModel->GetID($DiscussionID);
      if ($Discussion->Private)
         $Sender->EventArguments['FormPostValues']['Private'] = 1;
   }
   
   /**
    * Increment PrivateCommentCount.
    */
   public function CommentModel_AfterSaveComment_Handler($Sender) {
      $CommentModel = new CommentModel();
      $Comment = $CommentModel->GetID($Sender->EventArguments['CommentID']);
      if ($Comment->Private) {
         $Sql = "update ".$Sender->Database->DatabasePrefix."Discussion d
            set PrivateCountComments = PrivateCountComments +1
            where DiscussionID = ".$Comment->DiscussionID;
         $Sender->SQL->Query($Sql);
      }
   }
   
   /**
    * Add Privacy toggle link to discussions.
    */
   public function DiscussionController_CommentOptions_Handler($Sender) {
      if ($Sender->EventArguments['Type'] == 'Discussion' && Gdn::Session()->CheckPermission('Plugins.PrivateCommments.View'))
         $Sender->Options .= ' <span class="MItem">'.Anchor(T($Sender->Discussion->Private == '1' ? 'Make Public' : 'Make Private'), 
            'vanilla/discussion/private/'.$Sender->Discussion->DiscussionID.'/'.
            Gdn::Session()->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'PrivateDiscussion') . '</span> ';
   }
   
   /**
    * Inject a class and some CSS.
    */
   public function DiscussionController_Render_Before($Sender) {
      $Sender->CssClass .= ' PrivateCommentsEnabled';
      $Sender->AddAsset('Head', '<style>.PrivateCommentsEnabled .CommentForm a.WriteButton:after { content: " '.T('[PRIVATE]').'"; }</style>');
   }
   
   /**
    * Add 'Private' columns to Discussion and Comment tables.
    */
   public function Setup() {
      Gdn::Structure()->Table('Discussion')
         ->Column('Private', 'tinyint(1)', TRUE)
         ->Column('PrivateCountComments', 'int', 0)
         ->Set();
      Gdn::Structure()->Table('Comment')
         ->Column('Private', 'tinyint(1)', TRUE)
         ->Set();
   }
}