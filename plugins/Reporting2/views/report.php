<h2><?php echo t($this->data('Title')); ?></h2>
<?php
$Row = $this->data('Row');
echo formatQuote($Row);
?>
<?php
echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <?php if ($this->data('Reasons')) : ?>
            <li>
                <?php
                $FirstOption = array_shift(array_keys($this->data('Reasons')));
                echo $this->Form->label('@'.t('Report Reason', 'Reason'), 'Reason');
                echo $this->Form->radioList('Reason', $this->data('Reasons'), ['Default' => $FirstOption]);
                ?>
            </li>
        <?php endif; ?>
        <li>
            <?php
            $BodyLabel = $this->data('Reasons') ? '@'.t('Report Notes', 'Notes') : '@'.t('Report Reason', 'Reason');
            echo $this->Form->label($BodyLabel, 'Body');
            echo $this->Form->textBox('Body', ['MultiLine' => true]);
            ?>
        </li>
        <?php
        $this->fireEvent('AfterReportForm');
        ?>
    </ul>
    <div class="Buttons Buttons-Confirm">
<?php
echo $this->Form->button('Send Report');
echo $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
?>
    <div>

<?php echo $this->Form->close();
