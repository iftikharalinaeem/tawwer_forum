<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!function_exists('WriteComment'))
   include($this->FetchViewLocation('helper_functions', 'discussion'));
?>
<div class="DataHeading">
   <h1><?php
      $DiscussionName = Gdn_Format::Text($this->Discussion->Name);
      if ($DiscussionName == '')
         $DiscussionName = T('Blank Discussion Topic');

      echo $DiscussionName;
   ?></h1>
   <?php
   if ($Session->IsValid()) {
      echo '<div class="Options">';

      // Bookmark link
      echo Anchor(
         '<span>*</span>',
         '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
         'Bookmark' . ($this->Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
         array('title' => T($this->Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
      );

      echo '</div>';
   }
?>
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
      To add a comment: <?php
      try {
         $FB = Gdn::PluginManager()->GetPluginInstance('FacebookPlugin');
         $ImgSrc = Asset('/plugins/Facebook/design/facebook-login.png');
         $ImgAlt = T('Login with Facebook');
         $SigninHref = $FB->AuthorizeUri();
         $PopupSigninHref = $FB->AuthorizeUri('display=popup');
         echo "<a id=\"FacebookAuth\" href=\"$SigninHref\" class=\"PopupWindow\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" style=\"vertical-align: middle;\" /></a>";
      } catch(Exception $ex) {
      }
      ?> 
   </div>
   <?php 
}
