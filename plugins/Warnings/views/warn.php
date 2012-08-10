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
<h1><?php echo $this->Data('Title'); ?></h1>
<div class="Wrap FormWrappper WarningsForm">
<?php
echo $this->Form->Open();
echo $this->Form->Errors();

$CurrentLevel = $this->Data('CurrentLevel');
?>
<!--<h2><?php echo T('Set a New Warning Level'); ?></h2>-->

<div class="WarningLevels P <?php echo "WarningLevel-$CurrentLevel"; ?>"><div class="WarningsWrap">
      <div class="WarningBar"></div>
   <?php
   $Special = WarningModel::Special();
   
   for ($i = 0; $i <= $this->Data('MaxLevel'); $i++) {
      $CssClass = "Warn-Level$i";
      
      if ($i == $CurrentLevel) {
         $CssClass .= ' Selected';
      }
      
      echo '<div class="WarnBox '.$CssClass.'">';
      
      
      $Points = $i - $CurrentLevel;
      
      echo '<div class="WarnLevel">';
      if ($Points >= 0) {
         echo $this->Form->Radio('Level', '@'.$i, array('value' => $i));
      } else {
         echo Wrap($i, 'div', array('class' => 'Disabled', 'title' => T('If you want to decrease the warning level then remove a warning.')));
      }
      echo '</div>';
      
      if (isset($Special[$i])) {
         $Sp = $Special[$i];
         echo Wrap($Sp['Label'], 'div', array('class' => 'Special', 'title' => $Sp['Title']));
      }
      
      echo '</div>';
   }
   ?>
</div></div>

<div class="WarningExpireWrap P">
<?php
echo $this->Form->Label('How long do you want this warning to last?');
echo ' ';
echo $this->Form->DropDown('ExpireNumber', array(
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
    'never' => T('never')
));

echo ' ';

echo $this->Form->DropDown('ExpireUnit', array(
    'minutes' => T('minutes'),
    'hours' => T('hours'),
    'days' => T('days'),
    'weeks' => T('weeks'),
    'months' => T('months')
));
?>
</div>

<div class="P">
<?php
echo $this->Form->Label("Tell the user why you're warning them", 'Body');
echo $this->Form->TextBox('Body', array('Multiline' => TRUE, 'Wrap' => TRUE));
?>
</div>
   
<div class="P">
<?php
echo $this->Form->Label("Private Note for Moderators", 'ModeratorNote');
echo $this->Form->TextBox('ModeratorNote', array('Wrap' => TRUE));
?>
</div>

<?php
echo '<div class="Buttons Buttons-Confirm">', 
   $this->Form->Button('OK'), ' ',
   $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close')),
   '</div>';
echo $this->Form->Close();
?>
</div>