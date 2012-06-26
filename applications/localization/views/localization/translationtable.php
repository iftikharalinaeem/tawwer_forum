<div id="TranslationsTable">
<table class="LocaleTable TranslationsTable" summary="<?php echo T('The locales available for translation in Vanilla and there current % completeness.'); ?>">
   <thead>
      <tr>
         <th class="EnglishColumn"><?php echo T('English'); ?></th>
         <th class="TranslationColumn"><?php echo htmlspecialchars($this->Data('Locale.Name')); ?></th>
         <th>&#160;</th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($this->Data('Translations') as $Row): ?>
      <tr class="<?php echo Alternate('Alt', '', ''); ?>" id="CodeID_<?php echo($Row['CodeID']); ?>" codeid="<?php echo($Row['CodeID']); ?>">
         <td class="EnglishColumn">
            <?php 
            if ($Row['Name'] != $Row['EnTranslation'])
               echo '<div class="LocaleCode">'.htmlspecialchars($Row['Name']).'</div>';
               
               echo '<div class="EnglishTranslation">'.htmlspecialchars($Row['EnTranslation']).'</div>'; 
            ?>
         </td>
         <td class="TranslationColumn">
            <?php echo '<div class="Translation">'.htmlspecialchars($Row['Translation']).'</div>'; ?>
         </td>
         <td><?php echo '<div class="Approved-Icon Approved-'.$Row['Approved'].'" Title="'.T($Row['Approved']).'"></div>'?></td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>
<?php
if ($this->Data('_UsePager'))
   PagerModule::Write(array('Limit' => LocalizationController::PAGE_SIZE, 'CurrentRecords' => count($this->Data('Translations'))));
else
   echo '<div class="Buttons Pager PrevNextPager">'.Anchor(T('Refresh'), Gdn::Controller()->SelfUrl).'</div>';
?>
</div>