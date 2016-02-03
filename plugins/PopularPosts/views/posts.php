<?php if (!defined('APPLICATION')) { exit(); }
$popularPosts = $this->data('popularPosts');

?>
<h1 class="H HomepageTitle">Popular Discussions</h1>
<br/>
<ul class="DataList Discussions">
    <?php
    $Alt = '';
    foreach ($popularPosts->result() as $Discussion) {
        $Alt = $Alt == ' Alt' ? '' : ' Alt';
        WriteDiscussion($Discussion, $this, $Session, $Alt);
    }
    ?>
</ul>
<br/>
<?php
