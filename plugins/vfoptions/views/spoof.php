<?php if (!defined('APPLICATION')) exit();
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
      echo $this->Form->Label('Email', 'Email');
      echo $this->Form->TextBox('Email');
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Password', 'Password');
      echo $this->Form->Input('Password', 'password');
      ?>
   </li>
</ul>
<?php
echo $this->Form->Close('Go');