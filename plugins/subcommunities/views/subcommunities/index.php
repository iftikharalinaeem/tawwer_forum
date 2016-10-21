<?php if (!defined('APPLICATION')) { exit(); } ?>
<?php echo heading($this->data('Title'), sprintf(t('Add %s'), t('Subcommunity')), '/subcommunities/add', 'js-modal btn btn-primary'); ?>
<?php echo $this->form->open(array('action' => url('/subcommunities'))); ?>
<div class="toolbar">
    <div class="toolbar-main">
        <?php
        $placeholder = t('Search subcommunities.', 'Search for subcommunities by the name or slug.');
        echo $this->form->searchForm('search', '/subcommunities', ['placeholder' => $placeholder]);
        ?>
    </div>
    <?php PagerModule::write(array('Sender' => $this, 'View' => 'pager-dashboard')); ?>
</div>


<div id="sites-wrap">
    <?php
    require $this->fetchViewLocation('table');
    ?>
</div>
