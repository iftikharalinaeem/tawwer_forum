<?php if (!defined('APPLICATION')) exit();

$title = $this->getTitle();
$moduleClasses = 'groupSearch ';

echo '<div class="'.$this->getCssClass().'" role="search">';
$form = new Gdn_Form();
echo $form->open(['action' => url('/groups/browse/search'), 'method' => 'get']);
echo $form->hidden('group_group', ['value' => '1']);

echo '<div class="groupSearch-search">';
echo $form->textBox('Search', ['class' => 'InputBox BigInput groupSearch-text js-search-groups', 'placeholder' => $title, 'title' => $title, 'aria-label' => $title, 'role']);

echo '<button type="submit" class="Button groupSearch-button">';

echo $this->getButtonContents();

echo '</button>';
echo '</div>';

echo $form->close();
echo '</div>';
