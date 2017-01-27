<?php if (!defined('APPLICATION')) exit();
/** @var AnalyticsToolbarModule $this */
echo heading($this->data('Title'));
if (!$this->data('HasWidgets') && $this->data('IsPersonal')) : ?>
    <div class="hero">
        <div class="hero-content">
            <div class="hero-title">
                <?php echo t('Your personal analytics section is currently empty!'); ?>
            </div>
            <div class="hero-body">
                <?php echo t('You can tailor this section to see at a glance the metrics that matter to you.'); ?>
                <?php echo sprintf(t('Look for little pins on your other analytics pages that look like this: %s'),
                    dashboardSymbol('pin', 'icon icon-text')).' '.
                    t('Simply click on the pin and that metric will start appearing on this page.'); ?>
            </div>
        </div>
    </div>
<?php else : ?>
<div class="analytics-dashboard-content">
    <?php
    $toolbar = new AnalyticsToolbarModule();
    echo $toolbar->toString();
    ?>
    <div id="analytics_filter_warning" class="alert alert-warning padded">
        <?php echo t('Only showing charts affected by the category filter.'); ?>
    </div>
    <div id="analytics_panels">
        <?php foreach ($this->data('AnalyticsDashboard')->getPanels() as $panel): ?>
        <ul class="analytics-panel analytics-panel-<?php echo htmlspecialchars($panel->panelID);?> " id="analytics_panel_<?php echo htmlspecialchars($panel->panelID); ?>"></ul>
        <?php endforeach; ?>
    </div>
</div>
<?php endif;

// Render the helpAssets after the analytics toolbar module has been rendered, as they should appear after the
// category filter module (which is rendered by the toolbar module).

helpAsset(
    sprintf(t('About %s'), t('Date Picking')),
    t('When you choose a date range, the beginning date is inclusive and the ending date is exclusive. '
        .'This means that if you wanted to do the daily analysis of a week you would have to pick a Monday and the next Monday to have all seven days included.')
);
$widgetDescription = <<<WIDGETDESC
<p>There are 3 different types of widgets:</p>
<ol>
    <li><b>Metrics</b> - A single result compiled from all the data available in the selected time range.</li>
    <li><b>Graphs</b> - Multiple results compiled from all the data available in the selected time range and most of the time split by the selected interval.</li>
    <li><b>Leaderboards</b> - Ranking of some results, ordered from best to worst, taken from all the data available in the selected time range.</li>
</ol>
WIDGETDESC;
helpAsset(
    sprintf(t('About %s'), t('Widgets')),
    t('About widget description', $widgetDescription)
);
helpAsset(
    sprintf(t('About %s'), t('Pinning')),
    t('Quickly access only the info that you need by pinning widgets.').' '
    .sprintf(
        t('Simply click on a pin (&nbsp;%s&nbsp;) to showcase that widget in your "My Analytics" section.'),
        dashboardSymbol('pin', 'icon icon-text')
    )
);
