<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo $this->Data('Title'); ?></h1>
    <div class="buttons">
        <?php echo anchor(t('Add Site'), '/multisites/add', 'btn btn-primary js-modal'); ?>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-main">
        <?php
        echo $this->form->open(['action' => url('/multisites')]);
        echo $this->form->errors();
        echo '<div class="search-wrap input-wrap">';
        echo '<div class="search-icon-wrap search-icon-search-wrap">'.dashboardSymbol('search').'</div>';
        echo $this->form->textBox('search', ['placeholder' => t('Search sites', 'Search for sites by the name of the site or its url'), 'class' => 'form-control']);
        echo ' ', $this->form->button(t('Go'), ['name' => 'go', 'class' => 'search-submit']);
        echo '<a class="search-icon-wrap search-icon-clear-wrap" href="'.url('/multisites').'">'.dashboardSymbol('close').'</a>';
        echo '</div>';
        echo $this->form->close();
        ?>
    </div>
    <?php PagerModule::write(array('Sender' => $this, 'View' => 'pager-dashboard')); ?>
</div>

<div id="multisites-wrap">
<?php
require $this->FetchViewLocation('table');
?>
</div>

<div class="form-group">
    <div class="label-wrap-wide">
        <div class="label"><?php echo t('The sites are synchronized with the hub roughly every 10 minutes.'); ?></div>
    </div>
    <div class="input-wrap-right">
        <?php echo anchor(T('Sync Now'), '/multisites/syncnode.json', 'btn btn-primary Hijack'); ?>
    </div>
</div>
