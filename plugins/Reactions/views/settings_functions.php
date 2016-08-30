<?php if (!defined('APPLICATION')) exit;

function AutoDescription($ReactionType) {
   $Result = array();

   if ($IncrementColumn = GetValue('IncrementColumn', $ReactionType)) {
      $IncrementValue = GetValue('IncrementValue', $ReactionType, 1);
      $IncrementString = $IncrementValue > 0 ? "<b>Adds $IncrementValue</b> to" : "<b>Subtracts ".abs($IncrementValue)."</b> from";

      $Result[] = sprintf("%s a <b>post</b>'s %s.", $IncrementString, strtolower($IncrementColumn));
   }

   if ($Points = GetValue('Points', $ReactionType)) {
      if ($Points > 0)
         $IncrementString = "<b>Gives $Points</b> ".Plural($Points,'point','points')." to";
      else
         $IncrementString = "<b>Removes ".abs($Points)."</b> ".Plural($Points,'point','points')." from";

      $Result[] = sprintf("%s the <b>user</b>.", $IncrementString);
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

   if ($Class = GetValue('Class', $ReactionType)) {
      $Result[] = sprintf(T('ReactionClassRestriction', 'Requires &ldquo;%s&rdquo; reaction permission.'), $Class);
   }

   if ($Permission = GetValue('Permission', $ReactionType)) {
      $Result[] = sprintf(T('ReactionPermissionRestriction', '<b>Special restriction:</b> Only users with permission %s may use this reaction.'),$Permission);
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
