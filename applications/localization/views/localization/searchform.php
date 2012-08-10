<div class="LocaleSearchForm">
<?php

$Form = new Gdn_Form();
$Form->InputPrefix = '';
$Form->Method = 'get';

echo $Form->Open(array('Exclude' => array('Search', 'Field', 'Go')));

echo $Form->TextBox('Search', array('placeholder' => T('Search'))), ' ';

echo $Form->RadioList('Field', array('' => 'English', 'translation' => $this->Data('Locale.Name'))), ' ';

echo $Form->Button('Go'), ' ';

echo $Form->Close();

?>
</div>