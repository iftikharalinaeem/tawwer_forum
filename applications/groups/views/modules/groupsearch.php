<?php
echo $this->Form->Open(array('action' => Url('/search'), 'method' => 'get')).
    $this->Form->Hidden('group_group', array('value' => '1')).
    $this->Form->TextBox('Search', array('placeholder' => T('Search Groups'), 'class' => 'InputBox js-search-groups')).
    ' '.$this->Form->Button('Go', array('Name' => '')).
    $this->Form->Close();