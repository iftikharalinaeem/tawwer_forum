<?php if (!defined('APPLICATION')) {
    exit();
} ?>
    <h2><?php echo t('Zendesk'); ?></h2>
<?php
$ZendeskEmail = $this->Data['Data']['InsertEmail'];
$ZendeskName = $this->Data['Data']['InsertName'];

echo $this->Form->open();
echo $this->Form->errors();


?>
    <p><?php echo t('Complete this form to submit a Ticket to your'); ?> <a target="_blank" href="<?php echo c('Plugins.Zendesk.Url') ?>">Zendesk</a>
    </p>
    <p><?php echo t('The user who submitted the post will get an email from your Zendesk telling them how to proceed.') ?></p>

    <ul>
        <li><?php echo t('Contact Name') ?>: <?php echo htmlspecialchars($ZendeskName); ?></li>
        <li><?php echo t('Email'); ?>: <?php echo htmlspecialchars($ZendeskEmail); ?></li>

        <li><?php echo $this->Form->label('Subject Title', 'Title'); ?></li>
        <li><?php echo $this->Form->textBox('Title'); ?></li>

        <li>
            <?php
            echo $this->Form->label('Body', 'Body');
            echo $this->Form->textBox('Body', ['MultiLine' => true]);
            ?>
        </li>
    </ul>

    <div style="width: 400px"></div>
<?php echo $this->Form->close('Create Ticket', '', ['class' => 'Button BigButton']);
