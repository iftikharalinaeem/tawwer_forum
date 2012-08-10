<?php if (!defined('APPLICATION')) return; ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php

include $this->FetchViewLocation('SearchForm');
include $this->FetchViewLocation('TranslationTable');

Gdn_Theme::AssetBegin('Panel');
?>

<div class="Box">
   <h4><?php echo T('What Is This Page?'); ?></h4>
   <p>
      <?php
      echo T("Browse through the translations for this locale.");
      ?>
   </p>
</div>

<?php

echo Gdn_Theme::Module('TranslationFilterModule');

Gdn_Theme::AssetEnd();