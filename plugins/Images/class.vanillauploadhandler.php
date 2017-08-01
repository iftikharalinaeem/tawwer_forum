<?php if (!defined('APPLICATION')) exit();

require(PATH_PLUGINS.'/Images/library/jQuery-FileUpload/server/php/upload.class.php');

class VanillaUploadHandler extends UploadHandler {
   /// Properties
   
   /// Methods
   
   public function delete() {
      $file = Gdn::controller()->Request->get('file');

      $success = TRUE;
      
      // Delete the file.
      $upload = new Gdn_Upload();
      $upload->delete($file);
      
      // Delete the thumbnail.
      $parsed = $upload->parse($file);
      $thumbName = sprintf($parsed['SaveFormat'], "thumbnails/{$parsed['Name']}");
      $upload->delete($thumbName);
      
      $success = $parsed;
      
      header('Content-type: application/json');
      echo json_encode($success);
   }
   
   public function handle_file_upload($uploaded_file, $name, $size, $type, $error, $index = null) {
      // Change the filename to a random hash.
      $Filename = md5(microtime());
      $Result = parent::handle_file_upload($uploaded_file, $Filename, $size, $type, $error, $index);
      $Filename = $Result->name;
      
      // Move the files to their final location.
      $Dir = substr($Filename, 0, 2);
      $Name = "images/$Dir/$Filename";
      
      
      $Upload = new Gdn_Upload();
      
      // Move the thumbnail.
      $Source = $this->options['image_versions']['thumbnail']['upload_dir'].$Filename;
      $Target = "thumbnails/$Name";
      $Parsed = $Upload->saveAs($Source, $Target);
      $Result->thumbnail_url = $Parsed['Url'];
      
      // Move the image.
      $Source = $this->options['upload_dir'].$Filename;
      $Target = $Name;
      $Parsed = $Upload->saveAs($Source, $Target);
      $Result->url = $Parsed['Url'];
      
      $Result->delete_url = url('/post/uploadimage.json?file='.urlencode($Parsed['SaveName']), TRUE);
      $Result->parsed = $Parsed;
      
      $Result->RecordType = 'Temp';
      $Result->RecordID = time();
      $this->saveComment($Result);
      
      return $Result;
   }
   
   public function handle_file_wget($url) {
      // Temporarily copy the file locally.
      $filename = basename($url);
      $dest = $this->options['upload_dir'].$filename;
      
      touchFolder(dirname($dest));
      copy($url, $dest);
      $size = filesize($dest);
      $file = new stdClass();
      $file->name = $filename;
      $file->size = intval($size);
      $file->type = mime_content_type($dest);
      $file->url = $url;
      $this->make_image_versions($file);
      
      // Move the thumbnail.
      $upload = new Gdn_Upload();
      $source = $this->options['image_versions']['thumbnail']['upload_dir'].$filename;
      
      if (!file_exists($source)) {
         die("Thumbnail doesn't exist: ".$source);
      }
      
      $target = "thumbnails/$filename";
      $parsed = $upload->saveAs($source, $target);
      
      $file->thumbnail_url = $parsed['Url'];
      
      // Delete the temporary file.
      unlink($dest);
      
      $this->set_file_delete_url($file);
      
      $file->RecordType = 'Temp';
      $file->RecordID = time();
      $this->saveComment($file);
      
      ob_clean();
      echo json_encode([$file]);
   }
   
   public function saveComment($file) {
      $autoSave = Gdn::request()->post('AutoSave');
      if (!$autoSave)
         return;
      
      // See if we need to save a comment.
      $discussionID = Gdn::request()->post('DiscussionID');
      if (!$discussionID)
         return;
         
         
      $image = arrayTranslate((array)$file, ['url' => 'Image', 'thumbnail_url' => 'Thumbnail', 'size' => 'Size']);
      $image['DiscussionID'] = $discussionID;
      
      $imageModel = new ImageModel();
      $commentID = $imageModel->saveComment($image);
      
      if ($commentID) {
         $file->RecordType = 'Comment';
         $file->RecordID = $commentID;
      }
   }
}