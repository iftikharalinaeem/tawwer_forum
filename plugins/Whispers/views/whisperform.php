<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Whispers-Form">
   <?php
   if (Gdn::controller()->data('Discussion.Attributes.WhisperConversationID')):
      // The form is in private conversation mode.
      echo '<div class="Info">';

      if (Gdn::session()->checkPermission('Conversations.Moderation.Manage')) {
         $Query = [
             'tk' => Gdn::session()->transientKey(),
             'discussionid' => Gdn::controller()->data('Discussion.DiscussionID')];
         echo '<span style="float: right">'.anchor(t('Continue in Public...'), '/discussion/makepublic?'.http_build_query($Query), '', ['title' => t('Continue this discussion in public.')]).'</span>';
      }
   
      echo t('New comments will be in private between:');
   
      echo '<div class="P">';
      foreach (Gdn::controller()->data('WhisperUsers') as $User) {
         echo '<span>',
            userPhoto($User, ['ImageClass' => 'ProfilePhotoSmall']),
            ' '.userAnchor($User),
            '</span> ';
      }
      echo '</div>';
   
      echo '</div>';
      
   else:
      // Here is the general whisper form.
      $Conversations = $this->Conversations;
      $HasPermission = Gdn::session()->checkPermission('Plugins.Whispers.Allow');

      echo '<div class="P">';

      if (Gdn::session()->checkPermission('Conversations.Moderation.Manage')) {
         echo '<span style="float: right">'.anchor(t('Continue in Private...'), '/discussion/makeconversation?discussionid='.Gdn::controller()->data('Discussion.DiscussionID'), '', ['title' => t('Continue this discussion in private.')]).'</span>';
      }

      if ($HasPermission)
         echo $this->Form->checkBox('Whisper', t('Whisper'));

      echo '</div>';

      if ($HasPermission) {
         echo '<div id="WhisperForm">';

         echo '<div class="Info">',
            t('Whispering will start a private conversation.', 'Whispering will start a private conversation associated with this discussion. You can also continue the whole discussion in private by clicking <b>Continue in Private</b> above.'),
            '</div>';

         if (count($Conversations) > 0) {

            echo '<ul>';

            foreach ($Conversations as $Conversation) {
               $Participants = getValue('Participants', $Conversation);
               $ConversationName = '';
               foreach ($Participants as $User) {
                  $ConversationName = concatSep(', ', $ConversationName, htmlspecialchars(getValue('Name', $User)));
               }

               echo '<li>'.$this->Form->radio('ConversationID', $ConversationName, ['Value' => getValue('ConversationID', $Conversation)]).'</li>';
            }
            echo '<li>'.$this->Form->radio('ConversationID', t('New Whisper'), ['Value' => '']).'</li>';

            echo '</ul>';
         }

         echo wrap($this->Form->textBox('To', ['MultiLine' => TRUE, 'class' => 'MultiComplete']), 'div', ['class' => 'TextBoxWrapper']);

         echo '</div>';
      }
   
   endif;
   ?>
</div>