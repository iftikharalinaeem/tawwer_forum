<?php if (!defined('APPLICATION')) exit();

if (!function_exists('FormatScore')):

function formatScore($score) {
   return (int)$score;
}

endif;

function orderByButton($column, $label = FALSE, $defaultOrder = '', $cssClass = '') {
   $qSParams = $_GET;
   $qSParams['orderby'] = urlencode($column);
   $url = Gdn::controller()->SelfUrl.'?'.http_build_query($qSParams);
   if (!$label)
      $label = t('by '.$column);

   $cssClass = ' '.$cssClass;
   $currentColumn = Gdn::controller()->data('CommentOrder.Column');
   if ($column == $currentColumn) {
      $cssClass .= ' OrderBy-'.ucfirst(Gdn::controller()->data('CommentOrder.Direction')).' Selected';
   }

   return anchor($label, $url, 'FilterButton OrderByButton OrderBy-'.$column.$cssClass, ['rel' => 'nofollow']);
}

function reactionCount($row, $urlCodes) {
   if ($iD = getValue('CommentID', $row)) {
      $recordType = 'comment';
   } elseif ($iD = getValue('ActivityID', $row)) {
      $recordType = 'activity';
   } else {
      $recordType = 'discussion';
      $iD = getValue('DiscussionID', $row);
   }

   if ($recordType == 'activity')
      $data = getValueR('Data.React', $row, []);
   else
      $data = getValueR("Attributes.React", $row, []);

   if (!is_array($data)) {
      return 0;
   }

   $urlCodes = (array)$urlCodes;

   $count = 0;
   foreach ($urlCodes as $urlCode) {
      if (is_array($urlCode))
         $count += getValue($urlCode['UrlCode'], $data, 0);
      else
         $count += getValue($urlCode, $data, 0);
   }
   return $count;
}

if (!function_exists('ReactionButton')):

function reactionButton($row, $urlCode, $options = []) {
   $reactionType = ReactionModel::reactionTypes($urlCode);

   $isHeading = val('IsHeading', $options, FALSE);
   if (!$reactionType) {
      $reactionType = ['UrlCode' => $urlCode, 'Name' => $urlCode];
      $isHeading = TRUE;
   }

   if (val('Hidden', $reactionType)) {
       return '';
   }

   // Check reaction's permissions
   if ($permissionClass = getValue('Class', $reactionType)) {
      if (!Gdn::session()->checkPermission('Reactions.'.$permissionClass.'.Add'))
         return '';
   }
   if ($permission = getValue('Permission', $reactionType)) {
      if (!Gdn::session()->checkPermission($permission))
         return '';
   }

   $name = $reactionType['Name'];
   $label = t($name);
   $spriteClass = getValue('SpriteClass', $reactionType, "React$urlCode");

   if ($iD = getValue('CommentID', $row)) {
      $recordType = 'comment';
   } elseif ($iD = getValue('ActivityID', $row)) {
      $recordType = 'activity';
   } else {
      $recordType = 'discussion';
      $iD = getValue('DiscussionID', $row);
   }

   $count = 0;
   $isFlag = $permissionClass === 'Flag' || $urlCode === 'Flag';
   $countDisplay = !$isFlag || c('Reactions.FlagCount.DisplayToUsers', true) || checkPermission('Garden.Moderation.Manage');
   // Don't display counts for Spam or Abuse if you are not a moderator!
   if ($countDisplay) {
       if ($isHeading) {
           static $types = [];
           if (!isset($types[$urlCode]))
               $types[$urlCode] = ReactionModel::getReactionTypes(['Class' => $urlCode, 'Active' => 1]);

           $count = reactionCount($row, $types[$urlCode]);
       } else {
           if ($recordType == 'activity')
               $count = getValueR("Data.React.$urlCode", $row, 0);
           else
               $count = getValueR("Attributes.React.$urlCode", $row, 0);
       }
   }
   $countHtml = '';
   $linkClass = "ReactButton-$urlCode";
   if ($count) {
      $countHtml = ' <span class="Count">'.$count.'</span>';
      $linkClass .= ' HasCount';
   }
   $linkClass = concatSep(' ', $linkClass, getValue('LinkClass', $options));

   $urlCode2 = strtolower($urlCode);
   if ($isHeading) {
      $url = '#';
      $dataAttr = '';
   } else {
      $url = url("/react/$recordType/$urlCode2?id=$iD");
      $dataAttr = "data-reaction=\"$urlCode2\"";
   }

   $result = <<<EOT
<a class="Hijack ReactButton $linkClass" href="$url" tabindex="0" title="$label" $dataAttr rel="nofollow"><span class="ReactSprite $spriteClass"></span> $countHtml<span class="ReactLabel">$label</span></a>
EOT;

   return $result;
}

endif;


if (!function_exists('ScoreCssClass')):

function scoreCssClass($row, $all = FALSE) {
   $score = getValue('Score', $row);
   if (!$score)
      $score = 0;

   $bury = c('Reactions.BuryValue', -5);
   $promote = c('Reactions.PromoteValue', 5);

   if ($score <= $bury)
      $result = $all ? 'Un-Buried' : 'Buried';
   elseif ($score >= $promote)
      $result = 'Promoted';
   else
      $result = '';

   if ($all)
      return [$result, 'Promoted Buried Un-Buried'];
   else
      return $result;
}

endif;

if (!function_exists('WriteImageItem')):
   function writeImageItem($record, $cssClass = 'Tile ImageWrap') {
      if (val('CategoryCssClass', $record)) {
         $cssClass .= " ".val('CategoryCssClass', $record);
      }
      $attributes = getValue('Attributes', $record);
      if (!is_array($attributes))
         $attributes = dbdecode($attributes);

      $image = FALSE;
      if (getValue('Image', $attributes)) {
         $image = [
             'Image' => getValue('Image', $attributes),
             'Thumbnail' => getValue('Thumbnail', $attributes, ''),
             'Caption' => getValue('Caption', $attributes, ''),
             'Size' => getValue('Size', $attributes, '')
         ];
      }
      $type = FALSE;
      $title = FALSE;
      $body = getValue('Body', $record, '');

      $recordID = getValue('RecordID', $record); // Explicitly defined?
      if ($recordID) {
         $type = $record['RecordType'];
         $name = getValue('Name', $record);
         $url = getValue('Url', $record);
         if ($name && $url)
            $title = wrap(anchor(Gdn_Format::text($name), $url), 'h3', ['class' => 'Title']);
      } else {
         $recordID = getValue('CommentID', $record); // Is it a comment?
         if ($recordID)
            $type = 'Comment';
      }
      if (!$recordID) {
         $recordID = getValue('DiscussionID', $record); // Is it a discussion?
         if ($recordID)
            $type = 'Discussion';
      }

      $wide = FALSE;
      $formattedBody = Gdn_Format::to($body, $record['Format']);
      if (stripos($formattedBody, '<div class="Video') !== FALSE) {
         $wide = TRUE; // Video?
      } else if (inArrayI($record['Format'], ['Html', 'Text', 'Display']) && strlen($body) > 800) {
         $wide = TRUE; // Lots of text?
      }
      if ($wide)
         $cssClass .= ' Wide';
      ?>
      <div id="<?php echo "{$type}_{$recordID}" ?>" class="<?php echo $cssClass; ?>">
         <?php
         if ($type == 'Discussion' && function_exists('WriteDiscussionOptions'))
            writeDiscussionOptions();
         elseif ($type == 'Comment' && function_exists('WriteCommentOptions')) {
            $comment = (object)$record;
            writeCommentOptions($comment);
         }

         if ($title)
            echo $title;

         if ($image) {
            echo '<div class="Image">';
               echo anchor(img($image['Thumbnail'], ['alt' => $image['Caption'], 'title' => $image['Caption']]), $image['Image'], ['target' => '_blank']);
            echo '</div>';
            echo '<div class="Caption">';
               echo Gdn_Format::plainText($image['Caption']);
            echo '</div>';
         } else {
            echo '<div class="Body Message">';
               echo $formattedBody;
            echo '</div>';
         }
         ?>
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               echo userPhoto($record, ['Px' => 'Insert']);
               echo userAnchor($record, ['Px' => 'Insert']);
               ?>
            </span>
            <?php writeReactions($record); ?>
         </div>
      </div>
      <?php
   }
endif;

function writeOrderByButtons() {
   if (!Gdn::session()->isValid())
      return;

   echo '<span class="OrderByButtons">'.
      orderByButton('DateInserted', t('by Date')).
      ' '.
      orderByButton('Score').
      '</span>';
}


if (!function_exists('WriteProfileCounts')):

function writeProfileCounts() {
   $currentUrl = url('', TRUE);

   echo '<div class="DataCounts">';

   foreach (Gdn::controller()->data('Counts', []) as $key => $row) {
      $itemClass = 'CountItem';
      if (stringBeginsWith($currentUrl, $row['Url']))
         $itemClass .= ' Selected';

      echo ' <span class="CountItemWrap CountItemWrap-'.$key.'"><span class="'.$itemClass.'">';

      if ($row['Url'])
         echo '<a href="'.htmlspecialchars($row['Url']).'" class="TextColor" rel="nofollow">';

      echo ' <span class="CountTotal">'.Gdn_Format::bigNumber($row['Total'], 'html').'</span> ';
      echo ' <span class="CountLabel">'.t($row['Name']).'</span>';

      if ($row['Url'])
         echo '</a>';

      echo '</span></span> ';
   }

   echo '</div>';
}

endif;



if (!function_exists('WriteRecordReactions')):

function writeRecordReactions($row) {
   $userTags = getValue('UserTags', $row, []);
   if (empty($userTags))
      return;

   $recordReactions = '';
   foreach ($userTags as $tag) {
      $user = Gdn::userModel()->getID($tag['UserID'], DATASET_TYPE_ARRAY);
      if (!$user)
         continue;

      $reactionType = ReactionModel::fromTagID($tag['TagID']);
      if (!$reactionType || $reactionType['Hidden'])
         continue;
      $urlCode = $reactionType['UrlCode'];
      $spriteClass = getValue('SpriteClass', $reactionType, "React$urlCode");
      $title = sprintf('%s - %s on %s', $user['Name'], t($reactionType['Name']), Gdn_Format::dateFull($tag['DateInserted']));

      $userPhoto = userPhoto($user, ['Size' => 'Small', 'Title' => $title]);
      if ($userPhoto == '')
         continue;

      $recordReactions .= '<span class="UserReactionWrap" title="'.htmlspecialchars($title).'" data-userid="'.getValue('UserID', $user).'">'
         .$userPhoto
         ."<span class=\"ReactSprite $spriteClass\"></span>"
      .'</span>';
   }

   if ($recordReactions != '')
      echo '<div class="RecordReactions">'.$recordReactions.'</div>';
}

endif;
