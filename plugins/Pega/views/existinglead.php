<h2><?php echo T('Pega - Add Lead'); ?></h2>

<div><?php echo T('This user is already a Pega Lead.'); ?></div>

<div><a href="<?php echo C('Plugins.Pega.AuthenticationUrl'); ?>/<?php echo urlencode($this->Data['LeadID']); ?>"
        target="_blank"><?php echo T('View full details at : Pega'); ?></a></div>