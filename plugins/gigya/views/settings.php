<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open(), $this->Form->Errors();

$DashboardUrl = 'https://platform.gigya.com/Site/partners/dashboard.aspx';
$LoginUrl = 'https://platform.gigya.com/Site/partners/Plugins.aspx#cmd%3DPlugins.LoginPlugin';

echo $this->Form->Simple(array(
   'ClientID' => array('LabelCode' => 'API Key', 'Options' => array('Class' => 'InputBox BigInput'), 'Description' => sprintf('Enter the api key from your <a href="%s">Gigya dashboard</a>.', $DashboardUrl)),
   'AssociationSecret' => array('LabelCode' => 'Secret', 'Options' => array('Class' => 'InputBox BigInput'), 'Description' => sprintf('Enter your Gigya secret key from your <a href="%s">Gigya dashboard</a>. Hint: look for <i>Show Secret Key</i>.', $DashboardUrl)),
   'HeaderTemplate' => array('Options' => array('Multiline' => TRUE), 'Description' => sprintf('Configure your social login on the <a href="%s">Gigya login setup page</a> and paste the header code here.', $LoginUrl)),
   'BodyTemplate' => array('Options' => array('Multiline' => TRUE), 'Description' => 'Paste the body of your social login html here.'),
));

echo '<div class="Buttons">'.
   $this->Form->Button('Save').
   '</div>';

echo $this->Form->Close();
?>