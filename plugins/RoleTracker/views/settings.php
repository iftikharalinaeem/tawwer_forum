<h1><?php echo t($this->Data['Title']); ?></h1>

<div class="Info">
    <p><?php echo t('Tracked roles get extra CSS classes.', 'Discussions and comments made by users with a <b>tracked role</b> will be tagged with that role, and will receive a CSS class to allow visual customization.'); ?></p>
    <p><?php echo t('Tracked roles make content more visible.', 'This is useful for letting regular members know that a staff member has posted in a discussion, for example. <b>This functionality is not retro-active.</b>'); ?></p>
</div>

<?php
    /* @var SettingsController $this */
    echo $this->Form->open(['class' => 'RoleTracker Settings', 'enctype' => 'multipart/form-data']);
    echo $this->Form->errors();
?>

<table cellspacing="0" id="RoleTrackerTable">
    <thead>
        <tr>
            <th><?php echo t('Role Name')?></th>
            <th><?php echo t('Tracked')?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach($this->data('Roles') as $roleID => $role) {
            echo '<tr>';
            echo    '<td>'.$this->Form->label($this->Form->formData()[$roleID.'_Name'], $roleID.'_Name').'</td>';
            echo    '<td>'.$this->Form->checkBox($roleID.'_IsTracked').'</td>';
            echo "</tr>\n";
        }
        ?>
    </tbody>
</table>
<br/>
<?php echo $this->Form->close('Save');
