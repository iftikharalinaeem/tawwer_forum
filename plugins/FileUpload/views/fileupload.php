<?php if (!defined('APPLICATION')) exit(); ?>
<ul>
   <li>
      <div class="FileUploadBlock">
         <h1><?php echo T($this->Data['Title']); ?></h1>
         <div class="Info">
            <?php echo T('This plugin enables uploading files and attaching them to discussions and comments.'); ?>
         </div>
      </div>
      <?php
         echo $this->Plugin->Slice('toggle');
      ?>
      
      <div class="FileUploadBlock">
         <h1><?php echo T('Permissions'); ?></h1>
         <div class="Info">
            <?php echo T('Decide which kinds of users are allowed to upload and download files.'); ?>
         </div>
         <div>
         <?php
            //echo $this->Form->CheckBoxGridGroups($this->FileUploadPermissions, 'Permission'); 
         ?>
         </div>
      </div>
   </li>
</ul>