<?php if (!defined('APPLICATION')) { exit(); }
$popularPosts = $this->data('popularPosts');
$session = Gdn::session();
?>
<h1 class="H HomepageTitle">Popular Discussions</h1>
<br/>
<ul class="DataList Discussions">
    <?php
    $alt = '';
    foreach ($popularPosts->result() as $discussion) {
        $alt = $alt == ' Alt' ? '' : ' Alt';
        WriteDiscussion($discussion, $this, $session, $alt);
    }
    ?>
</ul>
<br/>
<?php
