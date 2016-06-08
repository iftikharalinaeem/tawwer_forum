<h1><?php echo t($this->Data['Title']); ?></h1>
<?php
/* @var SettingsController $this */
echo $this->Form->open(['class' => 'RoleTracker Settings', 'enctype' => 'multipart/form-data']);
echo $this->Form->errors();
?>
<ul>
    <li><?php echo wrap(t('Roles'), 'h2');?></li>
    <li>
        <table>
            <thead>
            <tr>
                <th><?php echo t('Role\'s name')?></th>
                <th><?php echo t('Is tracked')?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach($this->data('Roles') as $roleID => $role) {
                echo '<tr>';
                echo    '<td>'.$this->Form->label($this->Form->formData()[$roleID.'_Name'], $roleID.'_Name').'</td>';
                echo    '<td>'.$this->Form->checkBox($roleID.'_IsTracked').'</td>';
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
    </li>
</ul>
<?php echo $this->Form->close('Save');
