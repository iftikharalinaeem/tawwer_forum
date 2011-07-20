<?php if (!defined('APPLICATION')) exit();
/*
   $UcContext = ucfirst($this->Data['Plugin.Flagging.Data']['Context']);
   $ElementID = $this->Data['Plugin.Flagging.Data']['ElementID'];
   $URL = $this->Data['Plugin.Flagging.Data']['URL'];
   $Title = sprintf("Flag this %s",ucfirst($this->Data['Plugin.Flagging.Data']['Context']));
*/
?>
<script type="text/javascript">
   jQuery(document).ready(function($) {
      setAutoBan = function() {
         var showAutoBan = false;

         // Don't do anything if the warning box is checked
         if ($('#Form_Plugin-dot-Infraction-dot-Warning:checked').val() !== undefined) {
            showAutoBan = false;
         } else {
            // Define the current # of points that the user has
            var activePoints = $('ul.InfractionOverview:last li.Points strong').text()*1;
            if (typeof(activePoints) != 'number')
               activePoints = 0;
            
            // See what # of points has been assigned
            var checkedRadio = $(':radio:checked');
            var newPoints = 0;
            if (checkedRadio.attr('id') == 'Form_Plugin-dot-Infraction-dot-Reason4') {
               newPoints = $('#Form_Plugin-dot-Infraction-dot-Points').val()*1;
            } else {
               newPoints = checkedRadio.parents('ul.InfractionOptions li').find('span.Points').text()*1;
            }
            if (typeof(newPoints) != 'number')
               newPoints = 0;
               
				totalPoints = newPoints + activePoints;
            if (totalPoints >= 6)
               showAutoBan = true;
            
            $('div.AutoBan p').hide();
            if (totalPoints >= 8) {
               $('div.AutoBan p.PermaBan').show();
            } else if (totalPoints >= 6) {
               $('div.AutoBan p.TempBan').show();
            }
         }
         var autoBan = $('div.AutoBan');
         if (showAutoBan && !autoBan.is(':visible'))
            autoBan.slideDown('fast');
         else if (!showAutoBan && autoBan.is(':visible'))
            autoBan.slideUp('fast');
         else if (!showAutoBan)
            autoBan.hide();
      }

      // Based on changes to the form, display/hide autoban form.
      setAutoBan();
      $(':radio').click(function() { setAutoBan(); });
      $('#Form_Plugin-dot-Infraction-dot-Warning').change(function() {
         setAutoBan();
         // Style the points if this is a warning
         if ($(this).is(':checked'))
            $('ul.InfractionOptions li span.Points, ul.InfractionOptions li span.Points input').css('color', '#ddd');
         else
            $('ul.InfractionOptions li span.Points, ul.InfractionOptions li span.Points input').css('color', '#000');
         
      });
      $('#Form_Plugin-dot-Infraction-dot-Points').blur(function() { setAutoBan(); });
      
   });
</script>
<style type="text/css">
   div.Popup form ul.InfractionOptions li label,
   div.Popup form ul li label.CheckBoxLabel {
      font-size: 12px;
      margin: 0;
      padding: 4px 0;
   }
   div.Popup form ul li label.CheckBoxLabel {
      margin: 6px 0;
      border: 2px dashed #ddd;
      border-radius: 6px;
      -moz-border-radius: 6px;
      -webkit-border-radius: 6px;
      background: #ffd;
   }
   li.Headings span {
      font-weight: bold;
      font-size: 14px;
   }
   span.Reason,
   span.Points,
   span.Expires {
      font-size: 12px;
      display: inline-block;
      width: 70px;
      text-align: center;
   }
   span.Reason {
      text-align: left;
      width: 260px;
   }
   span.Expires {
      width: auto;
      text-align: left;
   }
   input.TinyInput {
      width: 20px;
   }
   span.Points input.TinyInput {
      text-align: center;
   }
   input.CustomInput {
      width: 220px;
   }
   ul.InfractionOptions li:nth-child(even) {
      background: #eee;
   }
   ul.InfractionOptions select {
      font-size: 12px;
   }
   div.InfractionPopup h3 {
      background: #EEEEEE;
      border-top: 1px solid #888888;
      border-bottom: 0;
      margin-top: 16px;
      padding: 2px 4px;
   }
   #Popup textarea.TextBox {
      width: 98%;
   }
   input.NoteInput,
   input.BanReasonInput {
      width: 98%;
   }
</style>
<h3>New Infraction</h3>
<ul>
   <li>
      <?php
         echo $this->Form->Checkbox('Plugin.Infraction.Warning', 'Make this a warning (no points assigned)');
      ?>
   </li>
</ul>
<ul class="InfractionOptions">
   <li class="Headings">
      <span class="Reason">Reason</span>
      <span class="Points">Points</span>
      <span class="Expires">Expires</span>
   </li>
   <li>
      <span class="Reason"><?php echo $this->Form->Radio('Plugin.Infraction.Reason', 'Minor Offense', array('value' => 'Minor Offense')); ?></span>
      <span class="Points">2</span>
      <span class="Expires">30 days - <?php echo Gdn_Format::Date(strtotime('+ 30 days')); ?></span>
   </li>
   <li>
      <span class="Reason"><?php echo $this->Form->Radio('Plugin.Infraction.Reason', 'Serious Offense', array('value' => 'Serious Offense')); ?></span>
      <span class="Points">3</span>
      <span class="Expires">60 days - <?php echo Gdn_Format::Date(strtotime('+ 60 days')); ?></span>
   </li>
   <li>
      <span class="Reason"><?php echo $this->Form->Radio('Plugin.Infraction.Reason', 'Alternate Account', array('value' => 'Alternate Account')); ?></span>
      <span class="Points">8</span>
      <span class="Expires">Never</span>
   </li>
   <li>
      <span class="Reason"><?php echo $this->Form->Radio('Plugin.Infraction.Reason', 'Spamming', array('value' => 'Spamming')); ?></span>
      <span class="Points">8</span>
      <span class="Expires">Never</span>
   </li>
   <?php if (Gdn::Session()->CheckPermission('Garden.Admin.Only')) { ?>
   <li>
      <span class="Reason">
         <?php echo $this->Form->Radio('Plugin.Infraction.Reason', '', array('value' => 'Custom')); ?>
         <?php echo $this->Form->Input('Plugin.Infraction.CustomReason', 'text', array('class' => 'CustomInput')); ?>
      </span>
      <span class="Points"><?php echo $this->Form->Input('Plugin.Infraction.Points', 'text', array('class' => 'TinyInput')); ?></span>
      <span class="Expires">
         <?php echo $this->Form->Input('Plugin.Infraction.Expires', 'text', array('class' => 'TinyInput')); ?>
         <?php echo $this->Form->DropDown('Plugin.Infraction.ExpiresRange', array(
            'Hours' => 'Hours',
            'Days' => 'Days',
            'Weeks' => 'Weeks',
            'Months' => 'Months',
            'Never' => 'Never'
         )); ?>
      </span>
   </li>
   <?php } ?>
</ul>
<?php
// Will this infraction cause the user to be auto-banned?
?>
<div class="AutoBan">
   <h3>Automatic Ban</h3>
   <p class="TempBan">Assigning this infraction will cause the user to be temporarily banned.</p>
   <p class="PermaBan">Assigning this infraction will cause the user to be banned permanently.</p>
   <ul>
      <li>
         <?php
            echo $this->Form->Label('Provide a reason for banishment', 'Plugin.Infraction.BanReason');
            echo $this->Form->Input('Plugin.Infraction.BanReason', 'text', array('class' => 'BanReasonInput'));
         ?>
      </li>
   </ul>
</div>
<h3>Message To User</h3>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Administrative Note', 'Plugin.Infraction.Note');
         echo $this->Form->Input('Plugin.Infraction.Note', 'text', array('class' => 'NoteInput'));
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Private Message To User', 'Plugin.Infraction.Message');
         echo $this->Form->TextBox('Plugin.Infraction.Message', array('Multiline' => TRUE));
      ?>
   </li>
</ul>
<?php echo $this->Form->Button('Give Infraction');