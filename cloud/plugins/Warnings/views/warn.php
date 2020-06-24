<?php if (!defined('APPLICATION')) return; ?>
<script>
jQuery(document).ready(function($) {
   $('#Form_ExpireNumber').change(function() {
      $('#Form_ExpireUnit').attr('disabled', $(this).val() == 'never');
   });

   $('.WarningLevels input[name="Level"]').hide();

   $('.WarningLevels input[name="Level"]').bind('change', function() {
      var cval = $('input[name="Level"]:checked').val();
      cval = parseInt(cval);

      var $levels = $('.WarningLevels');
      for (var i = 0; i <= 5; i++) {
         if (i != cval) {
            $levels.removeClass('WarningLevel-'+i);
         } else {
            $levels.addClass('WarningLevel-'+i);
         }
      }

      $('.WarningLevels input[name="Level"]').each(function() {
         var val = $(this).val();
//            console.log(val);
         val = parseInt(val);
         if (val == cval)
            $(this).closest('.WarnBox').addClass('Selected');
         else
            $(this).closest('.WarnBox').removeClass('Selected');
      });
   });
   // Set the highlighted element on page load
   $('.WarnBox').removeClass('Selected');
   $('input[name="Level"]:checked').each(function() {
      $(this).closest('.WarnBox').addClass('Selected');
   });

});
</script>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap FormWrapper WarningsForm">
<?php
echo $this->Form->open();
echo $this->Form->errors();

$CurrentLevel = $this->data('CurrentLevel');
?>
<!--<h2><?php echo t('Set a New Warning Level'); ?></h2>-->

<div class="WarningLevels P <?php echo "WarningLevel-$CurrentLevel"; ?>"><div class="WarningsWrap">
      <div class="WarningBar"></div>
   <?php
   $Special = WarningModel::special();

   for ($i = 0; $i <= $this->data('MaxLevel'); $i++) {
      $CssClass = "Warn-Level$i";

      if ($i == $CurrentLevel) {
         $CssClass .= ' Selected';
      }

      echo '<div class="WarnBox '.$CssClass.'">';


      $Points = $i - $CurrentLevel;

      echo '<div class="WarnLevel">';
      if ($Points >= 0) {
         echo $this->Form->radio('Level', '@'.$i, ['value' => $i]);
      } else {
         echo wrap($i, 'div', ['class' => 'Disabled', 'title' => t('If you want to decrease the warning level then remove a warning.')]);
      }
      echo '</div>';

      if (isset($Special[$i])) {
         $Sp = $Special[$i];
         echo wrap($Sp['Label'], 'div', ['class' => 'Special', 'title' => $Sp['Title']]);
      }

      echo '</div>';
   }
   ?>
</div></div>

<div class="WarningExpireWrap P">
<?php
echo $this->Form->label('How long do you want this warning to last?');
echo ' ';
echo $this->Form->dropDown('ExpireNumber', [
    '1' => '1',
    '2' => '2',
    '3' => '3',
    '4' => '4',
    '5' => '5',
    '6' => '6',
    '7' => '7',
    '8' => '8',
    '9' => '9',
    '10' => '10',
    '20' => '20',
    '30' => '30',
    'never' => t('never')
]);

echo ' ';

echo $this->Form->dropDown('ExpireUnit', [
    'minutes' => t('minutes'),
    'hours' => t('hours'),
    'days' => t('days'),
    'weeks' => t('weeks'),
    'months' => t('months')
]);
?>
</div>

<div class="P">
<?php
echo $this->Form->label("Tell the user why you're warning them", 'Body');
echo $this->Form->textBox('Body', ['Multiline' => TRUE, 'Wrap' => TRUE]);
?>
</div>

<div class="P">
<?php
echo $this->Form->label("Private Note for Moderators", 'ModeratorNote');
echo $this->Form->textBox('ModeratorNote', ['Wrap' => TRUE]);
?>
</div>

<?php
echo '<div class="Buttons Buttons-Confirm">',
   $this->Form->button('OK'), ' ',
   $this->Form->button('Cancel', ['type' => 'button', 'class' => 'Button Close']),
   '</div>';
echo $this->Form->close();
?>
</div>
