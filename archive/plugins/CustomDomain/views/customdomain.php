<?php if (!defined('APPLICATION')) exit();
$CustomDomain = str_replace(array('http://', '/'), array('', ''), $this->Form->GetValue('CustomDomain', ''));
$ExistingDomain = $this->Data('Site.Domain', FALSE);
?>
<h1>Custom Domain Name</h1>
<div class="CustomDomain">
   
   <div class="Info">
      <p>Custom domains let you use your own domain name for your forum, like <b>yourforum.com</b> or <b>forum.yourwebsite.com</b>.</p>
   </div>
   
   <div class="DNSInformation">
      <div class="DNSRecords">
         <h2>DNS Information</h2>
         <div><span>Vanilla Hostname</span> <?php echo $this->Data('ForumName'); ?></div>
         <div><span>IP Address</span> <?php echo $this->Data('ClusterAddress'); ?></div>
      </div>
      
      <?php if ($ExistingDomain) { ?>
      <div class="ExistingDomains"><h2>You already have a custom domain</h2><?php 
         echo '<div class="ExistingDomain">';
         echo Anchor("remove", "settings/customdomain/remove/{$ExistingDomain}");
         echo Wrap($ExistingDomain, 'span');
         echo '</div>';
      ?></div>
      <?php } ?>
   </div>
   
   <?php if ($this->Data('Steps')) { ?>
   <ul class="CustomSteps">
      <li><span>1.</span> If you don't have your own domain already, you can buy one from a registrar like <a href="http://godaddy.com">GoDaddy.com</a> or <a href="http://name.com">Name.com</a>.</li>
      <li><span>2.</span> Create a DNS record for your domain so that it points at our servers. There are two choices:
         <div class="MultiOption">
            <div><b>a</b>) If you're trying to use a <b>subdomain</b> (like forum.yourwebsite.com), create a <b>C NAME</b> record for your domain, pointing at your Vanilla address: <b><?php echo $this->Data('ForumName'); ?></b></div>
            <div><b>b</b>) If you want to use a <b>Top Level Domain</b> (like yourforum.com), create an <strong>A Record</strong> for your domain, using the IP Address: <b><?php echo $this->Data('ClusterLoadbalancerAddress'); ?></b></div>
         </div></li>
      <li><span>3.</span> Enter your chosen custom domain name below and click Continue.</li>
   </ul>
   <?php } ?>
   
   <?php 
      if ($this->Data('Attempt', FALSE)) {
         
         // Failed :(
         if ($this->Data('Failed', FALSE)) { ?>
            <div class="CustomFailed"><?php echo $this->Data('ErrorText'); ?></div>
         <?php }
         
         // Success!
         if (!$this->Data('Failed', FALSE)) { ?>
            <div class="CustomSuccess">
               Your domain has been created and configured. You should clear your cookies now, as your forum's cookie domain
               has changed.
            </div>
         <?php }
      }
   ?>
   
   <div class="NewDomain">
      <?php echo $this->Form->Open(); ?>
      <ul>
         <li><h2>New Custom Domain</h2><?php
            echo '<span style="font-size: 120%; padding-right: 6px; display: inline;">http://</span>' . $this->Form->TextBox('CustomDomain');
         ?></li>
      </ul>
      <?php echo $this->Form->Close('Continue →'); ?>
   </div>

</div>

