<?php if (!defined('APPLICATION')) exit();
/** @var Gdn_Form $form */
$form = $this->Form;

$stock_avatar_payload = $this->data('_payload');
$total_stock_avatars = count($stock_avatar_payload);
$crop_dimension_px = c('Garden.Thumbnail.Size') . 'px';

$permissions = ['AvatarPool.CustomUpload.Allow', 'Garden.Settings.Manage'];
$permissions = implode(' or ', $permissions);
$desc = t('Users with  the '.$permissions.' permission will be able to upload their own avatars.');
$desc .= ' '.t('Other users will only be able to select from only the following images when changing their profile photo from within Vanilla.');

echo subheading($this->data('Title'), $desc);
echo $form->open([
'enctype' => 'multipart/form-data',
'action' => url('/settings/avatarstock/upload'),
'id' => 'avatarstock-form'
]);
echo $form->errors();
echo $form->input($this->data('_file_input_name'), 'file', ['class' => 'js-new-avatar-pool-upload hidden']);
echo $form->close();
?>
      <div id="avatarstock" class="input-wrap">
         <?php
         echo $form->open([
             'enctype' => 'multipart/form-data',
             'action' => url('/settings/avatarstock/modify'),
             'id' => 'avatarstock-form-modify'
         ]);
         echo $form->errors();
         if ($total_stock_avatars) : ?>
            <div class="display-avatars label-selector">
               <?php
               foreach ($stock_avatar_payload as $key => $avatar):
                  $avatarDeleteLabelFor = 'Form_avatar_delete';
                  if ($key > 0) {
                     $avatarDeleteLabelFor .= $key;
                  }
                  ?>
                  <div class="label-selector-item col-xl-2">
                     <?php
                     $selected = '<svg class="icon icon-svg-checkmark" viewBox="0 0 17 17"><use xlink:href="#checkmark" /></svg>';
                     $attr = [
                         'class' => 'avatar-delete-input label-selector-input',
                         'value' => $avatar['AvatarID'],
                         'id' => $avatarDeleteLabelFor
                     ];
                     echo $form->input('avatar_delete[]', 'checkbox', $attr); ?>
                     <label class="avatar-wrap" for="<?php echo $avatarDeleteLabelFor; ?>">
                        <div class="image-wrap">
                           <img src="<?php echo $avatar['_path_crop']; ?>" alt="" <?php echo $style_dimensions; ?> class="label-selector-image"/>
                           <div class="overlay">
                              <div class="buttons">
                                 <a class="btn btn-link"><?php echo t('Select'); ?></a>
                              </div>
                              <div class="selected"><?php echo $selected ?></div>
                           </div>
                        </div>
                     </label>
                  </div>
               <?php endforeach; ?>
            </div>
         <?php endif;
         echo $form->close(); ?>
      </div>
<div class="buttons form-footer">
   <div class="btn btn-primary delete-selected-avatars padded-left"><?php echo(t('Delete Selected')); ?></div>
   <div class="btn btn-primary js-new-avatar-pool flex-grow"><?php echo t('Upload New Avatar'); ?></div>
</div>
