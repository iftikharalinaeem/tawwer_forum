<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap FormWrapper WarningsForm">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

if (count($this->Data('WarningTypes', array())) <= 1) {
    foreach ($this->Data('WarningTypes', array()) as $Row) {
        echo $this->Form->Hidden('WarningTypeID', array('value' => $Row['WarningTypeID']));
    }
} else {
?>
    <div class="P">
    <?php
    echo $this->Form->Label('Severity', 'WarningTypeID', array('class' => 'B'));

    foreach ($this->Data('WarningTypes', array()) as $Row) {
       $Points = Plural($Row['Points'], '%s point', '%s points');
       if ($Row['ExpireNumber'])
          $Expires = sprintf(T('lasts %s'), Plural($Row['ExpireNumber'], '%s '.rtrim($Row['ExpireType'], 's'), '%s '.$Row['ExpireType']));
       else
          $Expires = '';

       echo '<div class="WarningType">'.
          $this->Form->Radio('WarningTypeID', $Row['Name'], array('value' => $Row['WarningTypeID'])).

          ' <span class="Gloss">'.
          $Points;

       if ($Expires) {
          echo Bullet(' ').
          $Expires;
       }

       echo '</span>'.
          '</div>';
    }
    ?>
    </div>
<?php
}
?>

<div class="P">
<?php
echo $this->Form->Label("Message to User", 'Body', array('class' => 'B'));
echo $this->Form->BodyBox('Body');
?>
</div>

<div class="P">
<?php
echo $this->Form->Label("Private Note for Moderators", 'ModeratorNote', array('class' => 'B'));
echo $this->Form->TextBox('ModeratorNote', array('Wrap' => TRUE));
?>
</div>

<?php if ($this->Data('Record')): ?>
<div class="P">
<?php
echo $this->Form->CheckBox('AttachRecord', '@'.sprintf(T('Attach this warning to the %s.'), T(strtolower($this->Data('RecordType')))));
?>
<?php endif; ?>

</div>
<?php
echo '<div class="Buttons Buttons-Confirm">',
   $this->Form->Button('OK'), ' ',
   $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close')),
   '</div>';
echo $this->Form->Close();
?>
</div>
