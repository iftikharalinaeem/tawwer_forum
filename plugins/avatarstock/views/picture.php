<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::Session();

// Check that we have the necessary tools to allow image uploading
$AllowImages = Gdn_UploadImage::CanUploadImages();

// Is the photo hosted remotely?
/*$RemotePhoto = IsUrl($this->User->Photo, 0, 7);

// Define the current profile picture
$Picture = '';
if ($this->User->Photo != '') {
   if (IsUrl($this->User->Photo))
      $Picture = Img($this->User->Photo, array('class' => 'ProfilePhotoLarge'));
   else
      $Picture = Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')), array('class' => 'ProfilePhotoLarge'));
}

// Define the current thumbnail icon
$Thumbnail = $this->User->Photo;
if (!$Thumbnail && function_exists('UserPhotoDefaultUrl'))
   $Thumbnail = UserPhotoDefaultUrl($this->User);

if ($Thumbnail && !IsUrl($Thumbnail))
   $Thumbnail = Gdn_Upload::Url(ChangeBasename($Thumbnail, 'n%s'));

$Thumbnail = Img($Thumbnail, array('alt' => T('Thumbnail')));
 *
 */

$stock_avatar_payload = $this->Data('_stock_avatar_payload');
$current_stockavatar_id = $this->Data('_current_stockavatar_id');

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
            <label title="<?php echo $avatar['Caption']; ?>" <?php if ($current_stockavatar_id == $avatar['AvatarID']) echo 'class="current-stock-avatar"'; ?>>
               <?php echo Img($avatar['_path']); ?>
               <div class="avatar-caption"><?php echo $avatar['Caption']; ?></div>
               <input type="checkbox" name="AvatarID" value="<?php echo $avatar['AvatarID']; ?>" />
            </label>
         </li>

      <?php endforeach; ?>

   </ul>

   <?php
      echo $this->Form->Close('Save Selection', '', array('class' => 'Button Primary'));
   ?>

</div>