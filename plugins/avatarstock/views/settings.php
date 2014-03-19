<?php if (!defined('APPLICATION')) exit(); ?>

<?php
   //$username_limits = $this->Data('username_limits');

   $stock_avatar_payload = $this->Data('_payload');

?>

<div id="avatarstock">
   <h1>
      <?php echo $this->Data('Title'); ?>
   </h1>

   <div class="Info">
      Upload a limited stock of photos that members must choose between for
      their avatar.
   </div>

   <h3>Current stock of avatars</h3>

   <div class="display-avatars">
      <?php foreach($stock_avatar_payload as $avatar): ?>

         <div class="avatar-wrap">
            <img src="<?php echo $avatar['Path']; ?>" alt="" title="<?php echo $avatar['Caption']; ?>" />
            <div class="avatar-caption"><?php echo $avatar['Caption']; ?></div>
         </div>

      <?php endforeach; ?>


   </div>

   <h3>Add photos to the stock</h3>

   <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'action' => '/settings/avatarstock/upload', 'id' => 'avatarstock-form'));
      echo $this->Form->Errors();
   ?>

   <ul id="bulk-importer-list">
      <li id="avatar-file-upload">
         <?php
            echo $this->Form->Label('Upload image:', $this->Data('_file_input_name'));
            echo $this->Form->Input($this->Data('_file_input_name'), 'file');
         ?>
      </li>
      <li id="avatar-caption" title="A short (50 chars.) name of the stock avatar.">
         <?php
            echo $this->Form->Label('Avatar caption:', 'avatar_caption');
            echo $this->Form->Input('avatar_caption', 'text', array('maxlength' => 50));
         ?>
      </li>
   </ul>

   <?php echo $this->Form->Close('Start'); ?>
</div>
