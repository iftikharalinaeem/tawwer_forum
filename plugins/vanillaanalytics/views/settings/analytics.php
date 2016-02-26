<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo Gdn_Theme::module('AnalyticsToolbarModule');
?>
<div id="analytics_panels">
    <?php foreach ($this->data('AnalyticsDashboard')->getPanels() as $panel): ?>
    <div class="analytics-panel" id="analytics_panel_<?php echo htmlspecialchars($panel->panelID); ?>"></div>
    <?php endforeach; ?>
</div>
