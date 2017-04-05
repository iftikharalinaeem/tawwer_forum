<h1><?php echo t('GitHub Create Issue'); ?></h1>

<?php
echo $this->Form->open();
echo $this->Form->errors();
?>

<ul>
    <li>
        <label>Repository</label>
        <select name="Repository">
            <?php echo $this->Data['RepositoryOptions']; ?>
        </select>
    </li>

    <li>
        <?php echo $this->Form->label('Title', 'Title');  ?>
        <?php echo $this->Form->textBox('Title'); ?>
    </li>

    <li>
        <?php echo $this->Form->label('Body', 'Body');  ?>
        <?php echo $this->Form->textBox('Body', array('MultiLine' => true)); ?>
    </li>


</ul>

<?php echo $this->Form->close('Create Issue', '', array('class' => 'Button BigButton'));
