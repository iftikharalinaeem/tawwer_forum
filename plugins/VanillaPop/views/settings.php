<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo '<li>', Anchor(T('Vanilla Pop Overview'), 'http://vanillaforums.org/docs/vanillapop'), '</li>';
   echo '</ul>';
   ?>
</div>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
$IncomingAddress = $this->Data('IncomingAddress');
//$OutgoingAddress = C();
if ($IncomingAddress):
?>
<div class="Info">
   <p>Your forum's email address is <code><?php echo $IncomingAddress ?></code>.
   If you want to set up your own email address for the site then forward it to this one.
   We recommend using the same account as your outgoing address so that people can reply to email sent by the application.</p>
   
   <p>
      <b>New!</b> You can also set up additional email addresses to forward to individual categories.
      To do this forward email to <code><?php echo $this->Data('CategoryAddress'); ?></code>.
   </p>
</div>
<?php endif; ?>

<?php
$Cf = $this->ConfigurationModule;

$Cf->Render();