<ul class="DataList SearchResults">
<?php
if (is_array($this->SearchResults) && count($this->SearchResults) > 0) {
	foreach ($this->SearchResults as $Key => $Row) {
		$Row = (object)$Row;
		$this->EventArguments['Row'] = $Row;
?>
	<li class="Item">
		<?php $this->FireEvent('BeforeItemContent'); ?>
		<div class="ItemContent">
			<?php echo Anchor(Gdn_Format::Text($Row->Title), $Row->Url, 'Title'); ?>
			<div class="Excerpt"><?php
				echo Anchor(SliceString(Gdn_Format::Text($Row->Summary, FALSE), 250), $Row->Url);
			?></div>
		</div>
	</li>
<?php
	}
}
?>
</ul>