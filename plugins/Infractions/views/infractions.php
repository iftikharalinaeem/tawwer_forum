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
   ?>
<h1>Infraction History</h1>
   <?php
   $Active = 0;
   $Total = $InfractionData->NumRows();
   $UnReversed = 0;
   $Points = 0;
   $Warnings = 0;
   foreach ($InfractionData->Result() as $Infraction) {
      // Define data for summary
      if ($Infraction->Reversed == '0' && Gdn_Format::ToTimestamp($Infraction->DateExpires) > time()) {
         $Active++;
         $Points += $Infraction->Points;
      }
      if ($Infraction->Reversed == '0')
         $UnReversed++;

      if ($Infraction->Warning)
         $Warnings++;
      
      // Write history row
      var_dump($Infraction);
      echo '<br />';
   }
   
   // Write out infraction summary
   ?>
   <ul class="Overview InfractionOverview">
      <?php
      WriteOverviewItem('Active', $Active);
      WriteOverviewItem('Total', $UnReversed);
      WriteOverviewItem('Points', $Points);
      WriteOverviewItem('Warnings', $Warnings);
      ?>
   </ul>
   <strong>
   <?php
   if ($Points >= 8) {
      echo 'User has been banned for these infractions.';
   } else if ($Points >= 4) {
      echo 'User has been jailed for these infractions.';
   } else {
      echo 'User has not yet incurred any punishment for these infractions';
   }
   ?>
   </strong>
   <?php
}