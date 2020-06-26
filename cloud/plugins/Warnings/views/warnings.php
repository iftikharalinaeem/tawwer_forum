<?php if (!defined('APPLICATION')) return; ?>
<div class="DataListWrap">
<h2 class="H"><?php echo t('Warnings'); ?></h2>
<ul class="DataList Activities">
<?php 
if (count($this->data('Warnings')) > 0 ): 
   $Moderator = Gdn::session()->checkPermission('Garden.Moderation.Manage');
   $Now = time();
   $ExpiredFound = FALSE;
   
   foreach ($this->data('Warnings') as $Row): 
      $CssClass = '';
      if ($Row['Expired']) {
         // The warning has expired.
         $CssClass = ' Expired';
         
         if (!$ExpiredFound) {
            $CssClass .= ' FirstExpired';
            $ExpiredFound = TRUE;
         }
      }
   ?>
   <li id="Warning_<?php echo $Row['WarningID']; ?>" class="Item HasPhoto<?php echo $CssClass; ?>">
      <?php
      if ($Moderator) {
         echo '<div class="Options">'.anchor('×', 'profile/removewarning?warningid='.$Row['WarningID'], 'Delete Popup').'</div>';
      }
      ?>
      
      <div class="Author Photo">
         <?php
         $Photo = userPhoto($Row, ['Px' => 'Insert']);
         if (!$Photo)
            $Photo = '<span class="PhotoWrap">'.img('https://images.v-cdn.net/warn_50.png', ['class' => 'ProfilePhoto']).'</span>';
         echo $Photo;
         ?>
      </div>
      <div class="ItemContent Activity">
         <div class="Title">
            <?php
            if ($Row['Points'] == 0) {
               $TitleFormat = t('WarningTitleFormat.Notice', '{InsertUserID,User} warned {WarnUserID,User} for {Points,plural,%s point} (just a notice).');
            } else {
               $TitleFormat = t('WarningTitleFormat', '{InsertUserID,User} warned {WarnUserID,User} for {Points,plural,%s point}.');
            }
            
            $Title = formatString($TitleFormat, $Row);
            echo $Title;
            ?>
         </div>
         <div class="Message">
            <?php
            echo Gdn_Format::to($Row['Body'], $Row['Format']);
            
            if ($Row['ModeratorNote'] && Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
               echo '<div class="Hero ModeratorNote">';
               echo '<div><b>'.t('Private Note for Moderators').'</b></div>';
               echo Gdn_Format::to($Row['ModeratorNote'], $Row['Format']);
               echo '</div>';
            }
            ?>
         </div>
         <div class="Meta">
            <span class="MItem DateInserted"><?php echo Gdn_Format::date($Row['DateInserted'], 'html'); ?></span>
            <span class="MItem DateExpires">
               <?php
               $RemoveUserID = getValue('RemovedByUserID', $Row['Attributes']);
               
               if ($Row['DateExpires']) {
                  // The warning has an expiry date.
                  echo '<span class="MLabel">'.t($Row['Expired'] ? 'Expired' : 'Expires').'</span> ',
                     '<span class="MValue">'.Gdn_Format::date($Row['DateExpires'], 'html').'</span>';
               } elseif ($Row['Expired'] == FALSE) {
                  // The warning doesn't expire.
                  echo '<span class="MValue">'.t("Doesn't expire").'</span>';
               } elseif (!$RemoveUserID) {
                  // The warning just expired.
                  echo '<span class="MValue">'.t("Expired").'</span>';
               }
               ?>
            </span>
            <?php
            if ($RemoveUserID) {
               // A user remove this warning.
               $User = Gdn::userModel()->getID($RemoveUserID, DATASET_TYPE_ARRAY);
               echo '<span class="MItem RemovedBy"><span class="MLabel">'.t('Removed by').'</span> ',
                  '<span class="MValue">'.userAnchor($User).'</span></span>';
            }
            ?>
         </div>
      </div>
   </li>
   <?php 
   endforeach; 
else:
   echo '<li><div class="Empty">'.t('Not much happening here, yet.').'</div></li>';
endif;
   ?>
</ul>
</div>
