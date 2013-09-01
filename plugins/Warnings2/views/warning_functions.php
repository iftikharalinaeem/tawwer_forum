<?php if (!defined('APPLICATION')) return;

if (!function_exists('WriteUserNoteWarning')):

function WriteUserNoteWarning($Row) {   
  $Reversed = GetValue('Reversed', $Row);
  ?>
   <div class="Item-Col Item-Col8">
      <div class="Meta">
         <div class="Options">
            <?php
            Gdn_Theme::BulletRow(Bullet(' '));
            if (GetValue('RecordType', $Row) == 'Conversation') {
               echo Gdn_Theme::BulletItem('Conversation').
                  Anchor(T('message'), '/messages/'.GetValue('RecordID', $Row).'#latest', 'OptionsLink');
            }
            
            if (!$Reversed) {
               echo Gdn_Theme::BulletItem('Reverse').
                  Anchor(T('reverse'), '/profile/reversewarning?id='.$Row['UserNoteID'], 'Popup OptionsLink', array('title' => T('Reverse Warning')));
            }
            ?>
         </div>
         <?php
         echo '<span class="NoteType NoteType-'.$Row['Type'].'">'.T(ucfirst($Row['Type'])).'</span> '.Bullet(' ');

         if ($Reversed)
            echo '<del>';
         
         echo Plural(GetValue('Points', $Row, 0), '%s point', '%s points');
         
         if (isset($Row['ExpiresString'])) {
            echo Bullet(' ').
            sprintf(T('lasts %s'), $Row['ExpiresString']);
         }
         
         if ($Reversed)
            echo '</del>';
         
         if (GetValue('Reversed', $Row)) {
            echo Bullet(' ').
               T('reversed');
         }
         ?>
      </div>
      <div class="Post">
         <?php
         echo $Row['Body'];
         
         if (GetValue('ModeratorNote', $Row)) {
            echo '<div class="P">'.
               '<b>'.T('Private Note for Moderators').'</b>: '.
               Gdn_Format::Text($Row['ModeratorNote']).
               '</div>';
         }
         ?>
      </div>
   </div>
   <div class="Item-Col Item-Col4 User-Col">
      <div class="Media">
         <?php echo UserPhoto($Row, array('LinkClass' => 'Img', 'Px' => 'Insert')); ?>
         <div class="Media-Body">
            <?php 
            echo '<div>'.UserAnchor($Row, '', array('Px' => 'Insert')).'</div> ';
            echo '<div class="Meta">'.Gdn_Format::Date($Row['DateInserted'], 'html').'</div>';
            ?>
         </div>
      </div>
   </div>
   <?php
}

endif;