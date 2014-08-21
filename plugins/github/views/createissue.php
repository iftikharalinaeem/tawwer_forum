<h1><?php echo T('GitHub Create Issue'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>
    <li>
        <label>Repository</label>
        <select name="Repository">
            <?php echo $this->Data['RepositoryOptions']; ?>
        </select>
    </li>

    <li>
        <?php echo $this->Form->Label('Title', 'Title');  ?>
        <?php echo $this->Form->TextBox('Title'); ?>
    </li>

    <li>
        <?php echo $this->Form->Label('Body', 'Body');  ?>
        <?php echo $this->Form->TextBox('Body', array('MultiLine' => true)); ?>
    </li>


</ul>

<?php echo $this->Form->Close('Create Issue', '', array('class' => 'Button BigButton'));
