<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo T('Create a New Forum'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div class="Info"><?php echo T("Enter the name of the new forum you want to create below."); ?></div>
   </li>
   <li>
      <?php
         echo $this->Form->Label('New Forum Name', 'Name');
         echo $this->Form->TextBox('Name', array('style' => 'text-align: right;', 'maxlength' => 50));
         echo '<span style="font-size: 22px; font-weight: bold; color: #02455B;">.vanillaforums.com</span>';
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Create Forum');