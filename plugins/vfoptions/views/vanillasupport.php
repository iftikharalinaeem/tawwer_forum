<?php if (!defined('APPLICATION')) exit();
$SupportRequests = GetValue('SupportRequests', $this->Data, 0);
$UsedSupportRequests = 0;
if (is_numeric($SupportRequests)) {
   $UsedSupportRequests = GetValue('UsedSupportRequests', $this->Data);
   if ($UsedSupportRequests > $SupportRequests)
      $UsedSupportRequests = $SupportRequests;
}

$SiteID = C('VanillaForums.SiteID', '0');
$PlanUrl = 'http://vanillaforums.com/'.($SiteID > 0 ? 'account/changeplan/'.$SiteID : 'account');

?>
<h1>Vanilla Support</h1>
<?php if ($this->Form->AuthenticatedPostback() && $this->Form->ErrorCount() == 0) { ?>
   <div class="Info">
      <p class="Green"><strong>Your support request has been sent.</strong> You should receive a notification of your support ticket creation, and we will respond to your request by email.</p>
      <p><small><strong>Note:</strong> We make no guarantees on response times, but you will typically hear back from us within 1 business day.</small></p>
   </div>
<?php
} else {
   echo $this->Form->Open();
   echo $this->Form->Hidden('Browser');
   echo $this->Form->Errors();
   ?>
   <script type="text/javascript">
      $(document).ready(function() {
         $('#Form_Browser').val(navigator.userAgent);
      });
   </script>
   <ul>
      <li>
         <div class="Info">
            <p class="Green"><strong>Important:</strong> <a href="http://vanillaforums.com/help">Visit our FREE customer support forum</a> 24 hours a day, 7 days a week, 365 days a year.</p>
            <p>&nbsp;</p>
            <?php
            if ($SupportRequests === 'Unlimited') { ?>
            Your plan allows you unlimited support requests from the Vanilla team. Use the following form to create your support request.
            <?php } else if ($SupportRequests > 0) { ?>
            <strong>You have used <?php echo $UsedSupportRequests; ?> of the <?php echo $SupportRequests; ?> support requests</strong> alotted for your plan this month.
            To get more support requests from the Vanilla team, <a href="<?php echo $PlanUrl; ?>">upgrade your plan here</a>.
            <?php } else { ?>
            Your plan does not allow for any direct support requests from the Vanilla team.
            If you'd like to get Vanilla support, <a href="<?php echo $PlanUrl; ?>">upgrade your plan here</a>
            <strong><a href="http://vanillaforums.com/help">OR GET HELP ONLINE RIGHT NOW</a></strong>.
            <?php } ?>
         </div>
      </li>
      <?php if ((!is_numeric($SupportRequests) || $SupportRequests > 0) && $UsedSupportRequests < $SupportRequests) { ?>
      <li>
         <?php
            echo $this->Form->Label('Your Email Address', 'FromEmail');
            echo $this->Form->TextBox('FromEmail');
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label('Summarize your problem in 10 words or less', 'Subject');
            echo $this->Form->TextBox('Subject', array('class' => 'InputBox WideInput'));
         ?>
      </li>
      <li>
         <?php
            echo $this->Form->Label("Describe your problem in detail", 'Message');
            echo Wrap('Provide as much detail as possible. Whenever possible, include information like the url <br />of the page where you experienced a problem, data you entered into form inputs, etc.', 'div');
            echo $this->Form->TextBox('Message', array('MultiLine' => TRUE, 'style' => 'height: 300px'));
         ?>
      </li>
   </ul>
   <?php echo $this->Form->Button('Send Support Request');
      }
      echo $this->Form->Close();
}