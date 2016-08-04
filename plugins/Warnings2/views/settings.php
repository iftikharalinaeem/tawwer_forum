<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>

<div class="Info">
    <?php echo t('Warning types description', 'Users are banned when they reach a total of 5 (non expired) points.')?>
</div>

<?php
    /* @var SettingsController $this */
    echo $this->Form->open(['class' => 'RoleTracker Settings', 'enctype' => 'multipart/form-data']);
    echo $this->Form->errors();

    $durationPeriods = [
        'hours' => 'hours',
        'days' => 'days',
        'weeks' => 'weeks',
        'months' => 'months',
    ];
?>

<table cellspacing="0" id="WarningsTable">
    <thead>
        <tr>
            <th><?php echo t('Name')?></th>
            <th><?php echo t('Points')?></th>
            <th><?php echo t('Expiration')?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach($this->data('Warnings') as $warningID => $warning) {
            echo '<tr>';
            echo    '<td>'.$this->Form->input($warningID.'_Name').'</td>';
            echo    '<td>'.$this->Form->input($warningID.'_Points').'</td>';
            echo    '<td>'
                        .$this->Form->input($warningID.'_ExpireNumber')
                        .' '
                        .$this->Form->dropDown(
                            $warningID.'_ExpireType',
                            $durationPeriods,
                            ['Default' => $this->Form->formData()[$warningID.'_ExpireType']]
                        )
                    .'</td>';
            echo "</tr>\n";
        }
        ?>
    </tbody>
</table>
<br/>
<?php echo $this->Form->close('Save');

