<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Info">
    <?php
    echo '<div>'.t('KeywordBlockerRequiresApproval', 'The information you provided contains "blocked" word(s) and was sent to the moderation team for approval.').'</div>';
    if ($this->data('GroupUrl')) {
        echo '<div>'.anchor('Go back to the group.', $this->data('GroupUrl')).'</div>';
    } else {
        echo '<div>'.anchor('Go to the discussions list.', 'discussions').'</div>';
    }
    ?>
</div>
