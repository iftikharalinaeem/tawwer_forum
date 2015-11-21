<?php if (!defined('APPLICATION')) exit();
echo '<div class="BoxButtons BoxRequestBadge">';

echo Anchor(
    T('Request This Badge'),
    '/badge/request/'.$Data['BadgeID'],
    'Button BigButton Popup'
);
Gdn::Controller()->FireEvent('AfterRequestBadgeButton');

echo '</div>';