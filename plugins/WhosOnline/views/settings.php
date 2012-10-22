<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo T('Privacy Settings'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Settings');
         echo $this->Form->CheckBox('Plugin.WhosOnline.Invisible','Hide my online status from other members');
      ?>
   </li>

</ul>
<?php echo $this->Form->Close('Save');