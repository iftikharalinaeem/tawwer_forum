<?php

if (!function_exists('WriteReply')):
   
function WriteReply($Reply) {
   $User = Gdn::UserModel()->GetID($Reply['InsertUserID']);
      
   echo '<div id="Reply_'.$Reply['ReplyID'].'" class="Item Item-Reply HasPhoto">';

   // Author photo stuff.
   echo UserPhoto($User);

   echo '<div class="ItemContent ItemContent-Reply">';

   echo '<div class="Reply-Header">';
      echo UserAnchor($User);
      echo ' '.Gdn_Format::Date($Reply['DateInserted']);
   echo '</div>';

   echo '<div class="Reply-Body">';
      echo Gdn_Format::Text($Reply['Body']);
   echo '</div>';

   echo '</div>';

   echo '</div>';
}

endif;

if (!function_exists('WriteReplyButton')):

function WriteReplyButton($Comment) {
   $Path = '/post/reply?commentid='.urlencode(GetValue('CommentID', $Comment));
   
   echo Anchor(Sprite('SpReply', 'ReactSprite').' '.T('Reply'), $Path, 'ReactButton Reply Visible');
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
      echo Anchor(T('Write a reply'), '#', 'FormPlaceholder InputBox', array('style' => $Form->ErrorCount() ? 'display: none' : ''));
      
      // Write the form.
      $Form->IDPrefix = 'Reply_';
      echo $Form->Open(array('action' => Url('/post/reply?commentid='.GetValue('CommentID', $Comment)), 'class' => $Form->ErrorCount() ? '' : 'Hidden'));
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

if (!function_exists('WriteReplies')):
   
function WriteReplies($Comment) {
   $Replies = GetValue('Replies', $Comment, array());
   
   $Hidden = count($Replies) == 0 ? ' Hidden' : '';
   
   echo '<div id="Replies_'.GetValue('CommentID', $Comment).'" class="DataList DataList-Replies'.$Hidden.'">';
   
   // Write out all of the replies.
   foreach ($Replies as $Reply) {
      WriteReply($Reply);
   }
   
   WriteReplyForm($Comment);
   
   echo '</div>';
}

endif;