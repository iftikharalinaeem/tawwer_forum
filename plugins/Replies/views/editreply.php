<?php if (!defined('APPLICATION')) exit();
include_once $this->fetchViewLocation('reply_functions', '', 'plugins/Replies');

$Reply = $this->data('Reply');
writeReplyEdit($Reply);