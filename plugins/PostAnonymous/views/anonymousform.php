<?php if (!defined('APPLICATION')) exit(); ?>
<div class="PostAnonymous-Form">
   <?php
   echo '<div class="P Info">'.
      T("You can post anonymously to protect your privacy.").
      '</div>';
   echo '<ul class="List Inline PostOptions"><li>';
   echo $this->Form->CheckBox('Anonymous', T('Post this anonymously'));
   echo '</li></ul>';
   ?>
</div>