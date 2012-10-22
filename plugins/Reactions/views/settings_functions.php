<?php if (!defined('APPLICATION')) exit;

function AutoDescription($ReactionType) {
   $Result = array();
   
   if ($Permission = GetValue('Permission', $ReactionType)) {
      switch ($Permission) {
         case 'Garden.Moderation.Manage':
            $Result[] = '<b>Only moderators can use this reaction.</b>';
            break;
         case 'Garden.Settings.Manage':
            $Result[] = '<b>Only administrators can use this reaction.</b>';
            break;
      }
   }
   
   if ($IncrementColumn = GetValue('IncrementColumn', $ReactionType)) {
      $IncrementValue = GetValue('IncrementValue', $ReactionType, 1);
      $IncrementString = $IncrementValue > 0 ? "adds $IncrementValue to" : "subtracts ".abs($IncrementValue)." from";
      $IncrementString = '<b>'.$IncrementString.'</b>';
      
      $Result[] = sprintf("This reaction %s a post's %s.", $IncrementString, strtolower($IncrementColumn));
   }
   
   if ($Points = GetValue('Points', $ReactionType)) {
      $Result[] = Plural($Points, 'Users that get this reaction get %+d point.', 'Users that get this reaction get %+d points.');
   }
   
   if ($LogThreshold = GetValue('LogThreshold', $ReactionType)) {
      $Log = GetValue('Log', $ReactionType, 'Moderate');
      $LogString = $Log == 'Spam' ? 'spam queue' : 'moderation queue';
      
      $Result[] = sprintf("Posts with %s reactions will be placed in the <b>%s</b>.", $LogThreshold, $LogString);
   }
   
   if ($RemoveThreshold = GetValue('RemoveThreshold', $ReactionType)) {
      if ($RemoveThreshold != $LogThreshold) {
         $Result[] = sprintf("Posts will be removed when they get %s reactions.", $RemoveThreshold);
      }
   }
   
   return $Result;
}

function ActivateButton($ReactionType) {
   $Qs = array(
       'type' => $ReactionType['UrlCode'],
       'active' => !$ReactionType['Active']);
   
   $State = ($ReactionType['Active'] ? 'Active' : 'InActive');
   
   return '<span class="ActivateSlider ActivateSlider-'.$State.'">'.
      Anchor($State, '/settings/activatereactiontype?'.http_build_query($Qs), "SmallButton Hijack").
      '</span>';   
}