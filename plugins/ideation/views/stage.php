<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->label('Name', 'Name'),
            $this->Form->textBox('Name');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->radio('Status', '<strong>Open</strong> '.t("An idea in this stage is open to be voted on."), array('Value' => 'Open', 'Default' => 'Open'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->radio('Status', '<strong>Closed</strong> '.t("An idea in this stage is closed for voting."), array('Value' => 'Closed', 'Default' => 'Open'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Description', 'Description'),
                '<div class="Info2">'.t("An idea in this stage will have this message displayed on its discussion page.").'</div>',
            $this->Form->textBox('Description', array('Multiline' => true));
            ?>
        </li>
    </ul>
<?php
echo '<div class="Buttons">';
echo $this->Form->Button('Save');
echo '</div>';

echo $this->Form->Close();
