<?php if (!defined('APPLICATION')) { exit(); }

echo Gdn_Theme::module('AdvancedSearchModule', ['Results' => true]);

echo $this->fetchView('search-results', '', 'plugins/AdvancedSearch');
