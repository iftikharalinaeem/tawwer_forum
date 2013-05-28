<?php if (!defined('APPLICATION')) return; ?>
<div class="DataListWrap">
<h2 class="H"><?php echo T('Warnings'); ?></h2>
<ul class="DataList Activities">
<?php 
if (count($this->Data('Warnings')) > 0 ): 
   $Moderator = Gdn::Session()->CheckPermission('Garden.Moderation.Manage');
   $Now = time();
   $ExpiredFound = FALSE;
   
   foreach ($this->Data('Warnings') as $Row): 
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
         echo '<div class="Options">'.Anchor('×', 'profile/removewarning?warningid='.$Row['WarningID'].'&target='.urlencode($this->SelfUrl), 'Delete Popup').'</div>';
      }
      ?>
      
      <div class="Author Photo">
         <?php
         $Photo = UserPhoto($Row, array('Px' => 'Insert'));
         if (!$Photo)
            $Photo = Img('http://cdn.vanillaforums.com/images/warn_50.png', array('class' => 'ProfilePhoto'));
         echo $Photo;
         ?>
      </div>
      <div class="ItemContent Activity">
         <div class="Title">
            <?php
            if ($Row['Points'] == 0) {
               $TitleFormat = T('WarningTitleFormat.Notice', '{InsertUserID,User} warned {WarnUserID,User} for {Points,plural,%s points} (just a notice).');
            } else {
               $TitleFormat = T('WarningTitleFormat', '{InsertUserID,User} warned {WarnUserID,User} for {Points,plural,%s points}.');
            }
            
            $Title = FormatString($TitleFormat, $Row);
            echo $Title;
            ?>
         </div>
         <div class="Message">
            <?php
            echo Gdn_Format::To($Row['Body'], $Row['Format']);
            
            if ($Row['ModeratorNote'] && Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
               echo '<div class="Hero ModeratorNote">';
               echo '<div><b>'.T('Private Note for Moderators').'</b></div>';
               echo Gdn_Format::To($Row['ModeratorNote'], $Row['Format']);
               echo '</div>';
            }
            ?>
         </div>
         <div class="Meta">
            <span class="MItem DateInserted"><?php echo Gdn_Format::Date($Row['DateInserted'], 'html'); ?></span>
            <span class="MItem DateExpires">
               <?php
               $RemoveUserID = GetValue('RemovedByUserID', $Row['Attributes']);
               
               if ($Row['DateExpires']) {
                  // The warning has an expiry date.
                  echo '<span class="MLabel">'.T($Row['Expired'] ? 'Expired' : 'Expires').'</span> ',
                     '<span class="MValue">'.Gdn_Format::Date($Row['DateExpires'], 'html').'</span>';
               } elseif ($Row['Expired'] == FALSE) {
                  // The warning doesn't expire.
                  echo '<span class="MValue">'.T("Doesn't expire").'</span>';
               } elseif (!$RemoveUserID) {
                  // The warning just expired.
                  echo '<span class="MValue">'.T("Expired").'</span>';
               }
               ?>
            </span>
            <?php
            if ($RemoveUserID) {
               // A user remove this warning.
               $User = Gdn::UserModel()->GetID($RemoveUserID, DATASET_TYPE_ARRAY);
               echo '<span class="MItem RemovedBy"><span class="MLabel">'.T('Removed by').'</span> ',
                  '<span class="MValue">'.UserAnchor($User).'</span></span>';
            }
            ?>
         </div>
      </div>
   </li>
   <?php 
   endforeach; 
else:
   echo '<li><div class="Empty">'.T('Not much happening here, yet.').'</div></li>';
endif;
   ?>
</ul>
</div>
