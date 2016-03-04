<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
ob_start();
?>
<root>
    <email>user.name@example.com</email>
    <nickname>UserName</nickname>
    <firstName>User</firstName>
    <lastName>Name</lastName>
</root>
<?php
$xmlSample = ob_get_contents();
ob_end_clean();
?>
<ul>
    <li><?php
        echo $this->Form->label(t('Host'), 'Plugins.CASAuthentication.Host');
        echo $this->Form->textbox('Plugins.CASAuthentication.Host');
        ?></li>
    <li><?php
        echo $this->Form->label(t('Context'), 'Plugins.CASAuthentication.Context');
        echo $this->Form->textbox('Plugins.CASAuthentication.Context');
        ?></li>
    <li><?php
        echo $this->Form->label(t('Port'), 'Plugins.CASAuthentication.Port');
        echo $this->Form->textbox('Plugins.CASAuthentication.Port');
        ?></li>
    <li><?php
        echo $this->Form->label(t('Profile URL'), 'Plugins.CASAuthentication.ProfileUrl');
        echo '<span>';
            echo t('An URL which will receive the user\'s email and deserve the following XML document:');
            echo '<pre>'.htmlentities($xmlSample).'</pre>';
            echo t('URL example: http://www.example.com/profile?email=%s (%s will be replaced by the email)');
        echo '</span>';
        echo $this->Form->textbox('Plugins.CASAuthentication.ProfileUrl');
        ?></li>
    <li><?php
        echo $this->Form->label(t('Register URL'), 'Plugins.CASAuthentication.RegisterUrl');
        echo '<span>'.t('URL to your registration page.').'<span>';
        echo $this->Form->textbox('Plugins.CASAuthentication.RegisterUrl');
        ?></li>
</ul>
<?php echo $this->Form->close('Save');
