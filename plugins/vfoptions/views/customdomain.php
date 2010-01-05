<?php if (!defined('APPLICATION')) exit();
$Domain = str_replace(array('http://', '/'), array('', ''), Gdn::Config('Garden.Domain', 'your_forum_name.vanillaforums.com'));
$CustomDomain = str_replace(array('http://', '/'), array('', ''), $this->Form->GetValue('CustomDomain', ''));
?>
<h1>Custom Domain Name</h1>
<?php echo $this->Form->Errors(); ?>
<div class="Legal">
   <?php
   if ($this->Form->ErrorCount() > 0) {
   ?>
   <h3>Possible problem with <?php echo $CustomDomain; ?></h3>
   <p>We were unable to verify that <?php echo $CustomDomain; ?> is pointing at VanillaForums.com. Try the troubleshooting steps below to get it going.</p>
   
   <h3>How to set up <?php echo $CustomDomain; ?></h3>
   <p>Create a <strong>C NAME</strong> record for <strong><?php echo $CustomDomain; ?></strong>, pointing at your Vanilla forum's address: <strong><?php echo $Domain; ?></strong>
      <br /><?php echo Anchor('→ get more help with this step', 'http://vanillaforums.com/help#CNAME'); ?></p>
   
   <p>Once your C NAME record is set up, come back here and try again. It may take several hours for our system to recognize your changes.</p>
   
   <p>If you need help, or if your registrar recommends a different solution, please contact us at <?php echo Anchor('support@vanillaforums.com', 'mailto:support@vanillaforums.com?Subject=Custom Domain Name'); ?>. We will help you get up and running.</p>
   <?php   
   } else {
   ?>
   <p>With the Custom Domain upgrade you'll be able to point your own domain name at your Vanilla forum. Here's how:</p>
   <ol>
      <li>Register a domain name you like using your favourite domain name registrar. We like using <a href="http://godaddy.com">GoDaddy.com</a>. This should cost about $10 per year, and you can avoid all extra fees for web hosting and other services since you'll be pointing it at us. All you need is a domain name registration for a period of 1 year or more.</li>
      <li>Create a <strong>C NAME</strong> record for your domain, pointing at your Vanilla forum's address: <strong><?php echo $Domain; ?></strong>
      <?php echo Anchor('→ get more help with this step', 'http://vanillaforums.com/help#CNAME'); ?></li>
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
      echo '<span style="font-size: 120%; padding-right: 6px;">http://</span>' . $this->Form->TextBox('CustomDomain');
      ?>
   </li>
</ul>
<?php
echo $this->Form->Close('Continue →');