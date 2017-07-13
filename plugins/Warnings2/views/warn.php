<?php if (!defined('APPLICATION')) return; ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap FormWrapper WarningsForm">
<?php
echo $this->Form->open();
echo $this->Form->errors();

$post = $this->data('Record');
if ($post) {
    echo formatQuote($post);
}

if (count($this->data('WarningTypes', [])) <= 1) {
    foreach ($this->data('WarningTypes', []) as $Row) {
        echo $this->Form->hidden('WarningTypeID', ['value' => $Row['WarningTypeID']]);
    }
} else {
?>
    <div class="P">
    <?php
    echo $this->Form->label('Severity', 'WarningTypeID', ['class' => 'B']);

    foreach ($this->data('WarningTypes', []) as $Row) {
       $Points = plural($Row['Points'], '%s point', '%s points');
       if ($Row['ExpireNumber']) {
           $Expires = sprintf(t('lasts %s'), plural($Row['ExpireNumber'], '%s '.rtrim($Row['ExpireType'], 's'), '%s '.$Row['ExpireType']));
       } else {
           $Expires = '';
       }

       echo '<div class="WarningType">'.
          $this->Form->radio('WarningTypeID', $Row['Name'], ['value' => $Row['WarningTypeID']]).

          ' <span class="Gloss">'.
          $Points;

       if ($Expires) {
          echo bullet(' ').
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
echo $this->Form->label("Message to User", 'Body', ['class' => 'B']);
echo $this->Form->bodyBox('Body');
?>
</div>

<div class="P">
<?php
echo $this->Form->label("Private Note for Moderators", 'ModeratorNote', ['class' => 'B']);
echo $this->Form->textBox('ModeratorNote', ['Wrap' => true]);
?>
</div>

<?php if ($this->data('Record')): ?>
<div class="P">
<?php
echo $this->Form->checkBox('AttachRecord', '@'.sprintf(t('Attach this warning to the %s.'), t(strtolower($this->data('RecordType')))));
?>
<?php endif; ?>

</div>
<?php
echo '<div class="Buttons Buttons-Confirm">',
   $this->Form->button('OK'), ' ',
   $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']),
   '</div>';
echo $this->Form->close();
?>
</div>
