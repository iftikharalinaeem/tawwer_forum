<?php if (!defined('APPLICATION')) exit(); ?>
<div id="analytics_toolbar"></div>
<div id="analytics_panels">
    <?php foreach ($this->data('AnalyticsDashboard')->getPanels() as $panel): ?>
    <div class="analytics-panel" id="analytics_panel_<?php echo htmlspecialchars($panel->panelID); ?>"></div>
    <?php endforeach; ?>
</div>
