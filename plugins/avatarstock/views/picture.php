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

?>
<div class="SmallPopup FormTitleWrapper stockavatar-wrap">
<h1 class="H"><?php echo $this->Data('Title'); ?></h1>

   <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'id' => 'avatarstock-pick-form'));
      echo $this->Form->Errors();
   ?>

   <ul id="stockavatar-picker">

      <?php foreach ($stock_avatar_payload as $avatar): ?>

         <li class="avatar-option">
            <label <?php if ($current_stockavatar_id == $avatar['AvatarID']) echo 'class="current-stock-avatar"'; ?>>
               <?php echo Img($avatar['_path_crop'], array('style' => $style_dimensions)); ?>
               <input type="radio" name="AvatarID" value="<?php echo $avatar['AvatarID']; ?>" />
            </label>
         </li>

      <?php endforeach; ?>

   </ul>

   <?php
      echo $this->Form->Close('Save Selection', '', array('class' => 'Button Primary'));

      if ($this->User->Photo != '' && $AllowImages && !$RemotePhoto) {
         echo Wrap(Anchor(T('Remove Picture'), CombinePaths(array(UserUrl($this->User, '', 'removepicture'), $Session->TransientKey())), 'Button Danger PopConfirm'), 'p');
      }
   ?>

</div>