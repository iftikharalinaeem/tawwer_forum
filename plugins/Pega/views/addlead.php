<h2><?php echo T('Pega - Add Lead'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>
   <li>
      <?php echo $this->Form->Label('First Name', 'FirstName');  ?>
      <?php echo $this->Form->TextBox('FirstName'); ?>
   </li>
   <li>
      <?php echo $this->Form->Label('Last Name', 'LastName');  ?>
      <?php echo $this->Form->TextBox('LastName'); ?>
   </li>
   <li>
      <?php echo $this->Form->Label('Email', 'Email');  ?>
      <?php echo $this->Form->TextBox('Email'); ?>
   </li>
   <li>
      <?php echo $this->Form->Label('Status', 'Status');  ?>
      <select name="Status"><?php echo $this->Data['Data']['Options'] ?></select>
   </li>
   <li>
      <?php echo $this->Form->Label('Title', 'Title');  ?>
      <?php echo $this->Form->TextBox('Title', ['class' => '']); ?>
   </li>
   <li>
      <?php echo $this->Form->Label('Company', 'Company');  ?>
      <?php echo $this->Form->TextBox('Company'); ?>
   </li>


</ul>


<div style="width: 400px"></div>
<?php echo $this->Form->Close('Add Lead', '', ['class' => 'Button BigButton']);

