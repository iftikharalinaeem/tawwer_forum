<?php if (!defined('APPLICATION')) exit(); ?>

<?php echo $this->Form->Open(); ?>

<h1><?php echo T($this->Data['Title']); ?></h1>
<div class="Info">
   <?php echo T('This plugin enables uploading files and attaching them to discussions and comments.'); ?>
</div>
<ul>
   <li>
      <?php
         echo $this->Form->Label("Change Twitter feed handle");
         echo $this->Form->TextBox('Plugin.Twitter.Username');
      ?>
   </li>
</ul>
<?php
   echo $this->Form->Close("Save",'',array(
      'class' => 'SliceSubmit Button'
   ));
?>