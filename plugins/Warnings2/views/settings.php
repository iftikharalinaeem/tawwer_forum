<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->Data['Title']); ?></h1>

<div class="Info">
    <?php echo t('Warning types description',
                    'Users are banned when they reach a total of 5 (non expired) points.<br>'
                    .'Editing a warning type will not affect any previous warning.'
                )
    ?>
</div>

<?php
    /* @var SettingsController $this */
    echo $this->Form->open(['class' => 'Warnings Settings', 'enctype' => 'multipart/form-data']);
?>
    <div class="Wrap">
        <?php
            echo $this->Form->errors();

            $durationPeriods = [
                'hours' => 'hours',
                'days' => 'days',
                'weeks' => 'weeks',
                'months' => 'months',
            ];

            echo anchor(t('Add warning type'), '/warningtypes/add', 'Popup SmallButton');
        ?>
    </div>
    <table cellspacing="0" id="WarningsTable">
        <thead>
            <tr>
                <th><?php echo t('Name')?></th>
                <th><?php echo t('Description')?></th>
                <th><?php echo t('Points')?></th>
                <th><?php echo t('Expiration')?></th>
                <th><?php echo t('Options')?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $warnings = $this->data('Warnings');
            foreach($this->data('Warnings') as $warningID => $warning) {
                echo '<tr>';
                echo    '<td>'.htmlentities($warning['Name']).'</td>';
                echo    '<td>'.htmlentities($warning['Description']).'</td>';
                echo    '<td>'.$warning['Points'].'</td>';
                echo    '<td>'.$warning['ExpireNumber'].' '.$warning['ExpireType'].'</td>';
                echo    '<td>'
                            .anchor(t('Edit'), '/warningtypes/edit/'.$warningID, 'Popup SmallButton')
                            .anchor(t('Delete'), '/warningtypes/delete/'.$warningID, 'Popup SmallButton')
                        .'</td>';
                echo "</tr>\n";
            }
            ?>
        </tbody>
    </table>
<?php echo $this->Form->close();
