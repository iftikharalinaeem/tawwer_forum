<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor('Gigya Sign In Documentation', 'http://docs.vanillaforums.com/addons/gigya/'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo $this->Data('Title'); ?></h1>

<?php
echo $this->Form->Open(), $this->Form->Errors();

$DashboardUrl = 'https://platform.gigya.com/Site/partners/dashboard.aspx';
$LoginUrl = 'https://platform.gigya.com/Site/partners/Plugins.aspx#cmd%3DPlugins.LoginPlugin';

echo $this->Form->Simple([
   'ClientID' => ['LabelCode' => 'API Key', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => sprintf('Enter the api key from your <a href="%s">Gigya dashboard</a>.', $DashboardUrl)],
   'AssociationSecret' => ['LabelCode' => 'Secret', 'Options' => ['Class' => 'InputBox BigInput'], 'Description' => sprintf('Enter your Gigya secret key from your <a href="%s">Gigya dashboard</a>. Hint: look for <i>Show Secret Key</i>.', $DashboardUrl)],
   'HeadTemplate' => ['Options' => ['Multiline' => TRUE], 'Description' => sprintf('Configure your social login on the <a href="%s">Gigya login setup page</a> and paste the head code here.', $LoginUrl)],
   'BodyTemplate' => ['Options' => ['Multiline' => TRUE], 'Description' => 'Paste the body of your social login html here.'],
   'IsDefault' => ['Control' => 'Checkbox', 'LabelCode' => 'Make this connection your default signin method.'],
]);

echo '<div class="Buttons">'.
   $this->Form->Button('Save').
   '</div>';

echo $this->Form->Close();
?>