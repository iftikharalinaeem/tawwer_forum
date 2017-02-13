<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
if (c('Whitelist.BlockMode', false) === 'HARDCORE') {
    $msg = t('Be sure that you are whitelisted before updating the config or you could end up blocking yourself out of the forum.');
} else {
    $msg = t('This plugin will not block any request needed to serve the login page.');
}
?>
<div class="alert alert-warning padded">
    <strong><?php echo $msg.'<br/>'.t('This plugin does not block any request to assets (Images, Scripts, Stylesheets, etc...)'); ?></strong>
</div>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li><?php
            echo wrap(t('Warning'), 'h2');

            ?><hr></li>
        <li><?php
            echo $this->Form->label('Enabled', 'Whitelist.Active');
            echo $this->Form->checkBox('Whitelist.Active', t('Block any requests from non whitelisted sources'));
            ?></li>
        <li><?php
            echo $this->Form->label(t('IP Whitelist'), 'Whitelist.IPList');
            echo wrap(t('You can use * to allow any number between 0 and 255.'), 'p');
            echo wrap(t('You can use [number]-[number] to allow a range of number. ex. 190-200'), 'p');
            echo wrap(t('Some examples: 192.168.1.*, 192.168.0-1.*, 192.168.0-1.0-50'), 'p');
            echo wrap(wrap(t('Put one IP definition per line.'), 'strong'), 'p');
            echo $this->Form->textBox('Whitelist.IPList', ['MultiLine' => true]);
            ?></li>
    </ul>
<?php echo $this->Form->close('Save');
