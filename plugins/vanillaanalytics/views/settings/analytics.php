<?php if (!defined('APPLICATION')) exit(); ?>
<div class="analytics-dashboard-content">
    <h1><?php echo $this->data('Title'); ?></h1>
    <?php
    echo Gdn_Theme::module('AnalyticsToolbarModule');
    ?>
    <div id="analytics_panels">
        <?php foreach ($this->data('AnalyticsDashboard')->getPanels() as $panel): ?>
        <ol class="analytics-panel analytics-panel-<?php echo htmlspecialchars($panel->panelID); ?> Sortable" id="analytics_panel_<?php echo htmlspecialchars($panel->panelID); ?>"></ol>
        <?php endforeach; ?>
    </div>
</div>
