<?php if (!defined('APPLICATION')) exit(); ?>
<h1>Custom CSS</h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
      echo $this->Form->TextBox('CustomCSS', array('MultiLine' => TRUE, 'class' => 'TextBox Autogrow', 'style' => 'width: 100%; max-height: none; font-family: monospace; font-size: 12px; color: #000;'));
      ?>
   </li>
</ul>
<?php
echo $this->Form->Button('Save');
echo $this->Form->Button('Preview');
echo $this->Form->Close();