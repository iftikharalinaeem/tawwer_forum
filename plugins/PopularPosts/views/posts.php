<?php if (!defined('APPLICATION')) { exit(); }
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

$popularPosts = $this->data('popularPosts');
$session = Gdn::session();
?>
<div class="PopularPosts">
    <h1 class="H HomepageTitle">[Popular Discussions]</h1>
    <ul class="DataList Discussions">
        <?php
        $alt = '';
        foreach ($popularPosts->result() as $discussion) {
            $alt = $alt == ' Alt' ? '' : ' Alt';
            writeDiscussion($discussion, $this, $session, $alt);
        }
        ?>
    </ul>
<div>