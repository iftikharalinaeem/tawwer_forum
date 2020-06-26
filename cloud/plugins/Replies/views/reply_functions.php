<?php

if (!function_exists('WriteReplies')):
   
function writeReplies($comment) {
   $replies = getValue('Replies', $comment, []);
   
   $hidden = count($replies) == 0 ? ' Hidden' : '';
   
   echo '<div id="'.repliesElementID($comment).'" class="DataList DataList-Replies'.$hidden.'">';
   
   // Write out all of the replies.
   foreach ($replies as $reply) {
      writeReply($reply);
   }
   
   writeReplyForm($comment);
   
   echo '</div>';
}

endif;

if (!function_exists('WriteReply')):
   
function writeReply($reply) {
   $user = Gdn::userModel()->getID($reply['InsertUserID']);
      
   echo '<div id="Reply_'.$reply['ReplyID'].'" class="Item Item-Reply HasPhoto">';
   
   // Reply options.
   echo '<div class="Options">';
   writeReplyOptions($reply);
   echo '</div>';

   // Author photo stuff.
   echo userPhoto($user);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo userAnchor($user, 'Username');
      echo ' '.wrap(Gdn_Format::date($reply['DateInserted'], 'html'), 'span', ['class' => 'Meta DateInserted']);
   echo '</div>';

   echo '<div class="Reply-Body Message">';
      echo Gdn_Format::text($reply['Body']);
   echo '</div>';

   echo '</div>';

   echo '</div>';
}

endif;

if (!function_exists('WriteReplyButton')):

function writeReplyButton($comment) {
   $iD = replyRecordID($comment);
   
   $path = '/post/reply?commentid='.urlencode($iD);
   
   echo anchor(sprite('SpReply', 'ReactSprite').' '.t('Reply'), $path, 'ReactButton Reply Visible');
}

endif;

if (!function_exists('WriteReplyEdit')):

function writeReplyEdit($reply) {
   $form = Gdn::controller()->ReplyForm;
   $form->IDPrefix = 'EditReply_';
   
   $user = Gdn::userModel()->getID($reply['InsertUserID']);
      
   echo '<div id="Reply_'.$reply['ReplyID'].'" class="Item Item-Reply Item-ReplyEdit HasPhoto">';
   echo $form->open();

   // Author photo stuff.
   echo userPhoto($user);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo userAnchor($user, 'Username');
      echo ' '.wrap(Gdn_Format::date($reply['DateInserted'], 'html'), 'span', ['class' => 'Meta DateInserted']);
   echo '</div>';

   echo '<div class="Reply-Body Message">';
   
   echo $form->errors();
   echo $form->textBox('Body', ['Multiline' => TRUE, 'Wrap' => TRUE, 'rows' => 4]);

   echo '<div class="Buttons">';
   echo $form->button('Cancel', ['class' => 'Button Cancel', 'tabindex' => '-1']);
   echo ' ';
   echo $form->button('Save', ['class' => 'Button Primary']);
   echo '</div>';

   echo '</div>';

   echo '</div>';

   echo $form->close();
   echo '</div>';
}
endif;

if (!function_exists('WriteReplyForm')):
   
function writeReplyForm($comment) {
   if (!Gdn::session()->checkPermission('Vanilla.Replies.Add'))
      return;
   
   echo '<div class="Item Item-ReplyForm HasPhoto">';
   
   // Author photo stuff.
   echo userPhoto(Gdn::session()->User);
   
   
   echo '<div class="ItemContent ItemContent-Reply">';
   
      echo '<div class="Reply-Body">';
      
      $form = Gdn::controller()->ReplyForm;
         
      // Write the regular link.
      echo anchor(t('Reply here...'), '#', 'FormPlaceholder InputBox', ['style' => $form->errorCount() ? 'display: none' : '']);
      
      $iD = getValue('CommentID', $comment);
      if (!$iD)
         $iD = -getValue('DiscussionID', $comment);
      
      // Write the form.
      $form->IDPrefix = 'Reply_';
      echo $form->open(['action' => url('/post/reply?commentid='.$iD), 'class' => $form->errorCount() ? '' : 'Hidden']);
      echo $form->errors();
      echo $form->textBox('Body', ['Multiline' => TRUE, 'Wrap' => TRUE, 'rows' => 4]);
      
      echo '<div class="Buttons">';
      echo $form->button('Cancel', ['type' => 'button', 'class' => 'Button Cancel', 'tabindex' => '-1']);
      echo ' ';
      echo $form->button('Reply', ['class' => 'Button Primary']);
      echo '</div>';
      
      echo $form->close();
      
      echo '</div>';
      
      echo '</div>';
   
   echo '</div>';
}
   
endif;


if (!function_exists('WriteReplyOptions')):
   
function writeReplyOptions($reply) {
	$options = getReplyOptions($reply);
	if (empty($options))
		return;

   echo '<span class="ToggleFlyout OptionsMenu">';
      echo '<span class="OptionsTitle" title="'.t('Options').'">'.t('Options').'</span>';
      
      echo '<ul class="Flyout MenuItems">';
      foreach ($options as $code => $option):
         echo wrap(anchor($option['Label'], $option['Url'], getValue('Class', $option, 'Option-'.$code)), 'li');
      endforeach;
      echo '</ul>';
   echo '</span>';
}

endif;