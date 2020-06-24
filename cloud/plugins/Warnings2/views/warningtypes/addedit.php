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
echo $form->open(['class' => 'WarningType']);
echo $form->errors();
?>
    <ul class="padded">
        <li class="form-group">
            <?php
            echo $form->labelWrap('Name', 'Name');
            echo $form->textBoxWrap('Name');
            ?>
        </li>
        <li class="form-group">
            <?php
            echo $form->labelWrap('Description', 'Description');
            echo $form->textBoxWrap('Description');
            ?>
        <li class="form-group">
            <?php
            echo $form->labelWrap('Points', 'Points');
            echo $form->textBoxWrap('Points');
            ?>
        </li>
        <li class="form-group">
            <?php
            $options = [];
            if ($warning) {
                $options['Default'] = val('ExpireType', $warning);
            }

            echo
                $form->labelWrap('Expiration', 'ExpireNumber')
                .' <div class="input-wrap input-wrap-multiple input-wrap-1_3">'
                .$form->textBox('ExpireNumber', ["type" => "number", "min" => "0"])
                .$form->dropDown('ExpireType', $durationPeriods, $options)
                .'</div>';
            ?>
        </li>
    </ul>
<?php echo $form->close('Save');
