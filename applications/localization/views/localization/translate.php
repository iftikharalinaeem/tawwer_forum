<?php if (!defined('APPLICATION')) return; ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php

include $this->FetchViewLocation('TranslationForm');

include $this->FetchViewLocation('TranslationTable');

Gdn_Theme::AssetBegin('Panel');
?>

<div class="Box">
   <h4><?php echo T('What Is This Page?'); ?></h4>
   <p>
      <?php
      echo T("This page allows you to translate all of the text that hasn't been translated yet.");
      ?>
   </p>
</div>

<?php

echo Gdn_Theme::Module('TranslationFilterModule');

Gdn_Theme::AssetEnd();