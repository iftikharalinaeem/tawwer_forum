<?php if (!defined('APPLICATION')) return;

if (!function_exists('WriteUserNoteWarningUser')):
/***
 * Write note for a user.
 *
 * @param array user note
 */
function writeUserNoteWarningUser($Row) {
  $ViewNoteUrl = Url("/profile/viewnote/{$Row['UserNoteID']}");
  ?>
   <div class="Item-Col item-col-fullwidth">

       <div class="Media">
           <?php if (!isset($Row['HideWarnerIdentity']) || !$Row['HideWarnerIdentity']): ?>
               <?php echo userPhoto($Row, array('LinkClass' => 'Img', 'Px' => 'Insert')); ?>
           <?php endif; ?>
           <div class="Media-Body">
               <?php
               if (!isset($Row['HideWarnerIdentity']) || !$Row['HideWarnerIdentity']) {
                   echo '<div>'.UserAnchor($Row, '', array('Px' => 'Insert')).'</div> ';
               }
               echo '<div class="Meta"><a href="' . $ViewNoteUrl . '">'.Gdn_Format::date($Row['DateInserted'], 'html').'</a></div>';
               ?>
           </div>
       </div>

       <?php writeUserNoteBody($Row); ?>

   </div>
   <?php
}

endif;


if (!function_exists('WriteUserNoteWarning')):
/***
 * Write note for a user message.
 *
 * @param array user note
 */
function writeUserNoteWarning($Row) {
  $Reversed = val('Reversed', $Row);
  $IsPrivileged = val('Privileged', $Row, false);

  ?>
      <div class="Meta">
         <div class="Options">
            <?php
            Gdn_Theme::bulletRow(bullet(' '));
            if (val('ConversationID', $Row)) {
               echo Gdn_Theme::bulletItem('Conversation').
                  anchor(t('message'), '/messages/'.val('ConversationID', $Row).'#latest', 'OptionsLink', array('title' => t('The private message between the user and moderator.')));
            }

            if (!$Reversed && $IsPrivileged) {
               echo Gdn_Theme::BulletItem('Reverse').
                  anchor(t('reverse'), '/profile/reversewarning?id='.$Row['UserNoteID'], 'Popup OptionsLink', array('title' => t('Reverse this warning')));
            }
            ?>
         </div>

         <?php
         echo '<span class="NoteType NoteType-'.$Row['Type'].'">'.t(ucfirst($Row['Type'])).'</span> '.bullet(' ');

         if (val('Banned', $Row)) {
            echo '<span class="NoteType NoteType-ban">'.t('Ban').'</span> '.bullet(' ');
         }

         if ($Reversed)
            echo '<del>';

         echo Plural(val('Points', $Row, 0), '%s point', '%s points');

         if (isset($Row['ExpiresString'])) {
            echo bullet(' ').
            sprintf(t('lasts %s'), $Row['ExpiresString']);
         }

         if ($Reversed)
            echo '</del>';

         if (val('Reversed', $Row)) {
            echo bullet(' ').
               t('reversed');
         }
         ?>
      </div>

      <div class="Warning-Body">
         <?php

        if (val('Record', $Row)) {
            $Record = $Row['Record'];
            echo '<div class="P">'.
                '<b>'.t('Warned for').'</b>: '.
                anchor(htmlspecialchars($Record['Name']), $Record['Url']).
                '</div>';
        }

        if (val('RecordBody', $Row)) {
            echo '<blockquote class="Quote">' . Gdn_Format::text($Row['RecordBody']) . '</blockquote>';
        } elseif (val('Record', $Row)) {
            echo '<blockquote class="Quote">' . Gdn_Format::text($Record['Body']) . '</blockquote>';
        }




        echo $Row['Body'];

        if (val('ModeratorNote', $Row)) {
            echo '<div class="P">'.
                '<b>'.T('Private note for moderators').'</b>: '.
                Gdn_Format::text($Row['ModeratorNote']).
               '</div>';
         }
         ?>
      </div>
            <?php
}
endif;
