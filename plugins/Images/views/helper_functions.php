<?php if (!defined('APPLICATION')) exit();
if (!function_exists('WriteImageItem')):
   function WriteImageItem($Record, $CssClass = 'Tile ImageWrap') {
      $Attributes = GetValue('Attributes', $Record);
      if (!is_array($Attributes))
         $Attributes = @unserialize($Attributes);
      
      $Image = FALSE;
      if (GetValue('Image', $Attributes)) {
         $Image = array(
             'Image' => GetValue('Image', $Attributes),
             'Thumbnail' => GetValue('Thumbnail', $Attributes, ''),
             'Caption' => GetValue('Caption', $Attributes, ''),
             'Size' => GetValue('Size', $Attributes, '')
         );
      }
      $Type = FALSE;
      $Title = FALSE;
      $Body = GetValue('Body', $Record, '');
      
      // A little kludge for my test data where the serialized array was put 
      // directly inside the body.
      if (!$Image && is_array(@unserialize($Body)))
         $Image = unserialize($Body);
         
      $RecordID = GetValue('RecordID', $Record); // Explicitly defined?
      if ($RecordID) {
         $Type = 'Record';
         $Name = GetValue('Name', $Record);
         $Url = GetValue('Url', $Record);
         if ($Name && $Url)
            $Title = Wrap(Anchor(Gdn_Format::Text($Name), $Url), 'div class="Title"');
      } else {
         $RecordID = GetValue('CommentID', $Record); // Is it a comment?
         if ($RecordID)
            $Type = 'Comment';
      }
      if (!$RecordID) {
         $RecordID = GetValue('DiscussionID', $Record); // Is it a discussion?
         if ($RecordID)
            $Type = 'Discussion';
      }
      
      $Wide = FALSE;
      $FormattedBody = Gdn_Format::To($Body, $Record['Format']);
      if (stripos($FormattedBody, '<div class="Video') !== FALSE) {
         $Wide = TRUE; // Video?
      } else if (InArrayI($Record['Format'], array('Html', 'Text', 'Display')) && strlen($Body) > 800) {
         $Wide = TRUE; // Lots of text?
      }
      if ($Wide) $CssClass .= ' Wide';
      $CssClass .= ' Invisible';
      
      ?>
      <div id="<?php echo "{$Record['Type']}_{$RecordID}" ?>" class="<?php echo $CssClass; ?>">
         <?php
         if ($Type == 'Discussion' && function_exists('WriteDiscussionOptions'))
            WriteDiscussionOptions();
         elseif ($Type == 'Comment' && function_exists('WriteCommentOptions')) {
            $Comment = (object)$Record;
            WriteCommentOptions($Comment);
         }
         
         if ($Title)
            echo $Title;
         
         if ($Image) {
            echo '<div class="Image">';
               echo Anchor(Img($Image['Image'], array('alt' => $Image['Caption'], 'title' => $Image['Caption'])), $Image['Image'], array('target' => '_blank'));
            echo '</div>'; 
            echo '<div class="Caption">';
               echo Gdn_Format::PlainText($Image['Caption']);
            echo '</div>';
         } else {
            echo '<div class="Body">';
               echo $FormattedBody;
            echo '</div>';
         }
         ?>
         <div class="AuthorWrap">
            <span class="Author">
               <?php
               echo UserPhoto($Record, array('Px' => 'Insert'));
               echo UserAnchor($Record, array('Px' => 'Insert'));
               ?>
            </span>
            <?php WriteReactions($Record); ?>
         </div>
      </div>
      <?php
   }
endif;