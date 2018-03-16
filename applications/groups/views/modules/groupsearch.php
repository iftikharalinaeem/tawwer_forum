<?php if (!defined('APPLICATION')) exit();

$title = t('Search Groups');
$searchPlaceholder = t('GroupSearchPlaceHolder', 'Search Groups');

echo '<div class="SiteSearch groupsSearch">';
$Form = new Gdn_Form();
$Form = $this->Form;
echo $Form->open(['action' => url('/search'), 'method' => 'get']);
echo $Form->hidden('group_group', ['value' => '1']);


echo $Form->textBox('Search', ['class' => 'InputBox BigInput groupsSearch-text js-search-groups', 'placeholder' => $searchPlaceholder, 'aria-label' => $searchPlaceholder]);

echo '<button type="submit" class="Button groupsSearch-button" role="search" title="'.$title.'">';
echo '  <span class="sr-only">'.$title.'</span>';
echo '</button>';

echo $Form->close();
echo '</div>';
?>
