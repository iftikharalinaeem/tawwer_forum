<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
      echo $this->Form->Label(T('Disclaimer Text'), 'DisclaimerText');
      echo $this->Form->TextBox('DisclaimerText', array('MultiLine' => TRUE));
      ?>
   </li>
   <li>
      <div><?php echo $this->Form->Label(T('Check the categories that require a disclaimer.'), 'CategoryIDs'); ?></div>
      <?php
         echo $this->Form->CheckBoxList('CategoryIDs', $this->Data('_Categories'), NULL, array('TextField' => 'Name', 'ValueField' => 'CategoryID', 'listclass' => 'ColumnCheckBoxList'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');