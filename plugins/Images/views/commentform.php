<?php
if (!defined('APPLICATION'))
   exit();
$Session = Gdn::Session();
?>
<div id="NewImageForm" class="Toggle-NewImageForm <?php echo $this->EventArguments['FormCssClass']; ?> NewImageForm Hidden">
   <div class="FormWrapper">
      <?php
      echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'id' => 'UploadForm'));
      echo $this->Form->Errors();
      ?>
      <div id="filetable" class="UploadFiles files" role="presentation"></div>
      <div class="ImageFormWrap">
         <div class="ImageControlsWrap">
            <span class="FileInput btn btn-success fileinput-button">
               <?php 
               echo Sprite('SpImage');
               echo Wrap(T('Add Image'));
               echo '<input type="file" name="files[]" multiple>';
               ?>
            </span><?php 
            echo Wrap(T('Drag and Drop', 'Drag &amp; Drop'), 'span class="DropZone"'); ?>
            <div class="FetchUrl">
               <input type="text" class="UrlInput" placeholder="Paste image url..." />
               <?php echo Anchor('Fetch', '#', 'UrlButton Button Success'); ?>
            </div>
         </div>
         <div class="Buttons">
            <?php
            echo $this->Form->Button('Post', array('class' => 'Button ImageButton Primary'));
            ?>
         </div>
      </div>
      <?php echo $this->Form->Close(); ?>
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
   ?>
</div>
