<?php if (!defined('APPLICATION')) exit(); ?>

<h2><?php echo T($this->Data('Title')); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
   <ul>
      <li>
         <?php
         echo $this->Form->Label('Reason');
         echo $this->Form->TextBox('Reason', array('MultiLine' => TRUE));
         ?>
      </li>
      <?php
      $this->FireEvent('AfterReportForm');
      ?>
   </ul>
<?php echo $this->Form->Close('Report');