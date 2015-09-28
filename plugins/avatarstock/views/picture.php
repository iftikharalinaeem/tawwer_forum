<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();

// Check that we have the necessary tools to allow image uploading
$AllowImages = Gdn_UploadImage::CanUploadImages();

// Is the photo hosted remotely?
$RemotePhoto = IsUrl($this->User->Photo, 0, 7);

$stock_avatar_payload = $this->Data('_stock_avatar_payload');
$current_stockavatar_id = $this->Data('_current_stockavatar_id');

$crop_dimension_px = C('Garden.Thumbnail.Size') . 'px';
$style_dimensions = 'width:' . $crop_dimension_px .'; height:' . $crop_dimension_px .';';

// For admins who can upload their own images.
$Picture = '';
if ($this->User->Photo != '') {
    if (IsUrl($this->User->Photo))
        $Picture = Img($this->User->Photo, array('class' => 'ProfilePhotoLarge'));
    else
        $Picture = Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
}

// Only admins and users with the custom permission can upload their own avatar.
$customAvatarUploadAllowed = false;
if (CheckPermission('Garden.Settings.Manage')
|| CheckPermission('AvatarPool.CustomUpload.Allow')) {
    $customAvatarUploadAllowed = true;
}

?>
<div class="SmallPopup FormTitleWrapper stockavatar-wrap">
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>

   <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'id' => 'avatarstock-pick-form'));
      echo $this->Form->Errors();
   ?>

   <p><?php echo T('Select one of the following avatars:'); ?></p>
   <ul id="stockavatar-picker">

      <?php foreach ($stock_avatar_payload as $avatar): ?>
         <li class="avatar-option">
            <input class="avatar-option-radio" type="radio" name="AvatarID" id="<?php echo $avatar['AvatarID']; ?>" value="<?php echo $avatar['AvatarID']; ?>" <?php if($current_stockavatar_id===$avatar['AvatarID']) {echo "checked";}?>/>
            <label for="<?php echo $avatar['AvatarID']; ?>"><?php echo Img($avatar['_path_crop'], array('style' => $style_dimensions)); ?></label>
         </li>

      <?php endforeach; ?>

   </ul>



    <?php if ($customAvatarUploadAllowed): ?>

        <ul id="custom-avatar-upload">
            <li>
                <p><?php echo T('Or select an image on your computer (2mb max)'); ?>:</p>
            </li>

            <?php if (!$current_stockavatar_id && $Picture != ''): ?>
                <li class="CurrentPicture">
                    <?php echo $Picture; ?>
                </li>
            <?php endif; ?>

            <li>
                <?php echo $this->Form->Input('Picture', 'file'); ?>
            </li>
        </ul>

    <?php endif; ?>

   <?php
      echo $this->Form->Close('Save Avatar', '', array('class' => 'Button Primary'));
   ?>

    <?php
    if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
        echo Wrap(Anchor(T('Remove Picture'), CombinePaths(array(UserUrl($this->User, '', 'removepicture'), $Session->TransientKey())), 'Button Danger PopConfirm'), 'p', array('class' => 'remove-picture'));
    }
    ?>

</div>
