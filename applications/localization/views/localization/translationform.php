<div class="TranslationForm">
<?php

echo $this->Form->Open(array('id' => 'TranslationForm', 'action' => Url('/localization/translate.json?locale='.urlencode($this->Data('Locale.Locale')))));

echo $this->Form->Hidden('CodeID');

echo '<h2>'.T('English').'</h2>';

echo '<div class="EnglishTranslationWrapper" lang="en">';
echo '<span id="EnglishTranslation"></span>';
echo '<p id="GoogleTranslation"></p>';
echo '</div>';


echo $this->Form->Hidden('TranslationBak');

echo '<h2>';
echo $this->Form->Label($this->Data('Locale.Name'), 'Translation', array('accesskey' => 'T'));
echo '</h2>';

echo '<div class="TextBoxWrapper">';
echo $this->Form->TextBox('Translation', array('Multiline' => TRUE));
echo '</div>';

echo '<div class="Buttons">';
echo $this->Form->Button('Next', array('accesskey' => ']'));
echo $this->Form->Button('Previous', array('accesskey' => '['));
echo '</div>';

echo $this->Form->Close();
?>
</div>