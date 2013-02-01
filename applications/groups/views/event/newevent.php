<h1><?php echo T($this->Data['Title']); ?></h1>

<?php 
   echo $this->Form->Errors(); 
   echo $this->Form->Open();
?>

<div class="Event">
   <div class="P">
      <b><?php echo $this->Form->Label('Name of the Event', 'EventName'); ?></b>
      <div><?php echo $this->Form->TextBox('EventName'); ?></div>
   </div>
   
   <div class="P">
      <b><?php echo $this->Form->Label('Event Details', 'EventDetails'); ?></b>
      <div><?php echo $this->Form->BodyBox('Body', array('Table' => 'Event')); ?></div>
   </div>
   
   <div class="P">
      <b><?php echo $this->Form->Label('When', 'EventDate'); ?></b>
      <div><?php echo $this->Form->TextBox('EventDate', array(
         'class'        => 'DatePicker'
      )); ?></div> 
      <div><?php echo $this->Form->TextBox('EventTime', array(
         'placeholder'  => 'Add a time?'
      )); ?></div>
   </div>
   
   <div class="P">
      <b><?php echo $this->Form->Label('Where', 'EventWhere'); ?></b>
      <div><?php echo $this->Form->TextBox('EventWhere'); ?></div>
   </div>
   
   <div class="Buttons">
      <?php echo $this->Form->Button('Create Event', array('Type' => 'submit', 'class' => 'Button Primary')); ?> 
      <?php echo $this->Form->Button('Cancel', array('Type' => 'button', 'class' => 'PopupClose Button CancelButton')); ?>
   </div>
   
</div>
<?php echo $this->Form->Close(); ?>