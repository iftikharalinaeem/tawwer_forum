<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$Domain = str_replace(array('http://', '/'), array('', ''), C('Garden.Domain', 'your_forum_name.vanillaforums.com'));
$CustomDomain = str_replace(array('http://', '/'), array('', ''), $this->Form->GetValue('CustomDomain', ''));
?>
<h1>Custom Domain Name</h1>
<?php // echo $this->Form->Errors(); ?>
<div class="Info Legal">
   <?php
   if ($this->Form->ErrorCount() > 0) {
   ?>
   <h3>Possible problem with <?php echo $CustomDomain; ?></h3>
   <p>We were unable to verify that <?php echo $CustomDomain; ?> is pointing at VanillaForums.com. Try the troubleshooting steps below to get it going.</p>
   
   <h3>How to set up <?php echo $CustomDomain; ?></h3>
   <ul>
      <li>Create a <strong>C NAME</strong> record for <strong><?php echo $CustomDomain; ?></strong>, pointing at your Vanilla forum's address: <strong><?php echo $Domain; ?></strong> <?php echo Anchor('→ get more help with this step', 'http://vanillaforums.com/help/customdomain'); ?></li>
      <li>Once your C NAME record is set up, come back here and try again. It may take up to 72 hours for your changes to take effect.</li>
      <li>If you need help, contact us at: <?php echo Anchor('support@vanillaforums.com', 'mailto:support@vanillaforums.com?Subject=Custom Domain Name'); ?></li>
   </ul>
   <?php   
   } else {
   ?>
   <p>Custom domains let you use your own domain name for your forum, like <strong>forum.mywebsite.com</strong>.</p>
   <ol>
      <li>First, purchase your own domain name from a registrar like <a href="http://godaddy.com">GoDaddy.com</a> or <a href="http://name.com">Name.com</a>. This should cost about $10 per year, and you can avoid all extra fees for web hosting and other services since you'll be pointing it at us. All you need is a domain name registration for a period of 1 year or more.</li>
      <li>Next, create a <strong>C NAME</strong> record for your domain, pointing at your Vanilla forum's address: <strong><?php echo $Domain; ?></strong>
      <?php echo Anchor('&rarr; get more help with this step', 'http://vanillaforums.com/help/customdomain'); ?></li>
      <li>Enter your custom domain name below and click continue to complete the process.</li>
   </ol>
   <?php
   }
   ?>
</div>
<?php
echo $this->Form->Open();
?>
<ul>
   <li>
      <?php
      echo $this->Form->Label('Your Custom Domain Name', 'CustomDomain');
      echo '<span style="font-size: 120%; padding-right: 6px; display: inline;">http://</span>' . $this->Form->TextBox('CustomDomain');
      if (is_object($this->Site) && $this->Site->Domain != '')
         echo Anchor('Remove Custom Domain', 'settings/customdomain/remove/'.$Session->TransientKey(), 'Button');
      ?>
   </li>
</ul>
<?php
echo $this->Form->Close('Continue →');