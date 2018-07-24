<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();

?>
<h1><?php echo t('Outgoing Email'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="padded"><?php echo T("Email sent from the application will be addressed from the following name and address"); ?></div>
<ul>
   <li class="form-group">
      <?php
         echo $this->Form->labelWrap('Name', 'Garden.Email.SupportName');
         echo $this->Form->textBoxWrap('Garden.Email.SupportName');
      ?>
   </li>
   <li class="form-group">
      <?php
         echo $this->Form->labelWrap('Email', 'Garden.Email.SupportAddress');
         echo $this->Form->textBoxWrap('Garden.Email.SupportAddress');
      ?>
   </li>
</ul>
<?php echo $this->Form->close('Save'); ?>
