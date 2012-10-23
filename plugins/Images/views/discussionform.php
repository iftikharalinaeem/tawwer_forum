<?php
if (!defined('APPLICATION'))
   exit();
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if (C('Vanilla.Categories.Use') && is_object($this->Category))
   $CancelUrl = '/vanilla/categories/' . urlencode($this->Category->UrlCode);

require_once dirname(__FILE__).'/helper_functions.php';

?>
<div id="NewImageForm" class="NewImageForm DiscussionForm FormTitleWrapper">
   <?php
   if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
      echo Wrap($this->Data('Title'), 'h1 class="H"');

   echo $this->Form->Open(array('enctype' => 'multipart/form-data', 'id' => 'UploadForm'));
   echo $this->Form->Errors();

   echo '<div class="FormWrapper">';
   
   if ($this->ShowCategorySelector === TRUE) {
      echo '<div class="P">';
         echo '<div class="Category">';
         echo $this->Form->Label('Category', 'CategoryID'), ' ';
         echo $this->Form->CategoryDropDown('CategoryID', array('Value' => GetValue('CategoryID', $this->Category)));
         echo '</div>';
      echo '</div>';
   }

   echo '<div class="P">';
      echo $this->Form->Label('Name', 'Name');
      echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
   echo '</div>';

   WriteImageUpload();

   echo '</div>'; // FormWrapper

   // The table listing the files available for upload/download.
   echo '<div id="filetable" class="Tiles UploadFiles files" role="presentation"></div>';

   echo '<div class="Buttons">';
      echo $this->Form->Button('Post', array('class' => 'Button ImageButton Primary'));
      echo Anchor(T('Cancel'), $CancelUrl, 'Cancel');
   echo '</div>';

   echo $this->Form->Close();
   ?>
</div>

<?php 
// If the form was posted back, build up an array to re-populate the uploads table.
$PostedImages = $this->Form->GetFormValue('Image');
$PostedSizes = $this->Form->GetFormValue('Size');
$PostedCaptions = $this->Form->GetFormValue('Caption');
$Thumbnails = $this->Form->GetFormValue('Thumbnail', array());
$Files = array();

if (is_array($PostedImages)) {
   foreach ($PostedImages as $Key => $PostedImage) {
      $Filename = basename($PostedImage);
      $Thumbnail = GetValue($Key, $Thumbnails);
      
      $file = new stdClass();
      $file->name = $file->caption = GetValue($Key, $PostedCaptions);
      $file->size = intval(GetValue($Key, $PostedSizes));
      $file->type = substr(strrchr($Filename,'.'),1);
      $file->url = $PostedImage;
      $file->thumbnail_url = $Thumbnail;
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
         $('#filetable').append(html);
         

      });
   </script>";
}

?>
<script type="text/javascript">
   $(function() {
      $('.Tiles').imagesLoaded(function($images, $proper, $broken) {
         console.log('images loaded');

         this.masonry({
            itemSelector: '.ImageWrap',
            animate: true
         });
      });
   });
</script>
<?php

include_once($this->FetchViewLocation('template', '', 'plugins/Images'));