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
   
echo '<table class="PageNavigation Top"><tr><td>';
if ($this->Discussion->Closed == '0')
   echo $PostReplyButton;   
echo '</td><td>';
echo $this->Pager->ToString('less');
echo '</td></tr></table>';


if ($Session->IsValid()) {
   // Bookmark link
   echo Wrap(Anchor(
      '<span>*</span>',
      '/vanilla/discussion/bookmark/'.$this->Discussion->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($this->SelfUrl),
      'Bookmark' . ($this->Discussion->Bookmarked == '1' ? ' Bookmarked' : ''),
      array('title' => T($this->Discussion->Bookmarked == '1' ? 'Unbookmark' : 'Bookmark'))
   ), 'div', array('class' => 'BookmarkWrapper'));
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
                  if ($Descendant->Depth == 1)
                     echo Gdn_Format::Text($Descendant->Name);
                  else
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
   <?php if ($this->Discussion->CountComments > 1 && $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', 'any')) { ?>
      <div class="Administration">
         <input type="checkbox" name="Toggle" />
      </div>
   <?php } ?>
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

echo '<table class="PageNavigation Bottom"><tr><td>';
if ($this->Discussion->Closed == '0')
   echo $PostReplyButton;
echo '</td><td>';
echo $this->Pager->ToString('more');
echo '</td></tr></table>';

// Write out the comment form
if ($this->Discussion->Closed == '1') {
   ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('This discussion has been closed.'); ?></div>
   </div>
   <?php
} else if ($Session->IsValid() && $Session->CheckPermission('Vanilla.Comments.Add', TRUE, 'Category', $this->Discussion->PermissionCategoryID)) {
  	// Append the user's roles to the class definition
   $CssClass = 'CommentFormTable CommentTable';
   $CssRoles = $Session->User->Roles;
   foreach ($CssRoles as &$RawRole)
      $RawRole = 'role-'.str_replace(' ','_',  strtolower($RawRole));
	if (count($CssRoles))
		$CssClass .= ' '.implode(' ',$CssRoles);
	
	// Identify jailed & banned users
	if (GetValue('InsertBanned', $Session->User) == '1')
		$CssClass .= ' banned';
	else if (GetValue('InsertJailed', $Session->User) == '1')
		$CssClass .= ' jailed';

   ?>
   <div class="FormBanner">Post a Reply</div>
	<table class="<?php echo $CssClass; ?>">
		<tr>
			<td class="Author">
				<?php
				echo Wrap(UserAnchor($Session->User), 'div', array('class' => 'Name'));
				echo Wrap('<span class="Rank"></span>'.UserPhoto($Session->User), 'div', array('class' => 'Photo'));
				if ($Session->User->About != '')
					echo Wrap(Gdn_Format::Text($Session->User->About), 'div', array('class' => 'Status'));

				?>
			</td>
			<td class="Comment">
            <?php echo $this->FetchView('comment', 'post'); ?>
         </td>
      </tr>
   </table>
   <?php
} else if ($Session->IsValid()) { ?>
   <div class="Foot Closed">
      <div class="Note Closed"><?php echo T('Commenting not allowed.'); ?></div>
   </div>
   <?php
}