<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$DiscussionName = Gdn_Format::Text($this->Discussion->Name);
if ($DiscussionName == '')
   $DiscussionName = T('Blank Discussion Topic');

if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));
   
$PostReplyButton = Anchor('Post a Reply', '#Form_Body', 'BigButton');
if (!$Session->IsValid())
   $PostReplyButton = Anchor('Post a Reply', Gdn::Authenticator()->SignInUrl($this->SelfUrl.(strpos($this->SelfUrl, '?') ? '&' : '?').'post#Form_Body'), 'BigButton'.(SignInPopup() ? ' SignInPopup' : ''));
   
echo $this->Pager->ToString('less');

echo $PostReplyButton;   

if ($Session->IsValid()) {
   // Bookmark link
   echo Anchor(
      '<span>*</span>',
      '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
      'Bookmark' . ($this->Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => T($this->Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
   );
}
?>
<div class="Tabs HeadingTabs DiscussionTabs">
   <h1><?php echo $DiscussionName; ?></h1>
   <div class="SubTab">
      <span class="BreadCrumb FirstCrumb"> &rarr; </span><?php
      try {
         $CategoryModel = new CategoryModel();
         $DescendantData = $CategoryModel->GetDescendantsByCode($this->Discussion->CategoryUrlCode);
         if ($DescendantData) {
            foreach ($DescendantData->Result() as $Descendant) {
               // Ignore the root node
               if ($Descendant->CategoryID > 0) {
                  echo Anchor(Gdn_Format::Text($Descendant->Name), '/categories/'.$Descendant->UrlCode);
                  echo '<span class="BreadCrumb"> &rarr; </span>';
               }
            }
         }
      } catch (Exception $ex) {
         // Fail silently
      }
      echo Anchor($this->Discussion->Category, 'categories/'.$this->Discussion->CategoryUrlCode);
      echo '<span class="BreadCrumb"> &rarr; </span>';
      echo $DiscussionName;
      ?>
   </div>
</div>
<?php
   $this->FireEvent('BeforeDiscussion');
   echo $this->RenderAsset('DiscussionBefore');
?>
<ul class="MessageList Discussion">
   <?php echo $this->FetchView('comments'); ?>
</ul>
<?php

if($this->Pager->LastPage()) {
   $this->AddDefinition('DiscussionID', $this->Data['Discussion']->DiscussionID);
   $LastCommentID = $this->AddDefinition('LastCommentID');
   if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
      $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   $this->AddDefinition('Vanilla_Comments_AutoRefresh', Gdn::Config('Vanilla.Comments.AutoRefresh', 0));
}

echo $this->Pager->ToString('more');

// Write out the comment form
if ($this->Discussion->Closed == '1') {
   ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
      <?php echo Anchor(T('&larr; All Discussions'), 'discussions', 'TabLink'); ?>
   </div>
   <?php
} else if ($Session->IsValid() && $Session->CheckPermission('Vanilla.Comments.Add', TRUE, 'Category', $this->Discussion->PermissionCategoryID)) {
   echo $this->FetchView('comment', 'post');
} else if ($Session->IsValid()) { ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('Commenting not allowed.'); ?></div>
      <?php echo Anchor(T('&larr; All Discussions'), 'discussions', 'TabLink'); ?>
   </div>
   <?php
} else {
   ?>
   <div class="Foot">
      <?php echo $PostReplyButton; ?>
   </div>
   <?php 
}
