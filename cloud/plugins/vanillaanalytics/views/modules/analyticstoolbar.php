<?php

$form = new Gdn_Form('', 'bootstrap');
$categories = $this->data('cat01');
$catAttr = $this->data('catAttr');
$heading = $this->data('heading');

if ($this->data('DisplayCategoryFilter')) {
    Gdn_Theme::assetBegin('Help'); ?>
    <section class="control-panel">
        <h2 class="control-panel-heading"><?php echo t('Filter By...'); ?></h2>
        <div class="control-panel-body js-filter-content">
            <?php echo getCategoryFilterHTML($form, $categories, $catAttr, $heading); ?>
        </div>
    </section>
    <?php
    Gdn_Theme::assetEnd();
}
?>
<div class="toolbar-analytics toolbar flex-wrap" id="analytics_toolbar">
    <div class="btn-group">
        <?php foreach($this->data('Intervals') as $interval):
            $attr = [
                'class' => 'js-analytics-interval btn btn-secondary',
                'data-seconds' => val('data-seconds', $interval),
                'data-interval' => strtolower(val('text', $interval))
            ];
            echo wrap(val('text', $interval), 'div', $attr);
        endforeach; ?>
    </div>
    <div class="buttons flex">
        <div class="filter-date">
            <?php echo $form->textBox('dates', ['class' => 'js-date-range date-range form-control']); ?>
        </div>
        <div class="filter-category">
            <?php
            $attr = [
                'id' => 'categoryFilterId',
                'class' => 'btn btn-icon-border js-drop',
                'data-content-id' => 'categoryFilterContent'
            ];

            echo wrap(dashboardSymbol('filter'), 'div', $attr);
            ?>
        </div>
    </div>
</div>
<aside id="categoryFilterContent" aria-hidden="true" class="hidden">
    <div class="card card-category-filter">
        <section class="control-panel padded-left padded-right padded">
            <div class="control-panel-body js-filter-content">
                <?php echo getCategoryFilterHTML($form, $categories, $catAttr, $heading); ?>
            </div>
        </section>
    </div>
</aside>

