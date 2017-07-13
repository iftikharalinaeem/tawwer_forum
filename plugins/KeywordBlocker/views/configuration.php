<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li><?php
            echo $this->Form->label(t('Forbidden words'), 'KeywordBlocker.Words');
            echo wrap(t('Separate each word with a semi-colon ";"'), 'p');
            echo $this->Form->textBox('KeywordBlocker.Words', ['MultiLine' => true]);
            ?></li>
    </ul>
<?php echo $this->Form->close('Save');
