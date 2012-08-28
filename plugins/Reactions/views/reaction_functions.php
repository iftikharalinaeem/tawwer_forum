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

function ReactionCount($Row, $UrlCodes) {
   if ($ID = GetValue('CommentID', $Row)) {
      $RecordType = 'comment';
   } elseif ($ID = GetValue('ActivityID', $Row)) {
      $RecordType = 'activity';
   } else {
      $RecordType = 'discussion';
      $ID = GetValue('DiscussionID', $Row);
   }
   
   if ($RecordType == 'activity')
      $Data = GetValueR('Data.React', $Row, array());
   else
      $Data = GetValueR("Attributes.React", $Row, array());
   
   if (!is_array($Data)) {
      return 0;
   }
   
   $UrlCodes = (array)$UrlCodes;
   
   $Count = 0;
   foreach ($UrlCodes as $UrlCode) {
      if (is_array($UrlCode))
         $Count += GetValue($UrlCode['UrlCode'], $Data, 0);
      else
         $Count += GetValue($UrlCode, $Data, 0);
   }
   return $Count;
}

if (!function_exists('ReactionButton')):
   
function ReactionButton($Row, $UrlCode, $Options = array()) {
   $ReactionType = ReactionModel::ReactionTypes($UrlCode);
   
   $IsHeading = FALSE;
   if (!$ReactionType) {
      $ReactionType = array('UrlCode' => $UrlCode, 'Name' => $UrlCode);
      $IsHeading = TRUE;
   }
   
   if ($Permission = GetValue('Permission', $ReactionType)) {
      if (!Gdn::Session()->CheckPermission($Permission))
         return '';
   }
   
   $Name = $ReactionType['Name'];
   $Label = T($Name);
   $SpriteClass = GetValue('SpriteClass', $ReactionType, "React$UrlCode");
   
   if ($ID = GetValue('CommentID', $Row)) {
      $RecordType = 'comment';
   } elseif ($ID = GetValue('ActivityID', $Row)) {
      $RecordType = 'activity';
   } else {
      $RecordType = 'discussion';
      $ID = GetValue('DiscussionID', $Row);
   }
   
   if ($IsHeading) {
      static $Types = array();
      if (!isset($Types[$UrlCode]))
         $Types[$UrlCode] = ReactionModel::GetReactionTypes(array('Class' => $UrlCode, 'Active' => 1));
      
      $Count = ReactionCount($Row, $Types[$UrlCode]);
   } else {
      if ($RecordType == 'activity')
         $Count = GetValueR("Data.React.$UrlCode", $Row, 0);
      else
         $Count = GetValueR("Attributes.React.$UrlCode", $Row, 0);  
   }
   $CountHtml = '';
   $LinkClass = "ReactButton-$UrlCode";
   if ($Count) {
      $CountHtml = ' <span class="Count">'.$Count.'</span>';
      $LinkClass .= ' HasCount';
   }
   $LinkClass = ConcatSep(' ', $LinkClass, GetValue('LinkClass', $Options));
   
   $UrlCode2 = strtolower($UrlCode);
   if ($IsHeading)
      $Url = '';
   else
      $Url = Url("/react/$RecordType/$UrlCode2?id=$ID");
   
   $Result = <<<EOT
<a class="Hijack ReactButton $LinkClass" href="$Url" title="$Label" rel="nofollow"><span class="ReactSprite $SpriteClass"></span> $CountHtml<span class="ReactLabel">$Label</span></a>
EOT;
   
   return $Result;
}

endif;


if (!function_exists('ScoreCssClass')):
   
function ScoreCssClass($Row, $All = FALSE) {
   $Score = GetValue('Score', $Row);
   if (!$Score)
      $Score = 0;
   
   $Bury = C('Reactions.BuryValue', -5);
   $Promote = C('Reactions.PromoteValue', 5);
   
   if ($Score <= $Bury)
      $Result = $All ? 'Un-Buried' : 'Buried';
   elseif ($Score >= $Promote)
      $Result = 'Promoted';
   else
      $Result = '';
   
   if ($All)
      return array($Result, 'Promoted Buried Un-Buried');
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
      
      echo ' <span class="CountItemWrap"><span class="'.$ItemClass.'">';
      
      if ($Row['Url'])
         echo '<a href="'.htmlspecialchars($Row['Url']).'" class="TextColor">';
      
      echo ' <span class="CountTotal">'.Gdn_Format::BigNumber($Row['Total'], 'html').'</span> ';
      echo ' <span class="CountLabel">'.$Row['Name'].'</span>';
      
      if ($Row['Url'])
         echo '</a>';
      
      echo '</span></span> ';
   }
   
   echo '</div>';
}

if (!function_exists('WriteReactions')):
function WriteReactions($Row) {
   $Attributes = GetValue('Attributes', $Row);
   if (is_string($Attributes)) {
      $Attributes = @unserialize($Attributes);
      SetValue('Attributes', $Row, $Attributes);
   }
   
   if (C('Plugins.Reactions.ShowUserReactions', TRUE))
      WriteRecordReactions($Row);
   
   echo '<div class="Reactions">';
   
      // Write the flags.
      static $Flags = NULL, $FlagCodes = NULL;
      if ($Flags === NULL) {
         $Flags = ReactionModel::GetReactionTypes(array('Class' => 'Flag', 'Active' => 1));
         $FlagCodes = array();
         foreach ($Flags as $Flag) {
            $FlagCodes[] = $Flag['UrlCode'];
         }
      }

      if (!empty($Flags)) {
         echo '<span class="FlagMenu ToggleFlyout">';
            // Write the handle.
            echo ReactionButton($Row, 'Flag', array('LinkClass' => 'FlyoutButton'));
            echo Sprite('SpFlyoutHandle');
            echo '<ul class="Flyout MenuItems Flags" style="display: none;">';
            foreach ($Flags as $Flag) {
               echo '<li>'.ReactionButton($Row, $Flag['UrlCode']).'</li>';
            }
            echo '</ul>';
         echo '</span>';
         echo Bullet();
      }

      static $Types = NULL;
      if ($Types === NULL)
         $Types = ReactionModel::GetReactionTypes(array('Class' => array('Good', 'Bad'), 'Active' => 1));

      // Write the reactions.
      echo '<span class="ReactMenu">';
         echo '<span class="ReactButtons">';
         foreach ($Types as $Type) {
            echo ReactionButton($Row, $Type['UrlCode']);
         }
         echo '</span>';
      echo '</span>';

      Gdn::Controller()->EventArguments['ReactionTypes'] = $Types;
      Gdn::Controller()->FireEvent('AfterReactions');
   
   echo '</div>';
}

endif;

if (!function_exists('WriteRecordReactions')):

function WriteRecordReactions($Row) {
   $UserTags = GetValue('UserTags', $Row, array());
   if (empty($UserTags))
      return;
   
   $RecordReactions = '';
   foreach ($UserTags as $Tag) {
      $User = Gdn::UserModel()->GetID($Tag['UserID'], DATASET_TYPE_ARRAY);
      if (!$User)
         continue;
      
      $ReactionType = ReactionModel::FromTagID($Tag['TagID']);
      if (!$ReactionType)
         continue;
      $UrlCode = $ReactionType['UrlCode'];
      $SpriteClass = GetValue('SpriteClass', $ReactionType, "React$UrlCode");
      $Title = sprintf('%s - %s on %s', $User['Name'], T($ReactionType['Name']), Gdn_Format::DateFull($Tag['DateInserted']));
      
      $UserPhoto = UserPhoto($User, array('Size' => 'Small', 'Title' => $Title));
      if ($UserPhoto == '')
         continue;
      
      $RecordReactions .= '<span class="UserReactionWrap" title="'.htmlspecialchars($Title).'">'
         .$UserPhoto
         ."<span class=\"ReactSprite $SpriteClass\"></span>"
      .'</span>';
   }
   
   if ($RecordReactions != '')
      echo '<div class="RecordReactions">'.$RecordReactions.'</div>';
}

endif;