<?php if (!defined('APPLICATION')) exit;

function AutoDescription($reactionType) {
   $result = [];

   if ($incrementColumn = GetValue('IncrementColumn', $reactionType)) {
      $incrementValue = GetValue('IncrementValue', $reactionType, 1);
      $incrementString = $incrementValue > 0 ? "Adds $incrementValue to" : "Subtracts ".abs($incrementValue)." from";

      $result[] = sprintf("%s a post's %s.", $incrementString, strtolower($incrementColumn));
   }

   if ($points = GetValue('Points', $reactionType)) {
      if ($points > 0)
         $incrementString = "Gives $points ".Plural($points,'point','points')." to";
      else
         $incrementString = "Removes ".abs($points)." ".Plural($points,'point','points')." from";

      $result[] = sprintf("%s the user.", $incrementString);
   }

   if ($logThreshold = GetValue('LogThreshold', $reactionType)) {
      $log = GetValue('Log', $reactionType, 'Moderate');
      $logString = $log == 'Spam' ? 'spam queue' : 'moderation queue';

      $result[] = sprintf("Posts with %s reactions will be placed in the %s.", $logThreshold, $logString);
   }

   if ($removeThreshold = GetValue('RemoveThreshold', $reactionType)) {
      if ($removeThreshold != $logThreshold) {
         $result[] = sprintf("Posts will be removed when they get %s reactions.", $removeThreshold);
      }
   }

   if ($class = GetValue('Class', $reactionType)) {
      $result[] = sprintf(T('ReactionClassRestriction', 'Requires &ldquo;%s&rdquo; reaction permission.'), $class);
   }

   if ($permission = GetValue('Permission', $reactionType)) {
      $result[] = sprintf(T('ReactionPermissionRestriction', 'Special restriction: Only users with permission %s may use this reaction.'),$permission);
   }

   return $result;
}

function ActivateButton($reactionType) {
   $qs = [
       'urlcode' => $reactionType['UrlCode'],
       'active' => !$reactionType['Active']];

   $state = ($reactionType['Active'] ? 'Active' : 'InActive');

   $return = '<span id="reactions-toggle">';
   if ($state === 'Active') {
      $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/reactions/toggle?'.http_build_query($qs), 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-on"]);
   } else {
      $return .= wrap(anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', '/reactions/toggle?'.http_build_query($qs), 'Hijack'), 'span', ['class' => "toggle-wrap toggle-wrap-off"]);
   }

   $return .= '</span>';

   return $return;
}
