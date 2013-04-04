<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T($this->Title()); ?></h1>
<div class="AddReaction">
   <ul class="Reaction">
      <li class="Name">
         <?php echo $this->Form->Label('Name'); ?>
         <div><?php echo $this->Form->TextBox('Name'); ?></div>
      </li>

      <li class="UrlCode">
         <?php echo $this->Form->Label('UrlCode'); ?>
         <div><?php echo $this->Form->TextBox('UrlCode'); ?></div>
      </li>

      <li class="Description">
         <?php echo $this->Form->Label('Description'); ?>
         <div><?php echo $this->Form->BodyBox('Description', array('Table' => 'Reaction')); ?></div>
      </li>

      <li class="Class">
         <?php echo $this->Form->Label('Class'); ?>
         <div><?php echo $this->Form->TextBox('Class'); ?></div>
      </li>

      <li class="Points">
         <?php echo $this->Form->Label('Points'); ?>
         <div><?php echo $this->Form->TextBox('Points'); ?></div>
      </li>

   </ul>
   <div class="Buttons">
      <?php echo $this->Form->Button('Save', array('Type' => 'submit', 'class' => 'Button Primary')); ?>
   </div>
   <?php echo $this->Form->Close(); ?>
</div>