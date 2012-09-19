<?php
if (!defined('APPLICATION'))
   exit();
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if (C('Vanilla.Categories.Use') && is_object($this->Category))
   $CancelUrl = '/vanilla/categories/' . urlencode($this->Category->UrlCode);
?>
<div id="NewImageForm" class="NewImageForm DiscussionForm FormTitleWrapper">
   <?php
   if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
      echo Wrap($this->Data('Title'), 'h1 class="H"');

   echo '<div class="FormWrapper">';
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'id' => 'UploadForm'));
      echo $this->Form->Errors();

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
      
      if ($this->ShowCategorySelector === TRUE) {
         echo '<div class="P">';
            echo '<div class="Category">';
            echo $this->Form->CategoryDropDown('CategoryID', array('Value' => GetValue('CategoryID', $this->Category)));
            echo '</div>';
         echo '</div>';
      }
      
      echo '<div class="Buttons">';
         echo $this->Form->Button('Post', array('class' => 'Button ImageButton Primary'));
         echo Anchor(T('Cancel'), $CancelUrl, 'Cancel');
      echo '</div>';
      echo $this->Form->Close();
   echo '</div>';
   ?>
</div>

<?php 
// If the form was posted back, build up an array to re-populate the uploads table.
$PostedImages = $this->Form->GetFormValue('Image');
$PostedSizes = $this->Form->GetFormValue('Size');
$PostedCaptions = $this->Form->GetFormValue('Caption');
$Files = array();
if (is_array($PostedImages)) {
   foreach ($PostedImages as $Key => $PostedImage) {
      $Filename = basename($PostedImage);
      $file = new stdClass();
      $file->name = GetValue($Key, $PostedCaptions);
      $file->size = intval(GetValue($Key, $PostedSizes));
      $file->type = substr(strrchr($Filename,'.'),1);
      $file->url = $PostedImage;
      $file->thumbnail_url = Url('uploads/thumbnails/'.$Filename, TRUE);
      $file->delete_url = Url('vanilla/post/uploadimage/', TRUE).'?file='.urlencode($Filename);
      $file->delete_type = 'DELETE';
      $Files[] = $file;
   }
}
if (count($Files) > 0) {
   echo "<script type=\"text/javascript\">
      $(function () {
         post_tmpl_func = tmpl('template-download');
         var html = post_tmpl_func({
            files: ".json_encode($Files).",
            formatFileSize: fileSize
         });
         $('#filetable > tbody').append(html);
      });
   </script>";
}

include_once($this->FetchViewLocation('template', '', 'plugins/Images'));