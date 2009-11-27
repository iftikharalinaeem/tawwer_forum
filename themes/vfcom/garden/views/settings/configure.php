<?php if (!defined('APPLICATION')) exit();
$this->Form->AddHidden('Garden.Locale', Gdn::Config('Garden.Locale'));
$this->Form->AddHidden('Garden.RewriteUrls', Gdn::Config('Garden.RewriteUrls'));
$this->Form->AddHidden('Garden.Email.UseSmtp', Gdn::Config('Garden.Email.UseSmtp'));
$this->Form->AddHidden('Garden.Email.SmtpHost', Gdn::Config('Garden.Email.SmtpHost'));
$this->Form->AddHidden('Garden.Email.SmtpUser', Gdn::Config('Garden.Email.SmtpUser'));
$this->Form->AddHidden('Garden.Email.SmtpPassword', Gdn::Config('Garden.Email.SmtpPassword'));
$this->Form->AddHidden('Garden.Email.SmtpPort', Gdn::Config('Garden.Email.SmtpPort'));
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<h1><?php echo Gdn::Translate('General Settings'); ?></h1>
<ul>
   <li>
      <?php
         echo $this->Form->Label('Application Title', 'Garden.Title');
         echo $this->Form->TextBox('Garden.Title');
      ?>
   </li>
   <li>
      <div class="Info"><?php echo Translate("Email sent from Garden will be addressed from the following name and address"); ?></div>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Name', 'Garden.Email.SupportName');
         echo $this->Form->TextBox('Garden.Email.SupportName');
      ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Email', 'Garden.Email.SupportAddress');
         echo $this->Form->TextBox('Garden.Email.SupportAddress');
      ?>
   </li>
</ul>
<?php echo $this->Form->Close('Save');
