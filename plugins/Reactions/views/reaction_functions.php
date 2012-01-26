<?php if (!defined('APPLICATION')) exit();

if (!function_exists('WriteReactionBar')):
   
function WriteReactionBar($Row) {
   $Attributes = GetValue('Attributes', $Row);
   if (is_string($Attributes)) {
      $Attributes = @unserialize($Attributes);
      SetValue('Attributes', $Row, $Attributes);
   }
   
   decho($Row->Score, 'Score');
   
   echo '<div class="Reactions">';
   
   // Write the flags.
   echo '<div class="Flag">';
   echo '<div class="Handle FlagHandle"><a href="#"><span class="ReactSprite ReactFlag"></span> <span class="ReactLabel">Flag</span></a></div>';
   
   echo '<div class="ReactButtons" style="display:none">';
   echo '<a href="#" class="ReactHeading">'.T('Flag »').'</a> ';
   echo ReactionButton($Row, 'Abuse');
   echo ReactionButton($Row, 'Spam');
   echo ReactionButton($Row, 'Troll');
   
   echo '</div>';
   
   echo '</div>';
   
   
   echo '<div class="Nub">&#160;</div>';
   
   // Write the reactions.
   echo '<div class="React">';
   
   
   echo '<div class="ReactButtons">';
   
   echo ReactionButton($Row, 'OffTopic');
   echo ReactionButton($Row, 'Disagree');
   echo ReactionButton($Row, 'Agree');
   echo ReactionButton($Row, 'Awesome');
   echo ' <a href="#" class="ReactHeading">'.T('« React').'</a>';
   echo '</div>';
   
   echo '<div class="Handle ReactHandle" style="display:none"><a href="#"><span class="ReactSprite ReactAgree"></span> <span class="ReactLabel">React</span></a></div>';
   echo '</div>';
   
   echo '</div>';
}

endif;


if (!function_exists('ReactionButton')):
   
function ReactionButton($Row, $UrlCode, $Options = array()) {
   $ReactionType = ReactionModel::ReactionTypes($UrlCode);
   
   $Name = $ReactionType['Name'];
   $Label = $Name;
   $SpriteClass = GetValue('SpriteClass', $ReactionType, "React$UrlCode");
   $Count = GetValueR("Attributes.React.$UrlCode", $Row, 0);
   $CountHtml = '';
   $LinkClass = "ReactButton-$UrlCode";
   if ($Count) {
      $CountHtml = ' <span class="Count">'.$Count.'</span>';
      $LinkClass .= ' HasCount';
   }
   
   if (!$ID = GetValue('CommentID', $Row)) {
      $RecordType = 'discussion';
      $ID = GetValue('DiscussionID', $Row);
   } else {
      $RecordType = 'comment';
   }
   
   $UrlCode2 = strtolower($UrlCode);
   $Url = Url("/react/$RecordType/$UrlCode2?id=$ID");
   
   $Result = <<<EOT
<a class="Hijack ReactButton $LinkClass" href="$Url" rel="nofollow"><span class="ReactSprite $SpriteClass"></span> <span class="ReactLabel">$Label</span>$CountHtml</a>
   
EOT;
   
   return $Result;
}

endif;