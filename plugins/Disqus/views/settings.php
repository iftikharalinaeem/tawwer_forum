<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="PageInfo">
   <?php echo T('The Disqus plugin allows users to sign in using their Disqus account.', 'The Disqus plugin allows users to sign in using their Disqus account. <b>You must register your application with Disqus for this plugin to work.</b>'); ?>
</div>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$Form = $this->Form; //new Gdn_Form();
echo $Form->Simple(array(
    'AuthenticationKey' => array('LabelCode' => 'Consumer Key', 'Options' => array('class' => 'InputBox WideInput')),
    'AssociationSecret' => array('LabelCode' => 'Consumer Secret', 'Options' => array('class' => 'InputBox WideInput'))));

echo $this->Form->Button('Save');
echo $this->Form->Close();
