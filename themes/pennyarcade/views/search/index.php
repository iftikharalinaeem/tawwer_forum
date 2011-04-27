<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs SearchTabs">
	<h1>Search</h1>
</div>
<div class="SearchForm">
	<?php
	$Form = $this->Form;
	$Form->InputPrefix = '';
	echo $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
		'<span class="SearchSprite"></span>',
		$Form->TextBox('Search', array('class' => 'SearchBox')),
		$Form->Button('Search', array('Name' => '')),
      $Form->Errors(),
		$Form->Close();
	?>
</div>
<?php if ($this->SearchResults) { ?>
<div class="Tabs SearchTabs">
	<h2><?php printf(T(count($this->SearchResults) == 0 ? "No results for '%s'" : "Search results for '%s'"), $this->SearchTerm); ?></h2>
</div>
<?php
} 
if (is_array($this->SearchResults) && count($this->SearchResults) > 0) {
   echo $this->Pager->ToString('less');
   $ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);
   $this->Pager->Render();
}