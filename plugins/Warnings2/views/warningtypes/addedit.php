<?php if (!defined('APPLICATION')) exit; ?>
<h1>
<?php
    /**
     * @var $this WarningTypesController
     * @var $form Gdn_Form
     */
    $form = $this->Form;

    $warning = $this->data('WarningType', false);
    if ($warning) {
        echo t('Edit warning type');
    } else {
        echo t('Add warning type');
    }

    $durationPeriods = [
        'hours' => 'hours',
        'days' => 'days',
        'weeks' => 'weeks',
        'months' => 'months',
    ];
?>
</h1>

<?php
echo $form->open(array('class' => 'WarningType'));
echo $form->errors();
?>
    <ul>
        <li>
            <?php
            echo $form->label('Name', 'Name');
            echo $form->textBox('Name');
            ?>
        </li>
        <li>
            <?php
            echo $form->label('Description', 'Description');
            echo $form->textBox('Description');
            ?>
        <li>
            <?php
            echo $form->label('Points', 'Points');
            echo $form->textBox('Points');
            ?>
        </li>
        <li>
            <?php
            $options = [];
            if ($warning) {
                $options['Default'] = val('ExpireType', $warning);
            }

            echo
                $form->label('Expiration', 'ExpireNumber')
                .$form->textBox('ExpireNumber')
                .' '
                .$form->dropDown('ExpireType', $durationPeriods, $options)
            ?>
        </li>
    </ul>
<?php echo $form->close('Save');
