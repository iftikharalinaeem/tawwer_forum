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
            echo $this->Form->radio('State', '<strong>Open</strong> '.t("An idea in this status is open to be voted on."), array('Value' => 'Open', 'Default' => 'Open'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->radio('State', '<strong>Closed</strong> '.t("An idea in this status is closed for voting."), array('Value' => 'Closed', 'Default' => 'Open'));
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->label('Description', 'Description'),
                '<div class="Info2">'.t("An idea in this status will have this message displayed on its discussion page.").'</div>',
            $this->Form->textBox('Description', array('Multiline' => true));
            ?>
        </li>
        <li>
            <?php
            if (val('IsDefaultStatus', $this->Form->formData())) {
                echo '<strong>'.t('Default Status').'</strong> '.t("This is the starting status for new ideas.");
            } else {
                echo $this->Form->checkbox('IsDefaultStatus', '<strong>'.t('Default Status').'</strong> '.t("Make this the starting status for new ideas."));
            }
            ?>
        </li>
    </ul>
<?php
echo '<div class="Buttons">';
echo $this->Form->Button('Save');
echo '</div>';

echo $this->Form->Close();
