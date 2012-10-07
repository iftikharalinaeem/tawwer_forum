<?php if (!defined('APPLICATION')) exit();

require(PATH_PLUGINS.'/Images/library/jQuery-FileUpload/server/php/upload.class.php');

class VanillaUploadHandler extends UploadHandler {
   /// Properties
   
   /// Methods
   
   public function delete() {
      $File = Gdn::Controller()->Request->Get('file');

      $Success = TRUE;
      
      // Delete the file.
      $Upload = new Gdn_Upload();
      $Upload->Delete($File);
      
      // Delete the thumbnail.
      $Parsed = $Upload->Parse($File);
      $ThumbName = sprintf($Parsed['SaveFormat'], "thumbnails/{$Parsed['Name']}");
      $Upload->Delete($ThumbName);
      
      $Success = $Parsed;
      
      header('Content-type: application/json');
      echo json_encode($Success);
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
      $Parsed = $Upload->SaveAs($Source, $Target);
      $Result->thumbnail_url = $Parsed['Url'];
      
      // Move the image.
      $Source = $this->options['upload_dir'].$Filename;
      $Target = $Name;
      $Parsed = $Upload->SaveAs($Source, $Target);
      $Result->url = $Parsed['Url'];
      
      $Result->delete_url = Url('/post/uploadimage.json?file='.urlencode($Parsed['SaveName']), TRUE);
      $Result->parsed = $Parsed;
      
      return $Result;
   }
   
   public function handle_file_wget($url) {
      // Temporarily copy the file locally.
      $filename = basename($url);
      $dest = $this->options['upload_dir'].$filename;
      copy($url, $dest);
      $size = filesize($filepath);
      $file = new stdClass();
      $file->name = $filename;
      $file->size = intval($size);
      $file->type = mime_content_type($dest);
      $file->url = $url;
      $this->make_image_versions($file);
      
      
      // Move the thumbnail.
      $Upload = new Gdn_Upload();
      $Source = $this->options['image_versions']['thumbnail']['upload_dir'].$Filename;
      $Target = "thumbnails/$Name";
      $Parsed = $Upload->SaveAs($Source, $Target);
      $Result->thumbnail_url = $Parsed['Url'];
      
      // Delete the temporary file.
      unlink($dest);
      
      $this->set_file_delete_url($file);
      
      echo json_encode(array($file));
   }
}