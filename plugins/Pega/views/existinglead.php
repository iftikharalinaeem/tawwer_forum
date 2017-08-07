<h2><?php echo t('Pega - Add Lead'); ?></h2>

<div><?php echo t('This user is already a Pega Lead.'); ?></div>

<div><a href="<?php echo c('Plugins.Pega.AuthenticationUrl'); ?>/<?php echo urlencode($this->Data['LeadID']); ?>"
        target="_blank"><?php echo t('View full details at : Pega'); ?></a></div>