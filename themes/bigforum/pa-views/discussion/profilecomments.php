<?php if (!defined('APPLICATION')) exit();
foreach ($this->CommentData->Result() as $Comment) {
	$Permalink = '/discussion/comment/'.$Comment->CommentID.'/#Comment_'.$Comment->CommentID;
	$User = UserBuilder($Comment, 'Insert');
	$this->EventArguments['User'] = $User;
?>
<li class="Item">
	<div class="DateCreated TimestampContainer"><span class="TimestampArrow"></span><?php
		echo Anchor(Gdn_Format::Date($Comment->DateInserted), $Permalink, 'Permalink', array('rel' => 'nofollow'));
	?></div>		
	<?php $this->FireEvent('BeforeItemContent'); ?>
	<div class="ItemContent">
		<?php echo Anchor(Gdn_Format::Text($Comment->DiscussionName), $Permalink, 'Title'); ?>
		<div class="Excerpt"><?php
			echo Anchor(SliceString(Gdn_Format::Text(Gdn_Format::To($Comment->Body, $Comment->Format), FALSE), 250), $Permalink);
		?></div>
	</div>
</li>
<?php
}