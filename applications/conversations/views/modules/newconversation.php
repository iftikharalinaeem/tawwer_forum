<?php if (!defined('APPLICATION')) exit();
$name = $Data['Profile']['Name'] ?? '';
$appendName = '';
if ($name) {
    $name = urlencode($name);
    $appendName = '/'.$name;
}
echo anchor(t('New Message'), '/messages/add'.$appendName, 'Button BigButton NewConversation Primary', ['title' => $name, 'aria-label' => $name]);
