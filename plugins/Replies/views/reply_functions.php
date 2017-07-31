<?php

if (!function_exists('WriteReplies')):
   
function WriteReplies($comment) {
   $replies = GetValue('Replies', $comment, []);
   
   $hidden = count($replies) == 0 ? ' Hidden' : '';
   
   echo '<div id="'.RepliesElementID($comment).'" class="DataList DataList-Replies'.$hidden.'">';
   
   // Write out all of the replies.
   foreach ($replies as $reply) {
      WriteReply($reply);
   }
   
   WriteReplyForm($comment);
   
   echo '</div>';
}

endif;

if (!function_exists('WriteReply')):
   
function WriteReply($reply) {
   $user = Gdn::UserModel()->GetID($reply['InsertUserID']);
      
   echo '<div id="Reply_'.$reply['ReplyID'].'" class="Item Item-Reply HasPhoto">';
   
   // Reply options.
   echo '<div class="Options">';
   WriteReplyOptions($reply);
   echo '</div>';

   // Author photo stuff.
   echo UserPhoto($user);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo UserAnchor($user, 'Username');
      echo ' '.Wrap(Gdn_Format::Date($reply['DateInserted'], 'html'), 'span', ['class' => 'Meta DateInserted']);
   echo '</div>';

   echo '<div class="Reply-Body Message">';
      echo Gdn_Format::Text($reply['Body']);
   echo '</div>';

   echo '</div>';

   echo '</div>';
}

endif;

if (!function_exists('WriteReplyButton')):

function WriteReplyButton($comment) {
   $iD = ReplyRecordID($comment);
   
   $path = '/post/reply?commentid='.urlencode($iD);
   
   echo Anchor(Sprite('SpReply', 'ReactSprite').' '.T('Reply'), $path, 'ReactButton Reply Visible');
}

endif;

if (!function_exists('WriteReplyEdit')):

function WriteReplyEdit($reply) {
   $form = Gdn::Controller()->ReplyForm;
   $form->IDPrefix = 'EditReply_';
   
   $user = Gdn::UserModel()->GetID($reply['InsertUserID']);
      
   echo '<div id="Reply_'.$reply['ReplyID'].'" class="Item Item-Reply Item-ReplyEdit HasPhoto">';
   echo $form->Open();

   // Author photo stuff.
   echo UserPhoto($user);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo UserAnchor($user, 'Username');
      echo ' '.Wrap(Gdn_Format::Date($reply['DateInserted'], 'html'), 'span', ['class' => 'Meta DateInserted']);
   echo '</div>';

   echo '<div class="Reply-Body Message">';
   
   echo $form->Errors();
   echo $form->TextBox('Body', ['Multiline' => TRUE, 'Wrap' => TRUE, 'rows' => 4]);

   echo '<div class="Buttons">';
   echo $form->Button('Cancel', ['class' => 'Button Cancel', 'tabindex' => '-1']);
   echo ' ';
   echo $form->Button('Save', ['class' => 'Button Primary']);
   echo '</div>';

   echo '</div>';

   echo '</div>';

   echo $form->Close();
   echo '</div>';
}
endif;

if (!function_exists('WriteReplyForm')):
   
function WriteReplyForm($comment) {
   if (!Gdn::Session()->CheckPermission('Vanilla.Replies.Add'))
      return;
   
   echo '<div class="Item Item-ReplyForm HasPhoto">';
   
   // Author photo stuff.
   echo UserPhoto(Gdn::Session()->User);
   
   
   echo '<div class="ItemContent ItemContent-Reply">';
   
      echo '<div class="Reply-Body">';
      
      $form = Gdn::Controller()->ReplyForm;
         
      // Write the regular link.
      echo Anchor(T('Reply here...'), '#', 'FormPlaceholder InputBox', ['style' => $form->ErrorCount() ? 'display: none' : '']);
      
      $iD = GetValue('CommentID', $comment);
      if (!$iD)
         $iD = -GetValue('DiscussionID', $comment);
      
      // Write the form.
      $form->IDPrefix = 'Reply_';
      echo $form->Open(['action' => Url('/post/reply?commentid='.$iD), 'class' => $form->ErrorCount() ? '' : 'Hidden']);
      echo $form->Errors();
      echo $form->TextBox('Body', ['Multiline' => TRUE, 'Wrap' => TRUE, 'rows' => 4]);
      
      echo '<div class="Buttons">';
      echo $form->Button('Cancel', ['type' => 'button', 'class' => 'Button Cancel', 'tabindex' => '-1']);
      echo ' ';
      echo $form->Button('Reply', ['class' => 'Button Primary']);
      echo '</div>';
      
      echo $form->Close();
      
      echo '</div>';
      
      echo '</div>';
   
   echo '</div>';
}
   
endif;


if (!function_exists('WriteReplyOptions')):
   
function WriteReplyOptions($reply) {
	$options = GetReplyOptions($reply);
	if (empty($options))
		return;

   echo '<span class="ToggleFlyout OptionsMenu">';
      echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
      
      echo '<ul class="Flyout MenuItems">';
      foreach ($options as $code => $option):
         echo Wrap(Anchor($option['Label'], $option['Url'], GetValue('Class', $option, 'Option-'.$code)), 'li');
      endforeach;
      echo '</ul>';
   echo '</span>';
}

endif;