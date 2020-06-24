<?php
if (!defined('APPLICATION'))
   exit();
$Session = Gdn::session();
$CancelUrl = '/discussions';
if (c('Vanilla.Categories.Use') && is_object($this->Category))
   $CancelUrl = '/categories/' . urlencode($this->Category->UrlCode);

require_once dirname(__FILE__).'/helper_functions.php';

?>
<div id="NewImageForm" class="NewImageForm DiscussionForm FormTitleWrapper">
   <?php
   if ($this->deliveryType() == DELIVERY_TYPE_ALL)
      echo wrap($this->data('Title'), 'h1 class="H"');

   echo $this->Form->open(['enctype' => 'multipart/form-data', 'id' => 'UploadForm']);
   echo $this->Form->errors();

   echo '<div class="FormWrapper">';
   
   if ($this->ShowCategorySelector === TRUE) {
      echo '<div class="P">';
         echo '<div class="Category">';
         echo $this->Form->label('Category', 'CategoryID'), ' ';
         echo $this->Form->categoryDropDown('CategoryID', ['Value' => getValue('CategoryID', $this->Category)]);
         echo '</div>';
      echo '</div>';
   }

   echo '<div class="P">';
      echo $this->Form->label('Name', 'Name');
      echo wrap($this->Form->textBox('Name', ['maxlength' => 100, 'class' => 'InputBox BigInput']), 'div', ['class' => 'TextBoxWrapper']);
   echo '</div>';

   writeImageUpload();

   echo '</div>'; // FormWrapper

   // The table listing the files available for upload/download.
   echo '<div id="filetable" class="Tiles UploadFiles files" role="presentation"></div>';

   echo '<div class="Buttons">';
      echo $this->Form->button('Post', ['class' => 'Button ImageButton Primary']);
      echo anchor(t('Cancel'), $CancelUrl, 'Cancel');
   echo '</div>';

   echo $this->Form->close();
   ?>
</div>

<?php 
// If the form was posted back, build up an array to re-populate the uploads table.
$PostedImages = $this->Form->getFormValue('Image');
$PostedSizes = $this->Form->getFormValue('Size');
$PostedCaptions = $this->Form->getFormValue('Caption');
$Thumbnails = $this->Form->getFormValue('Thumbnail', []);
$Files = [];

if (is_array($PostedImages)) {
   foreach ($PostedImages as $Key => $PostedImage) {
      $Filename = basename($PostedImage);
      $Thumbnail = getValue($Key, $Thumbnails);
      
      $file = new stdClass();
      $file->name = $file->caption = getValue($Key, $PostedCaptions);
      $file->size = intval(getValue($Key, $PostedSizes));
      $file->type = substr(strrchr($Filename,'.'),1);
      $file->url = $PostedImage;
      $file->thumbnail_url = $Thumbnail;
      $file->delete_url = url('post/uploadimage/', TRUE).'?file='.urlencode($Filename);
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

include_once($this->fetchViewLocation('template', '', 'plugins/Images'));