<?php if (!defined('APPLICATION')) exit();

echo '<ul>';

foreach($this->Data('Users') as $User) {
   echo '<li>'.
      UserPhoto($User, array('Size' => 'Small')).
      UserAnchor($User).
      '</li>';
}

echo '</ul>';