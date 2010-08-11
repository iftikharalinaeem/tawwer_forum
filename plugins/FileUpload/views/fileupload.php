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
         <h3><?php echo T('Permissions'); ?></h3>
         <div class="Info">
            <?php echo T('Define who can upload and manage files on the '.Anchor('Roles & Permissions','/dashboard/role').' page.'); ?>
         </div>
      </div>
   </li>
</ul>