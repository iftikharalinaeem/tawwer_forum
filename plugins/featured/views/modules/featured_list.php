<div class="FeaturedWrap">
   <h2><?php echo T('Featured Discussions'); ?></h2>
<ul class="DataList">
<?php

foreach ($this->Data('Discussions') as $Discussion) {
   $Alt = $Alt == ' Alt' ? '' : ' Alt';
   WriteDiscussion($Discussion, $this, $Session, $Alt);
}
?>
</ul>
</div>