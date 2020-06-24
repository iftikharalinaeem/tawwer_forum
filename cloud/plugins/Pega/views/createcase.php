<h2><?php echo t('Pega - Create Case'); ?></h2>
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
      <?php echo $this->Form->label('Body', 'Body');  ?>
      <?php echo $this->Form->textBox('Body', ['MultiLine' => true]); ?>
   </li>

</ul>


<div style="width: 400px"></div>
<?php echo $this->Form->close('Create Case', '', ['class' => 'Button BigButton']);

