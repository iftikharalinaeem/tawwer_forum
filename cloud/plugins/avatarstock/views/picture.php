<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::session();

// Check that we have the necessary tools to allow image uploading
$AllowImages = Gdn_UploadImage::canUploadImages();

// Is the photo hosted remotely?
$RemotePhoto = isUrl($this->User->Photo, 0, 7);

$stock_avatar_payload = $this->data('_stock_avatar_payload');
$current_stockavatar_id = $this->data('_current_stockavatar_id');

$crop_dimension_px = c('Garden.Thumbnail.Size') . 'px';
$style_dimensions = 'width:' . $crop_dimension_px .'; height:' . $crop_dimension_px .';';

// For admins who can upload their own images.
$Picture = '';
if ($this->User->Photo != '') {
    if (isUrl($this->User->Photo))
        $Picture = img($this->User->Photo, ['class' => 'ProfilePhotoLarge']);
    else
        $Picture = img(Gdn_Upload::url(changeBasename($this->User->Photo, 'p%s')), ['class' => 'ProfilePhotoLarge']);
}

// Only admins and users with the custom permission can upload their own avatar.
$customAvatarUploadAllowed = false;
if (checkPermission('Garden.Settings.Manage')
|| checkPermission('AvatarPool.CustomUpload.Allow')) {
    $customAvatarUploadAllowed = true;
}

?>
<div class="SmallPopup FormTitleWrapper stockavatar-wrap">
<h1 class="H"><?php echo $this->data('Title'); ?></h1>

   <?php
      echo $this->Form->open(['enctype' => 'multipart/form-data', 'id' => 'avatarstock-pick-form']);
      echo $this->Form->errors();
   ?>

   <p><?php echo t('Select one of the following avatars:'); ?></p>
   <ul id="stockavatar-picker">

      <?php foreach ($stock_avatar_payload as $avatar): ?>
         <li class="avatar-option">
            <input class="avatar-option-radio" type="radio" name="AvatarID" id="<?php echo $avatar['AvatarID']; ?>" value="<?php echo $avatar['AvatarID']; ?>" <?php if($current_stockavatar_id===$avatar['AvatarID']) {echo "checked";}?>/>
            <label for="<?php echo $avatar['AvatarID']; ?>" title="<?php echo Gdn_Format::plainText($avatar['Name']); ?>"><?php echo img($avatar['_path_crop'], ['style' => $style_dimensions]); ?></label>
         </li>

      <?php endforeach; ?>

   </ul>



    <?php if ($customAvatarUploadAllowed): ?>

        <ul id="custom-avatar-upload">
            <li>
                <p><?php echo t('Or select an image on your computer (2mb max)'); ?>:</p>
            </li>

            <?php if (!$current_stockavatar_id && $Picture != ''): ?>
                <li class="CurrentPicture">
                    <?php echo $Picture; ?>
                </li>
            <?php endif; ?>

            <li>
                <?php echo $this->Form->input('Picture', 'file'); ?>
            </li>
        </ul>

    <?php endif; ?>

   <?php
      echo $this->Form->close('Save Avatar', '', ['class' => 'Button Primary']);
   ?>

    <?php
    if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
        echo wrap(anchor(t('Remove Picture'), userUrl($this->User, '', 'removepicture').'?tk='.$Session->transientKey(), 'Button Danger PopConfirm'), 'p', ['class' => 'remove-picture']);
    }
    ?>

</div>
