<?php if (!defined('APPLICATION')) return;

if (!function_exists('WriteUserNoteWarningUser')):
/**
 * Write note for a user.
 *
 * @param array user note
 */
function writeUserNoteWarningUser($row) {
  $viewNoteUrl = url("/profile/viewnote/{$row['UserNoteID']}");
  ?>
   <div class="Item-Col item-col-fullwidth">

       <div class="Media">
           <?php if (!isset($row['HideWarnerIdentity']) || !$row['HideWarnerIdentity']): ?>
               <?php echo userPhoto($row, ['LinkClass' => 'Img', 'Px' => 'Insert']); ?>
           <?php endif; ?>
           <div class="Media-Body">
               <?php
               if (!isset($row['HideWarnerIdentity']) || !$row['HideWarnerIdentity']) {
                   echo '<div>'.userAnchor($row, '', ['Px' => 'Insert']).'</div> ';
               }
               echo '<div class="Meta"><a href="' . $viewNoteUrl . '">'.Gdn_Format::date($row['DateInserted'], 'html').'</a></div>';
               ?>
           </div>
       </div>

       <?php writeUserNoteBody($row); ?>

   </div>
   <?php
}

endif;


if (!function_exists('WriteUserNoteWarning')):
/**
 * Write note for a user message.
 *
 * @param array user note
 */
function writeUserNoteWarning($row) {
  $reversed = val('Reversed', $row);
  $isPrivileged = val('Privileged', $row, false);

  ?>
      <div class="Meta">
         <div class="Options">
            <?php
            Gdn_Theme::bulletRow(bullet(' '));
            if (val('ConversationID', $row)) {
               echo Gdn_Theme::bulletItem('Conversation').
                  anchor(t('message'), '/messages/'.val('ConversationID', $row).'#latest', 'OptionsLink', ['title' => t('The private message between the user and moderator.')]);
            }

            if (!$reversed && $isPrivileged) {
               echo Gdn_Theme::bulletItem('Reverse').
                  anchor(t('reverse'), '/profile/reversewarning?id='.$row['UserNoteID'], 'Popup OptionsLink', ['title' => t('Reverse this warning')]);
            }
            ?>
         </div>

         <?php
         echo '<span class="NoteType NoteType-'.$row['Type'].'">'.t(ucfirst($row['Type'])).'</span> '.bullet(' ');

         if (val('Banned', $row)) {
            echo '<span class="NoteType NoteType-ban">'.t('Ban').'</span> '.bullet(' ');
         }

         if ($reversed) {
             echo '<del>';
         }

         echo plural(val('Points', $row, 0), '%s point', '%s points');

         if (isset($row['ExpiresString'])) {
            echo bullet(' ').sprintf(t('lasts %s'), $row['ExpiresString']);
         }

         if ($reversed) {
             echo '</del>';
         }

         if (val('Reversed', $row)) {
            echo bullet(' ').t('reversed');
         }
         ?>
      </div>

      <div class="Warning-Body userContent">
         <?php

        if (val('Record', $row) && ($row['Format'] !== 'Rich')) {
            $record = $row['Record'];
            echo '<div class="P">'.
                '<b>'.t('Warned for').'</b>: '.
                anchor(htmlspecialchars($record['Name']), $record['Url']).
                '</div>';
        }

        if (isset($row['Rule']['Name'])) {
            echo '<b>' . Gdn::translate('Infringed rule') . '</b>: ' . htmlspecialchars($row['Rule']['Name']);

            if (isset($row['Rule']['Description'])) {
                echo '<div class="Meta">' . htmlspecialchars($row['Rule']['Description']) . '</div>';
            }
        }

        if ($row['Format'] !== 'Rich') {
            if (val('RecordBody', $row)) {
                echo '<blockquote class="Quote">' . Gdn_Format::to($row['RecordBody'], $row['RecordFormat'] ?? null) . '</blockquote>';
            } elseif (val('Record', $row)) {
                echo '<blockquote class="Quote">' . Gdn_Format::to($record['Body'], $record['Format'] ?? null) . '</blockquote>';
            }
        }
        
        echo $row['Body'];

        if (val('ModeratorNote', $row)) {
            echo '<div class="P">'.
                '<b>'.t('Private note for moderators').'</b>: '.
                Gdn_Format::text($row['ModeratorNote']).
               '</div>';
         }
         ?>
      </div>
            <?php
}
endif;
