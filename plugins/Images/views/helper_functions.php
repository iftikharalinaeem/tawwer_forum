<?php if (!defined('APPLICATION')) exit();

function WriteImageUpload($AutoSave = FALSE) {
   echo '<div class="ImageControlsWrap">';
      echo '<div class="FileInput DropZone btn btn-success fileinput-button">';
         echo Wrap(T('Drag and drop or click to upload.'));
         echo '<input type="file" name="files[]" multiple>';
      echo '</div>';
      
      echo '<i class="Or P">'.T('or').'</i>';
      
      echo '<div class="FetchUrl P">';
         echo '<input type="text" class="UrlInput InputBox" placeholder="'.T('Paste image url...').'" />';
         echo ' '.Anchor('Fetch', '#', 'UrlButton Button Success');
         
      if ($AutoSave) {
         echo '<input type="hidden" name="AutoSave" value="1" />';
      }
      
      echo '</div>';
   echo '</div>';
}