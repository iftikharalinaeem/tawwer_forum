<?php if (!defined('APPLICATION')) exit(); ?>

<?php

   $stock_avatar_payload = $this->Data('_payload');
   $total_stock_avatars = count($stock_avatar_payload);

   $crop_dimension_px = C('Garden.Thumbnail.Size') . 'px';
   $style_dimensions = ' style="width:' . $crop_dimension_px .'; height:' . $crop_dimension_px .';" ';

?>

<h1>
   <?php echo $this->Data('Title'); ?>
</h1>
<div id="avatarstock">
   <div class="padded">
      Upload a stock of photos that members must choose between for their avatar.
   </div>

   <h3>Basic avatars</h3>

   <div class="display-avatars">

      <?php
         echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'action' => url('/settings/avatarstock/modify'), 'id' => 'avatarstock-form-modify'));
         echo $this->Form->Errors();
      ?>

      <?php if ($total_stock_avatars): ?>

         <?php foreach($stock_avatar_payload as $key => $avatar): ?>

            <?php
              $avatarDeleteLabelFor = 'Form_avatar_delete';
              if ($key > 0) {
                  $avatarDeleteLabelFor .= $key;
              }
            ?>

            <label class="avatar-wrap" for="<?php echo $avatarDeleteLabelFor; ?>">
               <img src="<?php echo $avatar['_path_crop']; ?>" alt="" <?php echo $style_dimensions; ?> />
               <?php echo $this->Form->Input('avatar_delete[]', 'checkbox', array('class'=>'avatar-delete-input', 'value'=>$avatar['AvatarID'])); ?>
            </label>

         <?php endforeach; ?>

      <?php endif; ?>

      <?php echo $this->Form->Close(); ?>

      <?php
         echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'action' => Url('/settings/avatarstock/upload'), 'id' => 'avatarstock-form'));
         echo $this->Form->Errors();
      ?>

         <label class="avatar-wrap upload-new-avatar" <?php echo $style_dimensions; ?> title="<?php echo T('Upload an avatar'); ?>">
            <?php
               echo $this->Form->Input($this->Data('_file_input_name'), 'file', array('class' => 'avatar-upload-input'));
            ?>
         </label>

      <?php echo $this->Form->Close(); ?>

      <input type="button" class="Button delete-selected-avatars" value="Delete selected" />

   </div>


</div>
