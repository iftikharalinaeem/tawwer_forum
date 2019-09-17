<?php if (!defined('APPLICATION')) { exit(); } ?>
<?php echo heading($this->data('Title'), sprintf(t('Add %s'), t('Subcommunity')), '/subcommunities/add', 'js-modal btn btn-primary'); ?>
<div class="toolbar">
    <div class="toolbar-main">
        <?php
        $placeholder = t('Search subcommunities.', 'Search for subcommunities by the name or slug.');
        echo $this->form->searchForm('search', '/subcommunities', ['placeholder' => $placeholder]);
        ?>
    </div>
    <?php PagerModule::write(['Sender' => $this, 'View' => 'pager-dashboard']); ?>
</div>
<div data-react="product-integration-form-group">
    <!-- Dummy content until the actual thing loads in with react. -->
    <li class="form-group">
        <div class="label-wrap-wide" id="formGroup-1-label"><label for="formGroup-1">Enable Product Integration</label>
            <div class="info">When enabled, you can manage products, and group subcommunities by those products</div>
        </div>
        <div class="input-wrap-right">
            <div class="toggle-wrap"><input id="formGroup-1" type="checkbox" class="toggle-input"><label for="formGroup-1"></label></div>
        </div>
    </li>
</div>

<div id="sites-wrap">
    <?php
    echo $this->fetchView('table');
    ?>
</div>
