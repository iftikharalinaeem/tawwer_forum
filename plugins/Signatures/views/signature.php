<?php if (!defined('APPLICATION')) exit(); ?>
<h2><?php echo T('Edit My Signature'); ?></h2>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Signature', 'Name');
         echo $this->Form->TextBox('Plugin.Signature.Sig', array('MultiLine' => TRUE));
      ?>
   </li>
   <?php
      $this->FireEvent('EditMySignatureAfter');
   ?>
</ul>
<?php echo $this->Form->Close('Save');