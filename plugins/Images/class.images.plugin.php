<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['Images'] = array(
   'Name' => 'Images',
   'Description' => "Upload images as discussions, comments, and activities.",
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'RegisterPermissions' => array('Plugins.Images.Add' => 'Garden.Profiles.Edit')
);

class ImagesPlugin extends Gdn_Plugin {
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   /** 
    * Add the "new image" button after the new discussion button. 
    */
   public function Base_BeforeNewDiscussionButton_Handler($Sender) {
      $NewDiscussionModule = &$Sender->EventArguments['NewDiscussionModule'];
      if (Gdn::Session()->CheckPermission('Plugins.Images.Add'))
         $NewDiscussionModule->AddButton(T('New Image'), 'post/image');
   }
   
   /** 
    * Display the Image label on the discussion list.
    */
   public function Base_BeforeDiscussionMeta_Handler($Sender) {
      $Discussion = $Sender->EventArguments['Discussion'];
      if (strcasecmp(GetValue('Type', $Discussion), 'Image') == 0)
         echo Tag($Discussion, 'Type', 'Image');
   }
   
   /** 
    * Add the image form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($Sender) {
      $Forms = $Sender->Data('Forms');
      $Forms[] = array('Name' => 'Image', 'Label' => Sprite('SpImage').T('New Image'), 'Url' => 'post/image');
		$Sender->SetData('Forms', $Forms);
   }
   
   /** 
    * Create the new image method on post controller.
    */
   public function PostController_Image_Create($Sender) {
      // Check permission 
      $Sender->Permission('Vanilla.Discussions.Add');
      $Sender->Permission('Plugins.Images.Add');

      $ImageModel = new ImageModel();
      
      // Override CategoryID if categories are disabled
      $Sender->CategoryID = GetValue(0, $Sender->RequestArgs);
      $UseCategories = $Sender->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      if (!$UseCategories) 
         $Sender->CategoryID = 0;

      $Sender->Category = CategoryModel::Categories($Sender->CategoryID);
      if (!is_object($Sender->Category))
         $Sender->Category = NULL;
      
      if ($UseCategories)
			$CategoryData = CategoryModel::Categories();

      $Sender->AddCssFile('plugins/Images/design/images.css');
      // Include JS necessary in the page.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/vendor/jquery.ui.widget.js');
      // The Templates plugin is included to render the upload/download listings.
      $Sender->AddJsFile('plugins/Images/library/Javascript-Templates/tmpl.min.js');
      // The Load Image plugin is included for the preview images and image resizing functionality.
      $Sender->AddJsFile('plugins/Images/library/Javascript-LoadImage/load-image.min.js');
      // The Canvas to Blob plugin is included for image resizing functionality.
      $Sender->AddJsFile('plugins/Images/library/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js');
      // The Iframe Transport is required for browsers without support for XHR file uploads.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.iframe-transport.js');
      // The basic File Upload plugin.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.fileupload.js');
      // The File Upload file processing plugin.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.fileupload-fp.js');
      // The File Upload user interface plugin.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.fileupload-ui.js');
      // The localization script.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/locale.js');
      // The main application script.
      $Sender->AddJsFile('plugins/Images/js/upload.js');
      // The XDomainRequest Transport is included for cross-domain file deletion for IE8+.
      $Sender->Head->AddString('<!--[if gte IE 8]><script src="'.Url('plugins/Images/library/jQuery-FileUpload/js/cors/jquery.xdr-transport.js').'"></script><![endif]-->');
      
      // Set the model on the form
      $Sender->Form->SetModel($ImageModel);
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         if ($Sender->Category !== NULL)
            $Sender->Form->SetData(array('CategoryID' => $Sender->Category->CategoryID));
      } else { // Form was submitted
         $FormValues = $Sender->Form->FormValues();
         $DiscussionID = $ImageModel->SaveDiscussion($FormValues);
         $Sender->Form->SetValidationResults($ImageModel->ValidationResults());
         if ($Sender->Form->ErrorCount() == 0) {
            $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);            
            Redirect(DiscussionUrl($Discussion));
         }
      }
      
      // Set up the page and render
      $Sender->Title(T('New Image'));
		$Sender->SetData('Breadcrumbs', array(array('Name' => $Sender->Data('Title'), 'Url' => '/post/image')));
      $Sender->Render('discussionform', '', 'plugins/Images');
   }
   
   public function PostController_UploadImage_Create($Sender) {
      error_reporting(E_ALL | E_STRICT);
      require(PATH_PLUGINS.'/Images/library/jQuery-FileUpload/server/php/upload.class.php');
      $upload_handler = new UploadHandler(array('script_url' => Url('post/uploadimage', TRUE)));
      header('Pragma: no-cache');
      header('Cache-Control: no-store, no-cache, must-revalidate');
      header('Content-Disposition: inline; filename="files.json"');
      header('X-Content-Type-Options: nosniff');
      header('Access-Control-Allow-Origin: *');
      header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, PUT, DELETE');
      header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');
      switch ($_SERVER['REQUEST_METHOD']) {
         case 'OPTIONS':
            break;
         case 'HEAD':
         case 'GET':
            $upload_handler->get();
            break;
         case 'POST':
            // Grab the image from the specified url.
            $Url = GetIncomingValue('inputUrl');
            if ($Url) {
               $upload_handler->handle_file_wget($Url);
            } else if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
                  $upload_handler->delete();
            } else {
                  $upload_handler->post();
            }
            break;
         case 'DELETE':
            $upload_handler->delete();
            break;
         default:
            header('HTTP/1.1 405 Method Not Allowed');
      }
      die();
   }   
   
   /** 
    * Add the js to the discussion form for file uploads.
    */
   public function DiscussionController_Render_Before($Sender) {
      $Sender->AddCssFile('plugins/Images/design/images.css');
      // Include JS necessary in the page.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/vendor/jquery.ui.widget.js');
      // The Templates plugin is included to render the upload/download listings.
      $Sender->AddJsFile('plugins/Images/library/Javascript-Templates/tmpl.min.js');
      // The Load Image plugin is included for the preview images and image resizing functionality.
      $Sender->AddJsFile('plugins/Images/library/Javascript-LoadImage/load-image.min.js');
      // The Canvas to Blob plugin is included for image resizing functionality.
      $Sender->AddJsFile('plugins/Images/library/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js');
      // The Iframe Transport is required for browsers without support for XHR file uploads.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.iframe-transport.js');
      // The basic File Upload plugin.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.fileupload.js');
      // The File Upload file processing plugin.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.fileupload-fp.js');
      // The File Upload user interface plugin.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/jquery.fileupload-ui.js');
      // The localization script.
      $Sender->AddJsFile('plugins/Images/library/jQuery-FileUpload/js/locale.js');
      // The main application script.
      $Sender->AddJsFile('plugins/Images/js/upload.js');
      // The XDomainRequest Transport is included for cross-domain file deletion for IE8+.
      $Sender->Head->AddString('<!--[if gte IE 8]><script src="'.Url('plugins/Images/library/jQuery-FileUpload/js/cors/jquery.xdr-transport.js').'"></script><![endif]-->');
   }
   
   /* Render the comment file upload form */
   public function DiscussionController_AfterBodyField_Handler($Sender) {
      echo $Sender->FetchView('commentform', '', 'plugins/Images');
   }
}