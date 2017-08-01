<h2><?php echo t('Pega - Add Lead'); ?></h2>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<ul>
   <li>
      <?php echo $this->Form->label('First Name', 'FirstName');  ?>
      <?php echo $this->Form->textBox('FirstName'); ?>
   </li>
   <li>
      <?php echo $this->Form->label('Last Name', 'LastName');  ?>
      <?php echo $this->Form->textBox('LastName'); ?>
   </li>
   <li>
      <?php echo $this->Form->label('Email', 'Email');  ?>
      <?php echo $this->Form->textBox('Email'); ?>
   </li>
   <li>
      <?php echo $this->Form->label('Status', 'Status');  ?>
      <select name="Status"><?php echo $this->Data['Data']['Options'] ?></select>
   </li>
   <li>
      <?php echo $this->Form->label('Title', 'Title');  ?>
      <?php echo $this->Form->textBox('Title', ['class' => '']); ?>
   </li>
   <li>
      <?php echo $this->Form->label('Company', 'Company');  ?>
      <?php echo $this->Form->textBox('Company'); ?>
   </li>


</ul>


<div style="width: 400px"></div>
<?php echo $this->Form->close('Add Lead', '', ['class' => 'Button BigButton']);

