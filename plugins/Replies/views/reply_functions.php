<?php

if (!function_exists('WriteReplies')):
   
function WriteReplies($Comment) {
   $Replies = GetValue('Replies', $Comment, array());
   
   $Hidden = count($Replies) == 0 ? ' Hidden' : '';
   
   echo '<div id="'.RepliesElementID($Comment).'" class="DataList DataList-Replies'.$Hidden.'">';
   
   // Write out all of the replies.
   foreach ($Replies as $Reply) {
      WriteReply($Reply);
   }
   
   WriteReplyForm($Comment);
   
   echo '</div>';
}

endif;

if (!function_exists('WriteReply')):
   
function WriteReply($Reply) {
   $User = Gdn::UserModel()->GetID($Reply['InsertUserID']);
      
   echo '<div id="Reply_'.$Reply['ReplyID'].'" class="Item Item-Reply HasPhoto">';
   
   // Reply options.
   echo '<div class="Options">';
   WriteReplyOptions($Reply);
   echo '</div>';

   // Author photo stuff.
   echo UserPhoto($User);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo UserAnchor($User, 'Username');
      echo ' '.Wrap(Gdn_Format::Date($Reply['DateInserted'], 'html'), 'span', array('class' => 'Meta DateInserted'));
   echo '</div>';

   echo '<div class="Reply-Body Message">';
      echo Gdn_Format::Text($Reply['Body']);
   echo '</div>';

   echo '</div>';

   echo '</div>';
}

endif;

if (!function_exists('WriteReplyButton')):

function WriteReplyButton($Comment) {
   $ID = ReplyRecordID($Comment);
   
   $Path = '/post/reply?commentid='.urlencode($ID);
   
   echo Anchor(Sprite('SpReply', 'ReactSprite').' '.T('Reply'), $Path, 'ReactButton Reply Visible');
}

endif;

if (!function_exists('WriteReplyEdit')):

function WriteReplyEdit($Reply) {
   $Form = Gdn::Controller()->ReplyForm;
   $Form->IDPrefix = 'EditReply_';
   
   $User = Gdn::UserModel()->GetID($Reply['InsertUserID']);
      
   echo '<div id="Reply_'.$Reply['ReplyID'].'" class="Item Item-Reply Item-ReplyEdit HasPhoto">';
   echo $Form->Open();

   // Author photo stuff.
   echo UserPhoto($User);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo UserAnchor($User, 'Username');
      echo ' '.Wrap(Gdn_Format::Date($Reply['DateInserted'], 'html'), 'span', array('class' => 'Meta DateInserted'));
   echo '</div>';

   echo '<div class="Reply-Body Message">';
   
   echo $Form->Errors();
   echo $Form->TextBox('Body', array('Multiline' => TRUE, 'Wrap' => TRUE, 'rows' => 4));

   echo '<div class="Buttons">';
   echo $Form->Button('Cancel', array('class' => 'Button Cancel', 'tabindex' => '-1'));
   echo ' ';
   echo $Form->Button('Save', array('class' => 'Button Primary'));
   echo '</div>';

   echo '</div>';

   echo '</div>';

   echo $Form->Close();
   echo '</div>';
}
endif;

if (!function_exists('WriteReplyForm')):
   
function WriteReplyForm($Comment) {
   if (!Gdn::Session()->CheckPermission('Vanilla.Replies.Add'))
      return;
   
   echo '<div class="Item Item-ReplyForm HasPhoto">';
   
   // Author photo stuff.
   echo UserPhoto(Gdn::Session()->User);
   
   
   echo '<div class="ItemContent ItemContent-Reply">';
   
      echo '<div class="Reply-Body">';
      
      $Form = Gdn::Controller()->ReplyForm;
         
      // Write the regular link.
      echo Anchor(T('Reply here...'), '#', 'FormPlaceholder InputBox', array('style' => $Form->ErrorCount() ? 'display: none' : ''));
      
      $ID = GetValue('CommentID', $Comment);
      if (!$ID)
         $ID = -GetValue('DiscussionID', $Comment);
      
      // Write the form.
      $Form->IDPrefix = 'Reply_';
      echo $Form->Open(array('action' => Url('/post/reply?commentid='.$ID), 'class' => $Form->ErrorCount() ? '' : 'Hidden'));
      echo $Form->Errors();
      echo $Form->TextBox('Body', array('Multiline' => TRUE, 'Wrap' => TRUE, 'rows' => 4));
      
      echo '<div class="Buttons">';
      echo $Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Cancel', 'tabindex' => '-1'));
      echo ' ';
      echo $Form->Button('Reply', array('class' => 'Button Primary'));
      echo '</div>';
      
      echo $Form->Close();
      
      echo '</div>';
      
      echo '</div>';
   
   echo '</div>';
}
   
endif;


if (!function_exists('WriteReplyOptions')):
   
function WriteReplyOptions($Reply) {
	$Options = GetReplyOptions($Reply);
	if (empty($Options))
		return;

   echo '<span class="ToggleFlyout OptionsMenu">';
      echo '<span class="OptionsTitle" title="'.T('Options').'">'.T('Options').'</span>';
      
      echo '<ul class="Flyout MenuItems">';
      foreach ($Options as $Code => $Option):
         echo Wrap(Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, 'Option-'.$Code)), 'li');
      endforeach;
      echo '</ul>';
   echo '</span>';
}

endif;