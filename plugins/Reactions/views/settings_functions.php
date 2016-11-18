<?php if (!defined('APPLICATION')) exit;

function AutoDescription($ReactionType) {
   $Result = array();

   if ($IncrementColumn = GetValue('IncrementColumn', $ReactionType)) {
      $IncrementValue = GetValue('IncrementValue', $ReactionType, 1);
      $IncrementString = $IncrementValue > 0 ? "Adds $IncrementValue to" : "Subtracts ".abs($IncrementValue)." from";

      $Result[] = sprintf("%s a post's %s.", $IncrementString, strtolower($IncrementColumn));
   }

   if ($Points = GetValue('Points', $ReactionType)) {
      if ($Points > 0)
         $IncrementString = "Gives $Points ".Plural($Points,'point','points')." to";
      else
         $IncrementString = "Removes ".abs($Points)." ".Plural($Points,'point','points')." from";

      $Result[] = sprintf("%s the user.", $IncrementString);
   }

   if ($LogThreshold = GetValue('LogThreshold', $ReactionType)) {
      $Log = GetValue('Log', $ReactionType, 'Moderate');
      $LogString = $Log == 'Spam' ? 'spam queue' : 'moderation queue';

      $Result[] = sprintf("Posts with %s reactions will be placed in the %s.", $LogThreshold, $LogString);
   }

   if ($RemoveThreshold = GetValue('RemoveThreshold', $ReactionType)) {
      if ($RemoveThreshold != $LogThreshold) {
         $Result[] = sprintf("Posts will be removed when they get %s reactions.", $RemoveThreshold);
      }
   }

   if ($Class = GetValue('Class', $ReactionType)) {
      $Result[] = sprintf(T('ReactionClassRestriction', 'Requires &ldquo;%s&rdquo; reaction permission.'), $Class);
   }

   if ($Permission = GetValue('Permission', $ReactionType)) {
      $Result[] = sprintf(T('ReactionPermissionRestriction', 'Special restriction: Only users with permission %s may use this reaction.'),$Permission);
   }

   return $Result;
}

function ActivateButton($ReactionType) {
   $Qs = array(
       'urlcode' => $ReactionType['UrlCode'],
       'active' => !$ReactionType['Active']);

   $State = ($ReactionType['Active'] ? 'Active' : 'InActive');

   $return = '<span id="reactions-toggle">';
   if ($State === 'Active') {
      $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/reactions/toggle?'.http_build_query($Qs), 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-on"));
   } else {
      $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/reactions/toggle?'.http_build_query($Qs), 'Hijack'), 'span', array('class' => "toggle-wrap toggle-wrap-off"));
   }

   $return .= '</span>';

   return $return;
}
