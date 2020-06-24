<?php if (!defined('APPLICATION')) exit(); ?>

<?php echo $this->Form->open(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo t('This plugin enables uploading files and attaching them to discussions and comments.'); ?>
</div>
<ul>
   <li>
      <?php
         echo $this->Form->label("Change Twitter feed handle");
         echo $this->Form->textBox('Plugin.Twitter.Username');
      ?>
   </li>
</ul>
<?php
   echo $this->Form->close("Save",'',[
      'class' => 'Button'
   ]);
?>
