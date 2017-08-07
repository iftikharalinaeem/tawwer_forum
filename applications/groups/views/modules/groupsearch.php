<?php if (!defined('APPLICATION')) exit();

echo $this->Form->open(['action' => url('/search'), 'method' => 'get']).
    $this->Form->hidden('group_group', ['value' => '1']).
    $this->Form->textBox('Search', ['placeholder' => t('Search Groups'), 'class' => 'InputBox js-search-groups']).
    ' '.$this->Form->button('Go', ['Name' => '']).
    $this->Form->close();