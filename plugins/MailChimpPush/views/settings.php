<?php if (!defined('APPLICATION')) exit();
helpAsset(sprintf(t('About %s'), t('MailChimpPush')),
    t('About MailChimpPush', "MailChimp Push synchronizes your users' email addresses with a MailChimp mailing list of 
    your choice. When a new user signs up, or when an existing user changed their email, Vanilla will send a 
    notification to MailChimp to add or update the user.")
);

$configured = $this->data('Configured') ? true : false;

if ($configured):
?>
<div class="header-menu">
   <a class="header-menu-item active" role="heading" aria-level="1" href="<?php echo url('/plugin/mailchimp'); ?>">
      <?php echo t('MailChimp Settings'); ?>
   </a>
   <a class="header-menu-item <?php echo $configured ? '' : 'disabled'; ?>" href="<?php echo url('/plugin/mailchimp/masssync'); ?>">
      <?php echo t('Mass Synchronization'); ?>
   </a>
</div>
<?php else:
   echo heading('MailChimp Settings');
endif; ?>
<div class="MailChimpSettings">
   <?php
   echo $this->Form->open();
   echo $this->Form->errors();
   ?>
   <ul>
      <li class="form-group">
         <div class="label-wrap">
            <?php echo $this->Form->label("API Key", "ApiKey"); ?>
            <div class="info">
               <?php echo Anchor(t('How to find your MailChimp API key'),
                   'http://kb.mailchimp.com/article/where-can-i-find-my-api-key'); ?>
            </div>
         </div>
         <?php echo $this->Form->textBoxWrap('ApiKey');  ?>
      </li>
   </ul>
   <?php if ($configured): ?>
      <ul class="MailingList">
         <li class="form-group">
            <div class="label-wrap">
               <?php echo $this->Form->label("Mailing List", "ListID"); ?>
               <div class="info">
                  <?php echo t('MailChimpPush List Settings', "Choose which list MailChimp will synchronize to when 
                  new users register, or existing ones change their email address."); ?>
               </div>
            </div>
            <div class="input-wrap">
               <?php echo $this->Form->dropDown('ListID', $this->data('Lists'), array('IncludeNull' => TRUE)); ?>
            </div>
         </li>
         <?php
         $interests = $this->data('Interests');
         // Create any dropdowns of interests associated with lists, each dropdown is hidden
         // by javascript unless the list is selected.
         foreach ($interests as $list => $interest) {
            echo "<li id='InterestDropdown{$list}' class='InterestDropdowns form-group'>";
            echo $this->Form->labelWrap("Group", "InterestID");
            echo '<div class="input-wrap">';
            // Disable the interest dropdown by default. Will be activated by javascript if needed.
            echo $this->Form->dropDown('InterestID['.$list.']', $interest, array('IncludeNull' => true,
                'disabled' => true, 'Value' => $this->Form->getValue('InterestID')));
            echo '</div>';
            echo "</li>";
         }
         ?>
         <li class="form-group">
            <div class="input-wrap no-label">
               <?php echo $this->Form->checkBox('ConfirmJoin', 'Send confirmation email?'); ?>
            </div>
         </li>
      </ul>
   <?php endif; ?>
   <?php echo $this->Form->close('Save'); ?>
</div>
