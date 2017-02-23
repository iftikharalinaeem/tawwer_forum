<h2><?php echo T($this->Data('Title')); ?></h2>
<?php
$Row = $this->Data('Row');
echo FormatQuote($Row);
?>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
    <ul>
        <?php if ($this->Data('Reasons')) : ?>
            <li>
                <?php
                $FirstOption = array_shift(array_keys($this->Data('Reasons')));
                echo $this->Form->Label('@'.T('Report Reason', 'Reason'), 'Reason');
                echo $this->Form->RadioList('Reason', $this->Data('Reasons'), ['Default' => $FirstOption]);
                ?>
            </li>
        <?php endif; ?>
        <li>
            <?php
            $BodyLabel = $this->Data('Reasons') ? '@'.T('Report Notes', 'Notes') : '@'.T('Report Reason', 'Reason');
            echo $this->Form->Label($BodyLabel, 'Body');
            echo $this->Form->TextBox('Body', ['MultiLine' => true]);
            ?>
        </li>
        <?php
        $this->FireEvent('AfterReportForm');
        ?>
    </ul>
    <div class="Buttons Buttons-Confirm">
<?php
echo $this->Form->Button('Send Report');
echo $this->Form->Button('Cancel', ['type' => 'button', 'class' => 'Button Close']);
?>
    <div>

<?php echo $this->Form->Close();
