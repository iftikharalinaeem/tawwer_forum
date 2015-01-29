<h2><?php echo T('Salesforce - Add Lead'); ?></h2>

<div><?php echo T('This user is already a Salesforce Lead.'); ?></div>

<div><a href="<?php echo C('Plugins.Salesforce.AuthenticationUrl'); ?>/<?php echo urlencode($this->Data['LeadID']); ?>"
        target="_blank"><?php echo T('View full details at : Salesforce'); ?></a></div>