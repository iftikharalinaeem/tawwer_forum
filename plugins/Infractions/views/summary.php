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
   $Points = 0;
   $Warnings = 0;
   if ($Total > 0) {
      foreach ($InfractionData->Result() as $Infraction) {
         // Define data for summary
         if ($Infraction->Reversed == '0' && Gdn_Format::ToTimestamp($Infraction->DateExpires) > time() && !$Infraction->Warning) {
            $Active++;
            $Points += $Infraction->Points;
         }
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
         WriteOverviewItem('Total', $Total);
         WriteOverviewItem('Points', $Points);
         WriteOverviewItem('Warnings', $Warnings);
         ?>
      </ul>
      <div class="Punishment">
      <?php
      if ($Points >= 8) {
         echo 'User is currently banned for these infractions.';
      } else if ($Points >= 4) {
         echo 'User is currently jailed for these infractions.';
      } else {
         echo 'User has not yet incurred any punishment for these infractions';
      }
      ?>
      </div>
      <ul class="DataList Infractions">
      <?php foreach ($InfractionData->Result() as $Infraction) { ?>
         <li class="Item">
            <div class="ItemContent Infraction">
               <?php
               echo Gdn_Format::Text($Infraction->Reason).': '; 
               if ($Infraction->ActivityID > 0) {
                  echo Anchor(SliceString(Gdn_Format::Text($Infraction->ActivityBody), 100), '/activity/item/'.$Infraction->ActivityID);
               } else if ($Infraction->CommentID > 0) {
                  echo Anchor(SliceString(Gdn_Format::Text($Infraction->CommentBody), 100), '/discussion/comment/'.$Infraction->CommentID.'/#Comment_'.$Infraction->CommentID);
               } else if ($Infraction->DiscussionID > 0) {
                  echo Anchor(SliceString(Gdn_Format::Text($Infraction->DiscussionName), 100), '/discussion/'.$Infraction->DiscussionID.'/'.Gdn_Format::Url($Infraction->DiscussionName));
               } else {
                  echo 'Profile Infraction';
               }
               ?>
               <div class="Meta">
                  <span class="Admin"><?php echo UserAnchor(UserBuilder($Infraction, 'Insert')); ?></span>
                  <span class="Inserted"><?php echo Gdn_Format::Date($Infraction->DateInserted); ?></span>
                  <span class="DateExpires"><?php echo $Infraction->Reversed ? 'Reversed' : 'Expires '.Gdn_Format::Date($Infraction->DateExpires); ?></span>
                  <span class="InfractionPoints"><?php echo $Infraction->Warning == '1' ? 'Warning' : 'Points: '.number_format($Infraction->Points); ?></span>
               </div>
            </div>
         </li>
         <?php
      }
      echo '</ul>';
   } else {
      echo Wrap('This user has not had any infractions.', 'p');
   }
}