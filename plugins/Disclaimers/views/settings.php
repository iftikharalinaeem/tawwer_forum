<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div><?php echo T('Check the categories that require a disclaimer.'); ?></div>
      <?php
         echo $this->Form->CheckBoxList('CategoryIDs', $this->Data('_Categories'), NULL, array('TextField' => 'Name', 'ValueField' => 'CategoryID', 'listclass' => 'ColumnCheckBoxList'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');