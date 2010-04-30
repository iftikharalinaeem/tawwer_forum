<?php if (!defined('APPLICATION')) exit();
echo Anchor(T('Create a New Issue'), '/post/discussion'.(array_key_exists('CategoryID', $Data) ? '/'.$Data['CategoryID'] : ''), 'BigButton NewDiscussion');