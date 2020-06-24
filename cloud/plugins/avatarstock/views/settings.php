<?php if (!defined('APPLICATION')) exit();
/** @var Gdn_Form $form */
$form = $this->Form;

$stock_avatar_payload = $this->data('_payload');
$total_stock_avatars = count($stock_avatar_payload);
$crop_dimension_px = c('Garden.Thumbnail.Size') . 'px';

$permissions = ['AvatarPool.CustomUpload.Allow', 'Garden.Settings.Manage'];
$permissions = implode(t('permissions or', ' or '), $permissions);
$desc = sprintf(t('Users with  the %s permission will be able to upload their own avatars.'), $permissions);
$desc .= ' '.t('Other users will only be able to select from only the following images when changing their profile photo from within Vanilla.');

echo subheading(t('Avatar Pool'), $desc);
echo $form->open([
'enctype' => 'multipart/form-data',
'action' => url('/settings/avatarstock/upload'),
'id' => 'avatarstock-form'
]);
echo $form->errors();
echo $form->input($this->data('_file_input_name'), 'file', ['class' => 'js-new-avatar-pool-upload hidden']);
echo $form->input($this->data('_input_name'), 'text', ['class' => 'js-new-avatar-pool-name hidden', 'value' => '']);
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
                     <label class="avatar-wrap" for="<?php echo $avatarDeleteLabelFor; ?>" title="<?php echo Gdn_Format::plainText($avatar['Name']); ?>">
                        <div class="image-wrap">
                           <img src="<?php echo $avatar['_path_crop']; ?>" <?php echo $style_dimensions; ?> class="label-selector-image"/>
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
   <div class="flex flex-wrap js-new-avatar-pool-name-group hidden">
      <div class="label-wrap">
         <div class="js-new-avatar-pool-filename padded-bottom"></div>
         <label for="Form_Label"><?php echo t('Name the Avatar'); ?>:</label>
         <div class="info"><b>Optional</b>. The name populates the title tag where users choose avatars. </div>
      </div>
      <?php echo $form->input('Upload_Name', 'text', ['class' => 'form-control padded-bottom']); ?>
      <div class="btn btn-primary flex-grow js-new-avatar-pool-save">
         <?php echo(t('Upload & Save')); ?>
      </div>
   </div>
   <div class="btn btn-primary delete-selected-avatars padded-left"><?php echo(t('Delete Selected')); ?></div>
   <div class="btn btn-primary js-new-avatar-pool flex-grow"><?php echo t('Upload New Avatar'); ?></div>
</div>
