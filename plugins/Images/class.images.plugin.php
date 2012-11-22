<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['Images'] = array(
   'Name' => 'Images',
   'Description' => "Upload images as discussions, comments, and activities.",
   'Version' => '1.0.2a',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'RegisterPermissions' => array('Plugins.Images.Add' => 'Garden.Profiles.Edit')
);

class ImagesPlugin extends Gdn_Plugin {
   /// Methods ///
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }
   
   /**
    * @param Gdn_Controller $Sender
    */
   public function AddJsFiles($Sender = NULL) {
      if (!$Sender)
         $Sender = Gdn::Controller();
      
      // Include JS necessary in the page.
      $Sender->AddJsFile('library/jQuery-FileUpload/js/vendor/jquery.ui.widget.js', 'plugins/Images');
      // The Templates plugin is included to render the upload/download listings.
      $Sender->AddJsFile('library/Javascript-Templates/tmpl.min.js', 'plugins/Images');
      // The Load Image plugin is included for the preview images and image resizing functionality.
      $Sender->AddJsFile('library/Javascript-LoadImage/load-image.min.js', 'plugins/Images');
      // The Canvas to Blob plugin is included for image resizing functionality.
      $Sender->AddJsFile('library/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js', 'plugins/Images');
      // The Iframe Transport is required for browsers without support for XHR file uploads.
      $Sender->AddJsFile('library/jQuery-FileUpload/js/jquery.iframe-transport.js', 'plugins/Images');
      // The basic File Upload plugin.
      $Sender->AddJsFile('library/jQuery-FileUpload/js/jquery.fileupload.js', 'plugins/Images');
      // The File Upload file processing plugin.
      $Sender->AddJsFile('library/jQuery-FileUpload/js/jquery.fileupload-fp.js', 'plugins/Images');
      // The File Upload user interface plugin.
      $Sender->AddJsFile('library/jQuery-FileUpload/js/jquery.fileupload-ui.js', 'plugins/Images');
      // The localization script.
      $Sender->AddJsFile('library/jQuery-FileUpload/js/locale.js', 'plugins/Images');
      // The main application script.
      $Sender->AddJsFile('upload.js', 'plugins/Images');
      // The XDomainRequest Transport is included for cross-domain file deletion for IE8+.
      $Sender->Head->AddString('<!--[if gte IE 8]><script src="'.Url('plugins/Images/library/jQuery-FileUpload/js/cors/jquery.xdr-transport.js').'"></script><![endif]-->');
   }
   
   /// Event Handlers ///
   
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('images.css', 'plugins/Images');
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
      if (!is_array($Forms))
         $Forms = array();
      
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

      // Set the model on the form
      $Sender->Form->SetModel($ImageModel);
      if (!$Sender->Form->IsPostBack()) {
         if ($Sender->Category !== NULL)
            $Sender->Form->SetData(array('CategoryID' => $Sender->Category->CategoryID));
      } else { // Form was submitted
         $FormValues = $Sender->Form->FormValues();
         $DiscussionID = GetValue('DiscussionID', $FormValues);
         $NewDiscussion = $DiscussionID == 0;
         $CommentIDs = array();
         $DiscussionID = $ImageModel->Save($FormValues);
         $CommentIDs = $ImageModel->CommentIDs;
         $Sender->Form->SetValidationResults($ImageModel->ValidationResults());
         if ($Sender->Form->ErrorCount() == 0) {
            $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);
            if ($NewDiscussion) {
               // Redirect to the new discussion
               Redirect(DiscussionUrl($Discussion).'#latest');
            } elseif (count($CommentIDs) > 0) {
               // Load/return the newly added comments.
               sort($CommentIDs);
               $FirstCommentID = array_shift($CommentIDs);
               $Offset = $Sender->CommentModel->GetOffset($FirstCommentID);
               $Comments = $Sender->CommentModel->Get($DiscussionID, 30, $Offset);
               $Sender->SetData('Comments', $Comments);
               $Sender->SetData('NewComments', TRUE);
               // $Sender->ClassName = 'DiscussionController';
               // $Sender->ControllerName = 'discussion';
               // $Sender->View = 'discussionitems';
               
               // Make sure to set the user's discussion watch records
               $CountComments = $Sender->CommentModel->GetCount($DiscussionID);
               $Limit = count($CommentIDs);
               $Sender->Offset = $CountComments - $Limit;
               $Sender->CommentModel->SetWatch($Discussion, $Limit, $Sender->Offset, $CountComments);
               $Sender->Render('discussionitems', '', 'plugins/Images');
               return;
            }
         }
      }
      // Set up the page and render
      $Sender->Title(T('New Image'));
		$Sender->SetData('Breadcrumbs', array(array('Name' => $Sender->Data('Title'), 'Url' => '/post/image')));
      $this->AddJsFiles();
      $Sender->AddJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugin/Reactions');
      
      $Sender->Render('discussionform', '', 'plugins/Images');
   }
   
   /** 
    * If the discussion type is "image", use the images view (if available)
    * @param type $Sender
    */
   public function Base_BeforeCommentRender_Handler($Sender) {
      $Discussion = $Sender->EventArguments['Discussion'];
      if (GetValue('Type', $Discussion) == 'Image') {
         // $this->ClassName = 'DiscussionController';
         // $this->ControllerName = 'discussion';
         $this->View = 'discussionlist';
         $this->ApplicationFolder = 'plugins/Images';
      }
   }
   
   public function PostController_UploadImage_Create($Sender) {
      error_reporting(E_ALL | E_STRICT);
      
      $Paths = array(
          'Upload' => PATH_UPLOADS.'/image-tmp/',
          'Thumb' => PATH_UPLOADS.'/image-tmp/thumbnails/');
      foreach ($Paths as $Path) {
         TouchFolder($Path);
      }
      
      $upload_handler = new VanillaUploadHandler(array(
          'script_url' => Url('post/uploadimage', TRUE),
          'upload_dir' => $Paths['Upload'],
          'image_versions' => array(
            'thumbnail' => array(
                 'upload_dir' => $Paths['Thumb']
                ))
         ));
      
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
    * @param Gdn_Controller $Sender
    */
   public function DiscussionController_Render_Before($Sender) {
      $Discussion = $Sender->Data('Discussion');
      if (GetValue('Type', $Discussion) != 'Image')
         return;

      // Include JS necessary in the page.
//      $this->AddJsFiles();
      
      // If the current discussion is of type "Image", switch to the images view
      $this->AddJsFiles();
      $Sender->AddJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions');
      
      $Sender->View = PATH_PLUGINS.'/Images/views/discussion.php';
      
      $Sender->CssClass .= ' NoPanel';
   }
   
   public function RootController_Render_Before($Sender) {
      if (InArrayI($Sender->RequestMethod, array('bestof', 'bestof2'))) {
         $Sender->AddJsFile('tile.js', 'plugins/Images');
      }
   }
   
   /* Add a toggle item to the form menu. */
   public function DiscussionController_BeforeCommentForm_Handler($Sender) {
      return;
      $FormToggleMenu = $Sender->EventArguments['FormToggleMenu'];
      $FormToggleMenu->AddLabel(Sprite('SpImage').' '.T('Image'), 'NewImageForm');
      // Is this discussion an image-type? If so, make the default response to post another image.
      if (GetValue('Type', $Sender->Data('Discussion')) == 'Image')
         $FormToggleMenu->CurrentLabelCode('NewImageForm'); 
   }
   
   /* Render the comment file upload form. */
   public function DiscussionController_AfterCommentFormMenu_Handler($Sender) {
      $OldAction = $Sender->Form->Action;
      $Sender->Form->Action = Url('vanilla/post/image');
      echo $Sender->FetchView('commentform', '', 'plugins/Images');
      $Sender->Form->Action = $OldAction;
   }
}