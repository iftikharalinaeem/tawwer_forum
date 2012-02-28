<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Tabs SearchTabs">
	<h1>Search</h1>
</div>
<div class="SearchForm">
	<?php
	$Form = $this->Form;
	$Form->InputPrefix = '';
   
   $Get = Gdn::Request()->Get();
   unset($Get['Search']);
   
	echo $Form->Open(array('action' => Url('/search?'.http_build_query($Get)), 'method' => 'get')),
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
   $ViewLocation = $this->FetchViewLocation('results');
   include($ViewLocation);
   ?>
   <table class="PageNavigation Bottom">
      <tr>
         <td class="NoJump">
         <?php
         echo PagerModule::Write();
         ?>
         </td>
      </tr>
   </table>
   <?php
}