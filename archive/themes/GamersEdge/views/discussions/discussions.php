<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
if (!function_exists('WriteDiscussion'))
   include($this->FetchViewLocation('helper_functions', 'discussions', 'vanilla'));

?>
<div id="TitleBg"><h2>Annoucements</h2></div>
<div id="ContentInner">


<?php
	
$Alt = '';
if (property_exists($this, 'AnnounceData') && is_object($this->AnnounceData)) {
	foreach ($this->AnnounceData->Result() as $Discussion) {
		$Alt = $Alt == ' Alt' ? '' : ' Alt';
		WriteDiscussion($Discussion, $this, $Session, $Alt);
	}
}
?>
</div>
<div id="TitleBg"><h2>Discussions</h2></div>
<div id="ContentInner">


<?php
$Alt = '';
foreach ($this->DiscussionData->Result() as $Discussion) {
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   WriteDiscussion($Discussion, $this, $Session, $Alt);
}

?>

</div>