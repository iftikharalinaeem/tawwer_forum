<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo T($this->Title()); ?></h1>
<div class="AddReaction">
   <ul class="Reaction">
      <li class="Name row form-group">
         <?php echo $this->Form->labelWrap('Name'); ?>
         <?php echo $this->Form->textBoxWrap('Name'); ?>
      </li>

      <li class="Description row form-group">
         <?php echo $this->Form->labelWrap('Description'); ?>
         <?php echo $this->Form->textBoxWrap('Description', ['Table' => 'Reaction', 'MultiLine' => true]); ?>
      </li>

      <li class="Class row form-group">
         <?php echo $this->Form->labelWrap('Class'); ?>
         <?php echo $this->Form->textBoxWrap('Class'); ?>
      </li>

      <li class="Points row form-group">
         <?php echo $this->Form->labelWrap('Points'); ?>
         <?php echo $this->Form->textBoxWrap('Points'); ?>
      </li>

   </ul>
   <?php echo $this->Form->Close('Save'); ?>
</div>
