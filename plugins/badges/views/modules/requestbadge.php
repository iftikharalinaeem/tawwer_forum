<?php if (!defined('APPLICATION')) exit();

echo '<div class="BoxButtons BoxRequestBadge">';

echo anchor(
    t('Request This Badge'),
    '/badge/request/'.$Data['BadgeID'],
    'Button BigButton Popup'
);
Gdn::controller()->fireEvent('AfterRequestBadgeButton');

echo '</div>';