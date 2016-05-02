<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
$themingNotices = '<br/>'.t('This plugin does not block any request to assets (Images, Scripts, Stylesheets, etc...)');
?>
    <ul>
        <li><?php
            echo wrap(t('Warning'), 'h2');
            if (c('Whitelist.BlockMode', false) === 'HARDCORE') {
                echo wrap(wrap(t('Be sure that you are whitelisted before updating the config or you could end up blocking yourself out of the forum.').$themingNotices, 'strong'), 'span', ['class' => 'WarningMessage']);
            } else {
                echo wrap(wrap(t('This plugin will not block any request needed to serve the login page.').$themingNotices, 'strong'), 'span', ['class' => 'WarningMessage']);
            }

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
