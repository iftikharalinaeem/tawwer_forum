<?php if (!defined('APPLICATION')) exit();

function WriteOverviewItem($Name, $Value, $Css = '') {
   if ($Css == '')
      $Css = $Name;
   echo '<li class="'.$Css.'">
      <div>
         '.$Name.'
         <strong>'.$Value.'</strong>
      </div>
   </li>';
}

$InfractionData = GetValue('InfractionData', $this->Data);
if (is_object($InfractionData)) {
   $Active = 0;
   $Total = $InfractionData->NumRows();
   $UnReversed = 0;
   $Points = 0;
   $Warnings = 0;
   if ($Total > 0) {
      foreach ($InfractionData->Result() as $Infraction) {
         // Define data for summary
         if ($Infraction->Reversed == '0'
            && (Gdn_Format::ToTimestamp($Infraction->DateExpires) > time() || $Infraction->DateExpires == '0000-00-00 00:00:00')
            && !$Infraction->Warning) {
            $Active++;
            $Points += $Infraction->Points;
         }
         if ($Infraction->Reversed == '0')
            $UnReversed++;

         if ($Infraction->Warning)
            $Warnings++;
      }
      
      // Write out infraction summary
      ?>
      <style type="text/css">
      ul.InfractionOverview {
         padding: 10px 0 0 20px;
         text-align: center;
      }
      ul.InfractionOverview li {
         color: #555555;
         display: inline-block;
         font-size: 10px;
         text-align: center;
         white-space: nowrap;
         width: 25%;
      }
      ul.InfractionOverview li div {
         margin: 0 auto;
         overflow: visible;
         text-align: left;
         width: 120px;
      }
      ul.InfractionOverview li strong {
         color: #222222;
         display: block;
         font-family: 'helvetica neue',arial,helvetica;
         font-size: 30px;
         font-weight: bold;
         line-height: 1;
      }
      div.Punishment {
         margin: 10px 0 0;
         padding: 10px 6px;
         background: #fafafa;
         border-top: 1px dotted #555;
         border-bottom: 1px dotted #555;
      }
      </style>
      <ul class="Overview InfractionOverview">
         <?php
         WriteOverviewItem('Active', $Active);
         WriteOverviewItem('Total', $UnReversed);
         WriteOverviewItem('Points', $Points);
         WriteOverviewItem('Warnings', $Warnings);
         ?>
      </ul>
      <div class="Punishment">
      <?php
      if ($Points >= 8)
         echo 'User is currently banned for these infractions.';
      else if ($Points >= 6)
         echo 'User has been temporarily banned for these infractions.';
      else if ($Points >= 4)
         echo 'User is currently jailed for these infractions.';
      else
         echo 'User has not yet incurred any punishment for these infractions';
      ?>
      </div>
      <ul class="DataList Infractions">
      <?php foreach ($InfractionData->Result() as $Infraction) { ?>
         <li class="Item">
            <div class="ItemContent Infraction">
               <?php
               echo '<div class="Title">';
               echo Gdn_Format::Text($Infraction->Reason);
               
               // Don't show admin note to non admins or to the target user
               if (Gdn::Session()->CheckPermission('Garden.Infractions.Manage') && Gdn::Session()->UserID != $Infraction->UserID) {
                  if ($Infraction->Note)
                     echo ': ', Gdn_Format::Text($Infraction->Note);
                  echo '</div>';
               }
               
               echo '<div class="Message">';
               if ($Infraction->ActivityID > 0) {
                  echo Anchor(SliceString(Gdn_Format::Text($Infraction->ActivityBody), 100), '/activity/item/'.$Infraction->ActivityID);
               } else if ($Infraction->CommentID > 0) {
                  echo Anchor(SliceString(Gdn_Format::Text(Gdn_Format::To($Infraction->CommentBody, $Infraction->CommentFormat)), 100), '/discussion/comment/'.$Infraction->CommentID.'/#Comment_'.$Infraction->CommentID);
               } else if ($Infraction->DiscussionID > 0) {
                  echo Anchor(SliceString(Gdn_Format::Text($Infraction->DiscussionName), 100), '/discussion/'.$Infraction->DiscussionID.'/'.Gdn_Format::Url($Infraction->DiscussionName));
               } else {
                  echo 'Profile Infraction';
               }
               echo '</div>';
               ?>
               <div class="Meta">
                  <span class="Admin"><?php echo UserAnchor(UserBuilder($Infraction, 'Insert')); ?></span>
                  <span class="Inserted"><?php echo Gdn_Format::Date($Infraction->DateInserted); ?></span>
                  <span class="DateExpires"><?php
                     if ($Infraction->Reversed)
                        echo 'Reversed';
                     else {
                        $ExpiryDateTime = strtotime($Infraction->DateExpires);
                        
                        if ($Infraction->DateExpires == NULL || $Infraction->DateExpires == '0000-00-00 00:00:00')
                           echo 'Never Expires';
                        else if ($ExpiryDateTime < time())
                           echo 'Expired '.Gdn_Format::Date($Infraction->DateExpires);
                        else
                           echo 'Expires '.Gdn_Format::Date($Infraction->DateExpires);
                     }
                  ?></span>
                  <span class="InfractionPoints"><?php echo $Infraction->Warning == '1' ? 'Warning' : 'Points: '.number_format($Infraction->Points); ?></span>
                  <?php
                  if (Gdn::Session()->CheckPermission('Garden.Infractions.Manage') && !$Infraction->Reversed) {
                     echo Anchor('Reverse', '/profile/reverseinfraction/'.$Infraction->InfractionID.'/'.Gdn::Session()->TransientKey(), array('class' => 'PopConfirm'));
                  }
                  ?>
               </div>
            </div>
         </li>
         <?php
      }
      echo '</ul>';
   } else {
      echo Wrap('This user has not had any infractions.', 'div', array('class' => 'Empty'));
   }
}