<?php if (!defined('APPLICATION')) exit();

if (!function_exists('FormatScore')):

function FormatScore($score) {
   return (int)$score;
}

endif;

function OrderByButton($column, $label = FALSE, $defaultOrder = '', $cssClass = '') {
   $qSParams = $_GET;
   $qSParams['orderby'] = urlencode($column);
   $url = Gdn::Controller()->SelfUrl.'?'.http_build_query($qSParams);
   if (!$label)
      $label = T('by '.$column);

   $cssClass = ' '.$cssClass;
   $currentColumn = Gdn::Controller()->Data('CommentOrder.Column');
   if ($column == $currentColumn) {
      $cssClass .= ' OrderBy-'.ucfirst(Gdn::Controller()->Data('CommentOrder.Direction')).' Selected';
   }

   return Anchor($label, $url, 'FilterButton OrderByButton OrderBy-'.$column.$cssClass, ['rel' => 'nofollow']);
}

function ReactionCount($row, $urlCodes) {
   if ($iD = GetValue('CommentID', $row)) {
      $recordType = 'comment';
   } elseif ($iD = GetValue('ActivityID', $row)) {
      $recordType = 'activity';
   } else {
      $recordType = 'discussion';
      $iD = GetValue('DiscussionID', $row);
   }

   if ($recordType == 'activity')
      $data = GetValueR('Data.React', $row, []);
   else
      $data = GetValueR("Attributes.React", $row, []);

   if (!is_array($data)) {
      return 0;
   }

   $urlCodes = (array)$urlCodes;

   $count = 0;
   foreach ($urlCodes as $urlCode) {
      if (is_array($urlCode))
         $count += GetValue($urlCode['UrlCode'], $data, 0);
      else
         $count += GetValue($urlCode, $data, 0);
   }
   return $count;
}

if (!function_exists('ReactionButton')):

function ReactionButton($row, $urlCode, $options = []) {
   $reactionType = ReactionModel::ReactionTypes($urlCode);

   $isHeading = val('IsHeading', $options, FALSE);
   if (!$reactionType) {
      $reactionType = ['UrlCode' => $urlCode, 'Name' => $urlCode];
      $isHeading = TRUE;
   }

   if (val('Hidden', $reactionType)) {
       return '';
   }

   // Check reaction's permissions
   if ($permissionClass = GetValue('Class', $reactionType)) {
      if (!Gdn::Session()->CheckPermission('Reactions.'.$permissionClass.'.Add'))
         return '';
   }
   if ($permission = GetValue('Permission', $reactionType)) {
      if (!Gdn::Session()->CheckPermission($permission))
         return '';
   }

   $name = $reactionType['Name'];
   $label = T($name);
   $spriteClass = GetValue('SpriteClass', $reactionType, "React$urlCode");

   if ($iD = GetValue('CommentID', $row)) {
      $recordType = 'comment';
   } elseif ($iD = GetValue('ActivityID', $row)) {
      $recordType = 'activity';
   } else {
      $recordType = 'discussion';
      $iD = GetValue('DiscussionID', $row);
   }

   if ($isHeading) {
      static $types = [];
      if (!isset($types[$urlCode]))
         $types[$urlCode] = ReactionModel::GetReactionTypes(['Class' => $urlCode, 'Active' => 1]);

      $count = ReactionCount($row, $types[$urlCode]);
   } else {
      if ($recordType == 'activity')
         $count = GetValueR("Data.React.$urlCode", $row, 0);
      else
         $count = GetValueR("Attributes.React.$urlCode", $row, 0);
   }
   $countHtml = '';
   $linkClass = "ReactButton-$urlCode";
   if ($count) {
      $countHtml = ' <span class="Count">'.$count.'</span>';
      $linkClass .= ' HasCount';
   }
   $linkClass = ConcatSep(' ', $linkClass, GetValue('LinkClass', $options));

   $urlCode2 = strtolower($urlCode);
   if ($isHeading) {
      $url = '#';
      $dataAttr = '';
   } else {
      $url = Url("/react/$recordType/$urlCode2?id=$iD");
      $dataAttr = "data-reaction=\"$urlCode2\"";
   }

   $result = <<<EOT
<a class="Hijack ReactButton $linkClass" href="$url" title="$label" $dataAttr rel="nofollow"><span class="ReactSprite $spriteClass"></span> $countHtml<span class="ReactLabel">$label</span></a>
EOT;

   return $result;
}

endif;


if (!function_exists('ScoreCssClass')):

function ScoreCssClass($row, $all = FALSE) {
   $score = GetValue('Score', $row);
   if (!$score)
      $score = 0;

   $bury = C('Reactions.BuryValue', -5);
   $promote = C('Reactions.PromoteValue', 5);

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
   function WriteImageItem($record, $cssClass = 'Tile ImageWrap') {
      if (val('CategoryCssClass', $record)) {
         $cssClass .= " ".val('CategoryCssClass', $record);
      }
      $attributes = GetValue('Attributes', $record);
      if (!is_array($attributes))
         $attributes = dbdecode($attributes);

      $image = FALSE;
      if (GetValue('Image', $attributes)) {
         $image = [
             'Image' => GetValue('Image', $attributes),
             'Thumbnail' => GetValue('Thumbnail', $attributes, ''),
             'Caption' => GetValue('Caption', $attributes, ''),
             'Size' => GetValue('Size', $attributes, '')
         ];
      }
      $type = FALSE;
      $title = FALSE;
      $body = GetValue('Body', $record, '');

      // A little kludge for my test data where the serialized array was put
      // directly inside the body.
      if (!$image && is_array(dbdecode($body)))
         $image = dbdecode($body);

      $recordID = GetValue('RecordID', $record); // Explicitly defined?
      if ($recordID) {
         $type = $record['RecordType'];
         $name = GetValue('Name', $record);
         $url = GetValue('Url', $record);
         if ($name && $url)
            $title = Wrap(Anchor(Gdn_Format::Text($name), $url), 'h3', ['class' => 'Title']);
      } else {
         $recordID = GetValue('CommentID', $record); // Is it a comment?
         if ($recordID)
            $type = 'Comment';
      }
      if (!$recordID) {
         $recordID = GetValue('DiscussionID', $record); // Is it a discussion?
         if ($recordID)
            $type = 'Discussion';
      }

      $wide = FALSE;
      $formattedBody = Gdn_Format::To($body, $record['Format']);
      if (stripos($formattedBody, '<div class="Video') !== FALSE) {
         $wide = TRUE; // Video?
      } else if (InArrayI($record['Format'], ['Html', 'Text', 'Display']) && strlen($body) > 800) {
         $wide = TRUE; // Lots of text?
      }
      if ($wide)
         $cssClass .= ' Wide';
      ?>
      <div id="<?php echo "{$type}_{$recordID}" ?>" class="<?php echo $cssClass; ?>">
         <?php
         if ($type == 'Discussion' && function_exists('WriteDiscussionOptions'))
            WriteDiscussionOptions();
         elseif ($type == 'Comment' && function_exists('WriteCommentOptions')) {
            $comment = (object)$record;
            WriteCommentOptions($comment);
         }

         if ($title)
            echo $title;

         if ($image) {
            echo '<div class="Image">';
               echo Anchor(Img($image['Thumbnail'], ['alt' => $image['Caption'], 'title' => $image['Caption']]), $image['Image'], ['target' => '_blank']);
            echo '</div>';
            echo '<div class="Caption">';
               echo Gdn_Format::PlainText($image['Caption']);
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
               echo UserPhoto($record, ['Px' => 'Insert']);
               echo UserAnchor($record, ['Px' => 'Insert']);
               ?>
            </span>
            <?php WriteReactions($record); ?>
         </div>
      </div>
      <?php
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


if (!function_exists('WriteProfileCounts')):

function WriteProfileCounts() {
   $currentUrl = Url('', TRUE);

   echo '<div class="DataCounts">';

   foreach (Gdn::Controller()->Data('Counts', []) as $key => $row) {
      $itemClass = 'CountItem';
      if (StringBeginsWith($currentUrl, $row['Url']))
         $itemClass .= ' Selected';

      echo ' <span class="CountItemWrap CountItemWrap-'.$key.'"><span class="'.$itemClass.'">';

      if ($row['Url'])
         echo '<a href="'.htmlspecialchars($row['Url']).'" class="TextColor">';

      echo ' <span class="CountTotal">'.Gdn_Format::BigNumber($row['Total'], 'html').'</span> ';
      echo ' <span class="CountLabel">'.T($row['Name']).'</span>';

      if ($row['Url'])
         echo '</a>';

      echo '</span></span> ';
   }

   echo '</div>';
}

endif;



if (!function_exists('WriteRecordReactions')):

function WriteRecordReactions($row) {
   $userTags = GetValue('UserTags', $row, []);
   if (empty($userTags))
      return;

   $recordReactions = '';
   foreach ($userTags as $tag) {
      $user = Gdn::UserModel()->GetID($tag['UserID'], DATASET_TYPE_ARRAY);
      if (!$user)
         continue;

      $reactionType = ReactionModel::FromTagID($tag['TagID']);
      if (!$reactionType || $reactionType['Hidden'])
         continue;
      $urlCode = $reactionType['UrlCode'];
      $spriteClass = GetValue('SpriteClass', $reactionType, "React$urlCode");
      $title = sprintf('%s - %s on %s', $user['Name'], T($reactionType['Name']), Gdn_Format::DateFull($tag['DateInserted']));

      $userPhoto = UserPhoto($user, ['Size' => 'Small', 'Title' => $title]);
      if ($userPhoto == '')
         continue;

      $recordReactions .= '<span class="UserReactionWrap" title="'.htmlspecialchars($title).'" data-userid="'.GetValue('UserID', $user).'">'
         .$userPhoto
         ."<span class=\"ReactSprite $spriteClass\"></span>"
      .'</span>';
   }

   if ($recordReactions != '')
      echo '<div class="RecordReactions">'.$recordReactions.'</div>';
}

endif;
