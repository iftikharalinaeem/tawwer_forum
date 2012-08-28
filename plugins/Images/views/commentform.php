<?php if (!defined('APPLICATION')) exit();

// The table listing the files available for upload/download.
echo '<table id="filetable" role="presentation" class="table table-striped"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>';
echo '<div class="P">';
   echo '<span class="Button Success FileInput btn btn-success fileinput-button">';
         echo Sprite('SpImage');
         echo Wrap(T('Add image'));
         echo '<input type="file" name="files[]" multiple>';
   echo '</span>';
   echo Wrap(T('or'), 'span class="or"');
   echo '<input type="text" class="url-input" placeholder="paste the url to an image here..." />';
   echo ' '.Anchor('Fetch', '#', 'UrlButton Button Success');
   echo Wrap(T('Drag & drop allowed', 'Hint: Drag and drop images here.'), 'div style="font-size: 11px;"');
echo '</div>';

include_once($this->FetchViewLocation('template', '', 'plugins/Images'));