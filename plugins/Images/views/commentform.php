<?php
$Session = Gdn::session();
?>
<div class="MessageForm CommentForm FormTitleWrapper">
   <h2 class="H"><?php echo t('Leave a Comment'); ?></h2>
   
   <div class="CommentFormWrap">
      <div class="Form-HeaderWrap">
         <div class="Form-Header">
            <span class="Author">
               <?php
               if (c('Vanilla.Comment.UserPhotoFirst', TRUE)) {
                  echo userPhoto($Session->User);
                  echo userAnchor($Session->User, 'Username');
               } else {
                  echo userAnchor($Session->User, 'Username');
                  echo userPhoto($Session->User);
               }
               ?>
            </span>
         </div>
      </div>
      <div class="Form-BodyWrap">
         <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
               <?php
               
               echo $this->Form->open(['enctype' => 'multipart/form-data', 'id' => 'UploadForm', 'action' => url('/post/imagecomment')]);
               writeImageUpload(TRUE);
               echo $this->Form->close();
               
               echo '<div class="TextControlsWrap" style="display: none;">';
               
               echo $this->Form->open();
               echo $this->Form->errors();
//               $CommentOptions = array('MultiLine' => TRUE, 'format' => getValueR('Comment.Format', $this));
               $this->fireEvent('BeforeBodyField');

               echo $this->Form->bodyBox('Body', ['Table' => 'Comment', 'tabindex' => 0]);

               echo '<div class="CommentOptions List Inline">';
//               $this->fireEvent('AfterBodyField');
               echo '</div>';
               
               
               echo "<div class=\"Buttons\">\n";
//               $this->fireEvent('BeforeFormButtons');
               $CancelText = t('Home');
               $CancelClass = 'Back';
//               if (!$NewOrDraft || $Editing) {
//                  $CancelText = t('Cancel');
//                  $CancelClass = 'Cancel';
//               }

               echo '<span class="'.$CancelClass.'">';
               echo anchor($CancelText, '/');

               if ($CategoryID = $this->data('Discussion.CategoryID')) {
                  $Category = CategoryModel::categories($CategoryID);
                  if ($Category)
                     echo ' <span class="Bullet">•</span> '.anchor($Category['Name'], $Category['Url']);
               }

               echo '</span>';

               $ButtonOptions = ['class' => 'Button Primary CommentButton'];
               $ButtonOptions['tabindex'] = 2;
               /*
               Caused non-root users to not be able to add comments. Must take categories
               into account. Look at CheckPermission for more information.
               if (!Gdn::session()->checkPermission('Vanilla.Comment.Add'))
                  $ButtonOptions['Disabled'] = 'disabled';
               */

               if ($Session->isValid()) {
                  echo anchor(t('Preview'), '#', 'PreviewButton')."\n";
                  echo anchor(t('Edit'), '#', 'WriteButton Hidden')."\n";
//                  if ($NewOrDraft)
                     echo anchor(t('Save Draft'), '#', 'DraftButton')."\n";
               }
               if ($Session->isValid())
                  echo $this->Form->button('Post Comment', $ButtonOptions);
               else {
                  $AllowSigninPopup = c('Garden.SignIn.Popup');
                  $Attributes = ['tabindex' => '-1'];
                  if (!$AllowSigninPopup)
                     $Attributes['target'] = '_parent';

                  $AuthenticationUrl = signInUrl($this->data('ForeignUrl', '/'));
                  $CssClass = 'Button Primary Stash';
                  if ($AllowSigninPopup)
                     $CssClass .= ' SignInPopup';

                  echo anchor(t('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
               }

               $this->fireEvent('AfterFormButtons');
               echo "</div>\n";
               echo $this->Form->close();
               
               echo '</div>';
               ?>
            </div>
         </div>
      </div>
   </div>
</div>
