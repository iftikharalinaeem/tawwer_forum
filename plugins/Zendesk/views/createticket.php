<?php if (!defined('APPLICATION')) {
    exit();
} ?>
    <h2><?php echo T('Zendesk'); ?></h2>
<?php
$ZendeskEmail = $this->Data['Data']['InsertEmail'];
$ZendeskName = $this->Data['Data']['InsertName'];

echo $this->Form->Open();
echo $this->Form->Errors();


?>
    <p><?php echo T('Complete this form to submit a Ticket to your'); ?> <a target="_blank" href="<?php echo C('Plugins.Zendesk.Url') ?>">Zendesk</a>
    </p>
    <p><?php echo T('The user who submitted the post will get an email from your Zendesk telling them how to proceed.') ?></p>

    <ul>
        <li><?php echo T('Contact Name') ?>: <?php echo htmlspecialchars($ZendeskName); ?></li>
        <li><?php echo T('Email'); ?>: <?php echo htmlspecialchars($ZendeskEmail); ?></li>

        <li><?php echo $this->Form->Label('Subject Title', 'Title'); ?></li>
        <li><?php echo $this->Form->TextBox('Title'); ?></li>

        <li>
            <?php
            echo $this->Form->Label('Body', 'Body');
            echo $this->Form->TextBox('Body', ['MultiLine' => true]);
            ?>
        </li>
    </ul>

    <div style="width: 400px"></div>
<?php echo $this->Form->Close('Create Ticket', '', ['class' => 'Button BigButton']);
