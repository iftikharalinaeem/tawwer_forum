<?php if (!defined('APPLICATION')) exit();
$About = ArrayValue(0, $this->RequestArgs, '');
$Domain = str_replace(array('http://', '/'), array('', ''), Gdn::Config('Garden.Domain', 'your_forum_name.vanillaforums.com'));
?>
<h1><?php
if ($About == 'customdomain')
   echo 'Custom Domain Name';
else if ($About == 'adremoval')
   echo 'Ad Removal';
else if ($About == 'singlesignon')
   echo 'Single Sign-on';
else if ($About == 'customcss')
   echo 'Custom CSS';
else if ($About == 'fileuploading')
   echo 'File Uploading';
else if ($About == 'datatransfer')
   echo 'Data Transfer';
else if ($About == 'dailybackups')
   echo 'Daily Backups';
else if ($About == 'ssl')
   echo 'Secure Login';
else 
   echo 'More Info?';
?></h1>
<?php
if ($About == 'customdomain') {
   ?>
   <div class="Legal">
      <p>With the Custom Domain upgrade you'll be able to point your own domain name at your Vanilla forum. Here's how:</p>
      <ol>
         <li>Register a domain name you like using your favourite domain name registrar. We like using <a href="http://godaddy.com">GoDaddy.com</a>. This should cost about $10 per year, and you can avoid all extra fees for web hosting and other services since you'll be pointing it at us. All you need is a domain name registration for a period of 1 year or more.</li>
         <li>Create a <strong>C record</strong> for your domain, pointing at your Vanilla forum's address: <strong><?php echo $Domain; ?></strong>
         <?php echo Anchor('→ get more help with this step', 'http://vanillaforums.com/help#FAQ1'); ?></li>
         <li>Enter your custom domain name below and click continue to complete the process.</li>
      </ol>
   </div>
   <?php
   echo $this->Form->Errors();
   echo $this->Form->Open();
   ?>
   <ul>
      <li>
         <?php
         echo $this->Form->Label('Your Custom Domain Name', 'CustomDomain');
         echo $this->Form->TextBox('CustomDomain');
         ?>
      </li>
   </ul>
   <?php
   echo $this->Form->Close('Continue →');
} else if ($About == 'adremoval') {
   ?>
   <div class="Legal">
      <p>We place Google Adsense advertisements on our forums to help pay the hosting bills. If you don't want those advertisements cluttering up the appearance of your sweet Vanilla forum, soon you will be able to remove them for just a few bucks a month.</p>
      <p>Click the button below to get started.</p>
   </div>
   <?php
   echo $this->Form->Errors();
   echo $this->Form->Open();
   echo $this->Form->Close('No More Ads! →');
} else if ($About == 'singlesignon') {
   ?>
   <div class="Legal">
      <p>With the Single Sign-on upgrade, you can seamlessly integrate our forum with your existing website so that your existing registered users don't need to re-register or sign-in again when they visit the discussion forum.</p>
      <p><strong>Coming Soon!</strong></p>
   </div>
   <?php
} else if ($About == 'customcss') {
   ?>
   <div class="Legal">
      <p>The Custom CSS upgrade allows you to completely change the appearance of your forum using css code. You can change banners, colors, fonts, and the layout of your forum.</p>
      <p>You will be able to test changes before they are applied for everyone to see, and you can quickly and easily revert to previous revisions, or revert right back to our default style. <strong>Please note that the Custom CSS upgrade does not allow you to upload templates or custom themes.</strong></p>
      <p><strong>Coming Soon!</strong></p>
   </div>
   <?php
   /*
   echo $this->Form->Errors();
   echo $this->Form->Open();
   echo $this->Form->Close('Get Started →');
   */
} else if ($About == 'fileuploading') {
   ?>
   <div class="Legal">
      <p>We give you 100mb of disk space for free so that your users can upload profile pictures. With the File Uploading upgrade, you can purchase extra space for your users to upload images and files.</p>
      <p>This upgrade also includes an "Attachments" option with which you can allow your users to upload files and associate them with their discussions and comments. You have total control over who has permission to perform the uploads, and an admin panel where you can monitor how much space you have used and how much is left.</p>
      <p><strong>Coming Soon!</strong></p>
   </div>
   <?php
} else if ($About == 'datatransfer') {
   ?>
   <div class="Legal">
      <p>We know how important your data is, so we want you to be able to get it both in and out of our system quickly and easily. We are currently working on a data transfer interface so you can have total control over your data.</p>
      <p><strong>Coming Soon!</strong></p>
   </div>
   <?php
} else if ($About == 'dailybackups') {
   ?>
   <div class="Legal">
      <p>Most businesses need to know that their data is safely and frequently backed up. We can give you that assurance with the Daily Backup upgrade. You'll be able to review your backups, and restore from any particular backup at any time.</p>
      <p><strong>Coming Soon!</strong></p>
   </div>
   <?php
} else if ($About == 'ssl') {
   ?>
   <div class="Legal">
      <p>The Secure Login upgrade allows you to use our secure socket layer (SSL) certificate when signing into your <?php echo $Domain; ?> domain.</p>
      <p><strong>Coming Soon!</strong></p>
   </div>
   <?php
} else {
   ?>
   <div class="Legal">
      <p>You are looking for more information, but we don't seem to have what you're looking for. Contact us for help! <?php echo Format::Email('support@vanillaforums.com'); ?></p>
   </div>
   <?php
}
?></div>
