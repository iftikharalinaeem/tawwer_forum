<?php if (!defined('APPLICATION')) exit();

/**
 * $Object is either a Comment or the original Discussion.
 */
function WriteComment($Object, $Sender, $Session, $CurrentOffset) {
   $Alt = ($CurrentOffset % 2) != 0;

   $Author = UserBuilder($Object, 'Insert');
	$Author->Status = GetValue('InsertStatus', $Object, '');
   $Type = property_exists($Object, 'CommentID') ? 'Comment' : 'Discussion';
	$Sender->EventArguments['Object'] = $Object;
   $Sender->EventArguments['Type'] = $Type;
   $Sender->EventArguments['Author'] = $Author;
   $CssClass = 'Item Comment';
   if ($Type == 'Comment') {
      $Sender->EventArguments['Comment'] = $Object;   
      $Id = 'Comment_'.$Object->CommentID;
      $Permalink = '/discussion/comment/'.$Object->CommentID.'/#Comment_'.$Object->CommentID;
   } else {
      $Sender->EventArguments['Discussion'] = $Object;   
      $CssClass .= ' FirstComment';
      $Id = 'Discussion_'.$Object->DiscussionID;
      $Permalink = '/discussion/'.$Object->DiscussionID.'/'.Gdn_Format::Url($Object->Name).'/p1';
   }
   $Sender->EventArguments['CssClass'] = &$CssClass;
   $Sender->Options = '';
   $CssClass .= $Object->InsertUserID == $Session->UserID ? ' Mine' : '';

   if ($Alt)
      $CssClass .= ' Alt';
   $Alt = !$Alt;
	
	// Append the user's roles to the class definition
	$CssRoles = strtolower(implode(' role-', GetValue('Roles', $Object, array())));
	if ($CssRoles != '')
		$CssClass .= ' role-'.$CssRoles;
	
	// Identify jailed & banned users
	if (GetValue('InsertBanned', $Object) == '1')
		$CssClass .= ' banned';
	else if (GetValue('InsertJailed', $Object) == '1')
		$CssClass .= ' jailed';
	
	if (!property_exists($Sender, 'CanEditComments'))
		$Sender->CanEditComments = $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', 'any');

   $Sender->FireEvent('BeforeCommentDisplay');
?>
<li class="<?php echo $CssClass; ?>" id="<?php echo $Id; ?>">
	<table class="CommentTable">
		<tr>
			<td class="Author">
				<?php
				echo Wrap(UserAnchor($Author), 'div', array('class' => 'Name'));
				echo Wrap('<span class="Rank"></span>'.UserPhoto($Author), 'div', array('class' => 'Photo'));
				if ($Author->Status != '')
					echo Wrap(Gdn_Format::Text($Author->Status), 'div', array('class' => 'Status'));

				$Sender->FireEvent('CommentInfo');
				?>
			</td>
			<td class="Comment">
				<div class="Comment">
					<div class="Meta">
						<?php $Sender->FireEvent('BeforeCommentMeta'); ?>
						<span class="DateCreated">
							<?php echo Anchor(Gdn_Format::Date($Object->DateInserted), $Permalink, 'Permalink', array('name' => 'Item_'.($CurrentOffset+1), 'rel' => 'nofollow')); ?>
						</span>
						<?php
						WriteOptionList($Object, $Sender, $Session);
						if ($Type == 'Comment' && $Sender->CanEditComments) {
							if (!property_exists($Sender, 'CheckedComments'))
								$Sender->CheckedComments = $Session->GetAttribute('CheckedComments', array());
								
							$ItemSelected = InSubArray($Id, $Sender->CheckedComments);
							echo '<div class="Administration"><input type="checkbox" name="'.$Type.'ID[]" value="'.$Id.'"'.($ItemSelected?' checked="checked"':'').' /></div>';
						}
						?>
						<?php $Sender->FireEvent('AfterCommentMeta'); ?>
					</div>
					<div class="Message">
						<?php
							$Sender->FireEvent('BeforeCommentBody');
							$Object->FormatBody = Gdn_Format::To($Object->Body, $Object->Format);
							$Sender->FireEvent('AfterCommentFormat');
							$Object = $Sender->EventArguments['Object'];
							echo $Object->FormatBody;
						?>
					</div>
					<?php $Sender->FireEvent('AfterCommentBody'); ?>
				</div>
			</td>
		</tr>
	</table>
</li>
<?php
	$Sender->FireEvent('AfterComment');
}

// No edits for PA theme
function WriteOptionList($Object, $Sender, $Session) {
   $EditContentTimeout = C('Garden.EditContentTimeout', -1);
	$CanEdit = $EditContentTimeout == -1 || strtotime($Object->DateInserted) + $EditContentTimeout > time();
	$TimeLeft = '';
	if ($CanEdit && $EditContentTimeout > 0) {
		$TimeLeft = strtotime($Object->DateInserted) + $EditContentTimeout - time();
		$TimeLeft = $TimeLeft > 0 ? ' ('.Gdn_Format::Seconds($TimeLeft).')' : '';
	}

   $Sender->Options = '';
	$CategoryID = GetValue('CategoryID', $Object);
	if(!$CategoryID && property_exists($Sender, 'Discussion'))
		$CategoryID = GetValue('CategoryID', $Sender->Discussion);
   $PermissionCategoryID = GetValue('PermissionCategoryID', $Object, GetValue('PermissionCategoryID', $Sender->Discussion));
		
   // Show discussion options if this is the discussion / first comment
   if ($Sender->EventArguments['Type'] == 'Discussion') {
      // Can the user edit the discussion?
      if (($CanEdit && $Session->UserID == $Object->InsertUserID) || $Session->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editdiscussion/'.$Object->DiscussionID, 'EditDiscussion').$TimeLeft.'</span>';
         
      // Can the user announce?
      if ($Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Announce == '1' ? 'Unannounce' : 'Announce'), 'vanilla/discussion/announce/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'AnnounceDiscussion') . '</span>';

      // Can the user sink?
      if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Sink == '1' ? 'Unsink' : 'Sink'), 'vanilla/discussion/sink/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'SinkDiscussion') . '</span>';

      // Can the user close?
      if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T($Sender->Discussion->Closed == '1' ? 'Reopen' : 'Close'), 'vanilla/discussion/close/'.$Object->DiscussionID.'/'.$Session->TransientKey().'?Target='.urlencode($Sender->SelfUrl), 'CloseDiscussion') . '</span>';
      
      // Can the user delete?
      if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete Discussion'), 'vanilla/discussion/delete/'.$Object->DiscussionID.'/'.$Session->TransientKey(), 'DeleteDiscussion') . '</span>';
   } else {
      // And if this is just another comment in the discussion ...
      
      // Can the user edit the comment?
      if (($CanEdit && $Session->UserID == $Object->InsertUserID) || $Session->CheckPermission('Vanilla.Comments.Edit', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Edit'), '/vanilla/post/editcomment/'.$Object->CommentID, 'EditComment').$TimeLeft.'</span>';

      // Can the user delete the comment?
      if ($Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $PermissionCategoryID))
         $Sender->Options .= '<span>'.Anchor(T('Delete'), 'vanilla/discussion/deletecomment/'.$Object->CommentID.'/'.$Session->TransientKey().'/?Target='.urlencode($Sender->SelfUrl), 'DeleteComment') . '</span>';
   }
   
   // Allow plugins to add options
   $Sender->FireEvent('CommentOptions');
   echo $Sender->Options;
}