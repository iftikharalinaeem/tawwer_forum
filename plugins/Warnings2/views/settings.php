<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo t($this->data('Title')); ?></h1>

<div class="padded">
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
    <div class="padded">
        <div class="Wrap">
            <?php
                echo $this->Form->errors();

                $durationPeriods = [
                    'hours' => 'hours',
                    'days' => 'days',
                    'weeks' => 'weeks',
                    'months' => 'months',
                ];

                echo '<div class="padded">';
                echo anchor(t('Add warning type'), '/warningtypes/add', 'Popup SmallButton');
                echo '</div>';
            ?>
        </div>
        <table id="WarningsTable" class="table-data js-tj">
            <thead>
                <tr>
                    <th class="column-sm"><?php echo t('Name')?></th>
                    <th class="column-lg"><?php echo t('Description')?></th>
                    <th class="column-sm"><?php echo t('Points')?></th>
                    <th class="column-sm"><?php echo t('Expiration')?></th>
                    <th class="column-md"><?php echo t('Options')?></th>
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
                                . ' &middot; '
                                .anchor(t('Delete'), '/warningtypes/delete/'.$warningID, 'Popup SmallButton')
                            .'</td>';
                    echo "</tr>\n";
                }
                ?>
            </tbody>
        </table>
    </div>
<?php echo $this->Form->close();
