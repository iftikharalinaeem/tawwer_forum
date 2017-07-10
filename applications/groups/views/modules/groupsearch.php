<?php if (!defined('APPLICATION')) exit();

echo $this->Form->Open(['action' => Url('/search'), 'method' => 'get']).
    $this->Form->Hidden('group_group', ['value' => '1']).
    $this->Form->TextBox('Search', ['placeholder' => T('Search Groups'), 'class' => 'InputBox js-search-groups']).
    ' '.$this->Form->Button('Go', ['Name' => '']).
    $this->Form->Close();