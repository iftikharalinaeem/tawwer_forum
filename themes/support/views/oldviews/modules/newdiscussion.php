<?php if (!defined('APPLICATION')) exit();
echo Anchor(T('Ask a Question'), '/post/discussion'.(array_key_exists('CategoryID', $Data) ? '/'.$Data['CategoryID'] : ''), 'BigButton NewDiscussion');