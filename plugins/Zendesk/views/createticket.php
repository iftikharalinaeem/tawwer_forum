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
    <p>Complete this form to submit a Ticket to your <a target="_blank" href="<?php echo C('Plugins.Zendesk.Url') ?>">Zendesk</a>
    </p>
    <p>The user who submitted the post will get an email from your Zendesk telling them how to proceed.</p>

    <ul>
        <li>Contact Name: <?php echo htmlspecialchars($ZendeskName); ?></li>
        <li>Email: <?php echo htmlspecialchars($ZendeskEmail); ?></li>

        <li><?php echo $this->Form->Label('Subject Title', 'Title'); ?></li>
        <li><?php echo $this->Form->TextBox('Title'); ?></li>

        <li>
            <?php
            echo $this->Form->Label('Body', 'Body');
            echo $this->Form->TextBox('Body', array('MultiLne' => true));
            ?>
        </li>
    </ul>

    <div style="width: 400px"></div>
<?php echo $this->Form->Close('Create Ticket', '', array('class' => 'Button BigButton'));

