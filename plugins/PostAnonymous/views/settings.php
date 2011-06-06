<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <div><?php echo T('Enter the username of the anonymous user.'); ?></div>
      <?php
         echo $this->Form->Label('UserID', 'Username');
         echo $this->Form->TextBox('Username');
      ?>
   </li>
   <li>
      <div><?php echo T('Check the categories that can be posted to anonymously.'); ?></div>
      <?php
         echo $this->Form->CheckBoxList('CategoryIDs', $this->Data('_Categories'), NULL, array('TextField' => 'Name', 'ValueField' => 'CategoryID', 'listclass' => 'ColumnCheckBoxList'));
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');