<?php if (!defined('APPLICATION')) exit();

if (!function_exists('FormatScore')):
   
function FormatScore($Score) {
   return $Score;
}

endif;

function OrderByButton($Column, $Label = FALSE, $DefaultOrder = '', $CssClass = '') {
   $QSParams = $_GET;
   $QSParams['orderby'] = urlencode($Column);
   $Url = Gdn::Controller()->SelfUrl.'?'.http_build_query($QSParams);
   if (!$Label)
      $Label = T('by '.$Column);
   
   $CssClass = ' '.$CssClass;
   $CurrentColumn = Gdn::Controller()->Data('CommentOrder.Column');
   if ($Column == $CurrentColumn) {
      $CssClass .= ' OrderBy-'.ucfirst(Gdn::Controller()->Data('CommentOrder.Direction')).' Selected';
   }
   
   return Anchor($Label, $Url, 'FilterButton OrderByButton OrderBy-'.$Column.$CssClass, array('rel' => 'nofollow'));
}

if (!function_exists('ReactionButton')):
   
function ReactionButton($Row, $UrlCode, $Options = array()) {
   $ReactionType = ReactionModel::ReactionTypes($UrlCode);
   
   $Name = $ReactionType['Name'];
   $Label = $Name;
   $SpriteClass = GetValue('SpriteClass', $ReactionType, "React$UrlCode");
   
   if ($ID = GetValue('CommentID', $Row)) {
      $RecordType = 'comment';
   } elseif ($ID = GetValue('ActivityID', $Row)) {
      $RecordType = 'activity';
   } else {
      $RecordType = 'discussion';
      $ID = GetValue('DiscussionID', $Row);
   }
   
   if ($RecordType == 'activity')
      $Count = GetValueR("Data.React.$UrlCode", $Row, 0);
   else
      $Count = GetValueR("Attributes.React.$UrlCode", $Row, 0);
   
   $CountHtml = '';
   $LinkClass = "ReactButton-$UrlCode";
   if ($Count) {
      $CountHtml = ' <span class="Count">'.$Count.'</span>';
      $LinkClass .= ' HasCount';
   }
   
   $UrlCode2 = strtolower($UrlCode);
   $Url = Url("/react/$RecordType/$UrlCode2?id=$ID");
   
   $Result = <<<EOT
<a class="Hijack ReactButton $LinkClass" href="$Url" title="$Label" rel="nofollow"><span class="ReactSprite $SpriteClass"></span> <span class="ReactLabel">$Label</span>$CountHtml</a>
   
EOT;
   
   return $Result;
}

endif;


if (!function_exists('ScoreCssClass')):
   
function ScoreCssClass($Row, $All = FALSE) {
   $Score = GetValue('Score', $Row);
   
   if ($Score < 0)
      $Result = 'Score-Low';
   elseif ($Score > 10)
      $Result = 'Score-High';
   else
      $Result = '';
   
   if ($All)
      return array($Result, 'Score-Low Score-High');
   else
      return $Result;
}

endif;

function WriteOrderByButtons() {
   if (!Gdn::Session()->IsValid())
      return;
   
   echo '<span class="OrderByButtons">'.
      OrderByButton('DateInserted', T('by Date')).
      ' '.
      OrderByButton('Score').
      '</span>';
}


function WriteProfileCounts() {
   $CurrentUrl = Url('', TRUE);
   
   echo '<div class="DataCounts">';
   
   foreach (Gdn::Controller()->Data('Counts', array()) as $Row) {
      $ItemClass = 'CountItem';
      if (StringBeginsWith($CurrentUrl, $Row['Url']))
         $ItemClass .= ' Selected';
      
      echo ' <span class="'.$ItemClass.'">';
      
      if ($Row['Url'])
         echo '<a href="'.htmlspecialchars($Row['Url']).'">';
      
      echo ' <span class="CountTotal">'.Gdn_Format::BigNumber($Row['Total'], 'html').'</span> ';
      echo ' <span class="CountLabel">'.$Row['Name'].'</span>';
      
      if ($Row['Url'])
         echo '</a>';
      
      echo '</span> ';
   }
   
   echo '</div>';
}

if (!function_exists('WriteReactionBar')):
   
function WriteReactionBar($Row) {
   $Attributes = GetValue('Attributes', $Row);
   if (is_string($Attributes)) {
      $Attributes = @unserialize($Attributes);
      SetValue('Attributes', $Row, $Attributes);
   }
   
//   decho($Row, 'Row');
   
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
   
   echo '<div class="Handle ReactHandle" style="display:none"><a href="#"><span class="ReactSprite ReactAgree">&#160;</span> <span class="ReactLabel">React</span></a></div>';
   echo '</div>';
   
   echo '</div>';
}

endif;