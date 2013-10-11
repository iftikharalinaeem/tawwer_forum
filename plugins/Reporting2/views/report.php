<?php if (!defined('APPLICATION')) exit(); ?>

<h2><?php echo T($this->Data('Title')); ?></h2>
<?php
   $Row = $this->Data('Row');
   echo FormatQuote($Row);
?>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
   <ul>
      <li>
         <?php
         echo $this->Form->Label('Reason', 'Body');
         echo $this->Form->TextBox('Body', array('MultiLine' => TRUE));
         ?>
      </li>
      <?php
      $this->FireEvent('AfterReportForm');
      ?>
   </ul>
<div class="Buttons Buttons-Confirm">
   <?php
   echo $this->Form->Button('Send Report');
   echo $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
   ?>
<div>

<?php echo $this->Form->Close();