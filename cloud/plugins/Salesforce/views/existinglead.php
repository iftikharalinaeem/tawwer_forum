<h2><?php echo t('Salesforce - Add Lead'); ?></h2>

<div><?php echo t('This user is already a Salesforce Lead.'); ?></div>

<div><a href="<?php echo c('Plugins.Salesforce.AuthenticationUrl'); ?>/<?php echo urlencode($this->Data['LeadID']); ?>"
        target="_blank"><?php echo t('View full details at : Salesforce'); ?></a></div>