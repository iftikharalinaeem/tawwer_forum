<?php if (!defined('APPLICATION')) exit();
include_once $this->FetchViewLocation('reply_functions', '', 'plugins/Replies');

$Reply = $this->Data('Reply');
WriteReply($Reply);

