<?php if (!defined('APPLICATION')) exit();

function writeImageUpload($autoSave = FALSE) {
   echo '<div class="ImageControlsWrap">';
      echo '<div class="FileInput DropZone btn btn-success fileinput-button">';
         echo wrap(t('Drag and drop or click to upload.'));
         echo '<input type="file" name="files[]" multiple>';
      echo '</div>';
      
      echo '<i class="Or P">'.t('or').'</i>';
      
      echo '<div class="FetchUrl P">';
         echo '<input type="text" class="UrlInput InputBox" placeholder="'.t('Paste image url...').'" />';
         echo ' '.anchor('Fetch', '#', 'UrlButton Button Success');
         
      if ($autoSave) {
         echo '<input type="hidden" name="AutoSave" value="1" />';
      }
      
      echo '</div>';
   echo '</div>';
}