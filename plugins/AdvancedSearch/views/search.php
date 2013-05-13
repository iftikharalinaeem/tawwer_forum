<?php if (!defined('APPLICATION')) exit();

echo Gdn_Theme::Module('AdvancedSearchModule', array('Results' => TRUE));

echo $this->FetchView('search-results', '', 'plugins/AdvancedSearch');

echo '<div class="PageControls Bottom">';
PagerModule::Write();
echo '</div>';
