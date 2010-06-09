<?php if (!defined('APPLICATION')) exit(); ?>
   <h4><?php echo T('Add People to this Conversation'); ?></h4>

<div class="Box AddPeople">
   <?php
      echo $this->Form->Open();
      echo $this->Form->TextBox('AddPeople', array('MultiLine' => TRUE, 'class' => 'MultiComplete'));
      echo $this->Form->Close('Add');
   ?>
</div>