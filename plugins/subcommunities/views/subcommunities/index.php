<?php if (!defined('APPLICATION')) { exit(); } ?>

<div class="header-block">
    <h1><?php echo $this->data('Title'); ?></h1>
    <div class="btn-group">
        <?php echo anchor(sprintf(t('Add %s'), t('Subcommunity')), '/subcommunities/add', 'js-modal btn btn-primary'); ?>
    </div>
</div>

<?php echo $this->form->open(array('action' => url('/subcommunities'))); ?>
<div class="toolbar">
    <div class="toolbar-main">
        <?php
        echo $this->form->errors();
        echo '<div class="search-wrap input-wrap">';
        echo '<div class="icon-wrap icon-search-wrap">'.dashboardSymbol('search').'</div>';
        echo $this->form->textBox('search', ['class' => 'form-control', 'placeholder' => t('Search subcommunities.', 'Search for subcommunities by the name or slug.')]);
        echo ' ', $this->form->button(t('Go'));
        echo '<a class="icon-wrap icon-clear-wrap" href="'.url('/subcommunities').'">'.dashboardSymbol('close').'</a>';
        echo '</div>';
        ?>
    </div>
    <?php PagerModule::write(array('Sender' => $this, 'View' => 'pager-dashboard')); ?>
</div>

<?php echo $this->form->close(); ?>

<div id="sites-wrap">
    <?php
    require $this->fetchViewLocation('table');
    ?>
</div>
