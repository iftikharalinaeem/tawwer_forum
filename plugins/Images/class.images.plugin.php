<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class ImagesPlugin extends Gdn_Plugin {
   /// Methods ///

   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      include dirname(__FILE__).'/structure.php';
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function AddJsFiles($sender = NULL) {
      if (!$sender)
         $sender = Gdn::Controller();

      // Include JS necessary in the page.
      $sender->AddJsFile('library/jQuery-FileUpload/js/vendor/jquery.ui.widget.js', 'plugins/Images');
      // The Templates plugin is included to render the upload/download listings.
      $sender->AddJsFile('library/Javascript-Templates/tmpl.min.js', 'plugins/Images');
      // The Load Image plugin is included for the preview images and image resizing functionality.
      $sender->AddJsFile('library/Javascript-LoadImage/load-image.min.js', 'plugins/Images');
      // The Canvas to Blob plugin is included for image resizing functionality.
      $sender->AddJsFile('library/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js', 'plugins/Images');
      // The Iframe Transport is required for browsers without support for XHR file uploads.
      $sender->AddJsFile('library/jQuery-FileUpload/js/jquery.iframe-transport.js', 'plugins/Images');
      // The basic File Upload plugin.
      $sender->AddJsFile('library/jQuery-FileUpload/js/jquery.fileupload.js', 'plugins/Images');
      // The File Upload file processing plugin.
      $sender->AddJsFile('library/jQuery-FileUpload/js/jquery.fileupload-fp.js', 'plugins/Images');
      // The File Upload user interface plugin.
      $sender->AddJsFile('library/jQuery-FileUpload/js/jquery.fileupload-ui.js', 'plugins/Images');
      // The localization script.
      $sender->AddJsFile('library/jQuery-FileUpload/js/locale.js', 'plugins/Images');
      // The main application script.
      $sender->AddJsFile('upload.js', 'plugins/Images');
      // The XDomainRequest Transport is included for cross-domain file deletion for IE8+.
      $sender->Head->AddString('<!--[if gte IE 8]><script src="'.Url('plugins/Images/library/jQuery-FileUpload/js/cors/jquery.xdr-transport.js').'"></script><![endif]-->');
   }

   /// Event Handlers ///

   public function AssetModel_StyleCss_Handler($sender, $args) {
      $sender->AddCssFile('images.css', 'plugins/Images');
   }

   /**
    * Add the "new image" button after the new discussion button.
    */
   public function Base_BeforeNewDiscussionButton_Handler($sender) {
      $newDiscussionModule = &$sender->EventArguments['NewDiscussionModule'];
      if (Gdn::Session()->CheckPermission('Plugins.Images.Add'))
         $newDiscussionModule->AddButton(T('New Image'), 'post/image');
   }

   /**
    * Display the Image label on the discussion list.
    */
   public function Base_BeforeDiscussionMeta_Handler($sender) {
      $discussion = $sender->EventArguments['Discussion'];
      if (strcasecmp(GetValue('Type', $discussion), 'Image') == 0)
         echo Tag($discussion, 'Type', 'Image');
   }

   /**
    * Add the image form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($sender) {
      $forms = $sender->Data('Forms');
      if (!is_array($forms))
         $forms = [];

      $forms[] = ['Name' => 'Image', 'Label' => Sprite('SpImage').T('New Image'), 'Url' => 'post/image'];
		$sender->SetData('Forms', $forms);

   }

   /**
    * Create the new image method on post controller.
    */
   public function PostController_Image_Create($sender) {
      // Check permission
      $sender->Permission('Vanilla.Discussions.Add');
      $sender->Permission('Plugins.Images.Add');

      $imageModel = new ImageModel();

      // Override CategoryID if categories are disabled
      $sender->CategoryID = GetValue(0, $sender->RequestArgs);
      $useCategories = $sender->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      if (!$useCategories)
         $sender->CategoryID = 0;

      $sender->Category = CategoryModel::Categories($sender->CategoryID);
      if (!is_object($sender->Category))
         $sender->Category = NULL;

      if ($useCategories)
			$categoryData = CategoryModel::Categories();

      // Set the model on the form
      $sender->Form->SetModel($imageModel);
      if (!$sender->Form->IsPostBack()) {
         if ($sender->Category !== NULL)
            $sender->Form->SetData(['CategoryID' => $sender->Category->CategoryID]);
      } else { // Form was submitted
         $formValues = $sender->Form->FormValues();
         $discussionID = GetValue('DiscussionID', $formValues);
         $newDiscussion = $discussionID == 0;
         $commentIDs = [];
         $discussionID = $imageModel->Save($formValues);
         $commentIDs = $imageModel->CommentIDs;
         $sender->Form->SetValidationResults($imageModel->ValidationResults());
         if ($sender->Form->ErrorCount() == 0) {
            $discussion = $sender->DiscussionModel->GetID($discussionID);
            if ($newDiscussion) {
               // Redirect to the new discussion
               redirectTo(DiscussionUrl($discussion).'#latest');
            } elseif (count($commentIDs) > 0) {
               // Load/return the newly added comments.
               sort($commentIDs);
               $firstCommentID = array_shift($commentIDs);
               $offset = $sender->CommentModel->GetOffset($firstCommentID);
               $comments = $sender->CommentModel->Get($discussionID, 30, $offset);
               $sender->SetData('Comments', $comments);
               $sender->SetData('NewComments', TRUE);
               // $Sender->ClassName = 'DiscussionController';
               // $Sender->ControllerName = 'discussion';
               // $Sender->View = 'discussionitems';

               // Make sure to set the user's discussion watch records
               $countComments = $sender->CommentModel->GetCount($discussionID);
               $limit = count($commentIDs);
               $sender->Offset = $countComments - $limit;
               $sender->CommentModel->SetWatch($discussion, $limit, $sender->Offset, $countComments);
               $sender->Render('discussionitems', '', 'plugins/Images');
               return;
            }
         }
      }
      // Set up the page and render
      $sender->Title(T('New Image'));
		$sender->SetData('Breadcrumbs', [['Name' => $sender->Data('Title'), 'Url' => '/post/image']]);
      $this->AddJsFiles();
      $sender->AddJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugin/Reactions');

      $sender->Render('discussionform', '', 'plugins/Images');
   }

   /**
    * If the discussion type is "image", use the images view (if available)
    * @param type $sender
    */
   public function Base_BeforeCommentRender_Handler($sender) {
      $discussion = $sender->EventArguments['Discussion'];
      if (GetValue('Type', $discussion) == 'Image') {
         // $this->ClassName = 'DiscussionController';
         // $this->ControllerName = 'discussion';
         $this->View = 'discussionlist';
         $this->ApplicationFolder = 'plugins/Images';
      }
   }

   public function PostController_UploadImage_Create($sender) {
      error_reporting(E_ALL | E_STRICT);

      $paths = [
          'Upload' => PATH_UPLOADS.'/image-tmp/',
          'Thumb' => PATH_UPLOADS.'/image-tmp/thumbnails/'];
      foreach ($paths as $path) {
         TouchFolder($path);
      }

      $upload_handler = new VanillaUploadHandler([
          'script_url' => Url('post/uploadimage', TRUE),
          'upload_dir' => $paths['Upload'],
          'image_versions' => [
            'thumbnail' => [
                 'upload_dir' => $paths['Thumb']
                ]]
         ]);

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
            $url = GetIncomingValue('inputUrl');
            if ($url) {
               $upload_handler->handle_file_wget($url);
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
    * @param Gdn_Controller $sender
    */
   public function DiscussionController_Render_Before($sender) {
      $discussion = $sender->Data('Discussion');
      if (GetValue('Type', $discussion) != 'Image')
         return;

      // Include JS necessary in the page.
//      $this->AddJsFiles();

      // If the current discussion is of type "Image", switch to the images view
      $this->AddJsFiles();
      $sender->AddJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions');

      $sender->View = PATH_PLUGINS.'/Images/views/discussion.php';

      $sender->CssClass .= ' NoPanel';
   }

   public function RootController_Render_Before($sender) {
      if (InArrayI($sender->RequestMethod, ['bestof', 'bestof2'])) {
         $sender->AddJsFile('tile.js', 'plugins/Images');
      }
   }

   /* Add a toggle item to the form menu. */
   public function DiscussionController_BeforeCommentForm_Handler($sender) {
      return;
      $formToggleMenu = $sender->EventArguments['FormToggleMenu'];
      $formToggleMenu->AddLabel(Sprite('SpImage').' '.T('Image'), 'NewImageForm');
      // Is this discussion an image-type? If so, make the default response to post another image.
      if (GetValue('Type', $sender->Data('Discussion')) == 'Image')
         $formToggleMenu->CurrentLabelCode('NewImageForm');
   }

   /* Render the comment file upload form. */
   public function DiscussionController_AfterCommentFormMenu_Handler($sender) {
      $oldAction = $sender->Form->Action;
      $sender->Form->Action = Url('post/image');
      echo $sender->FetchView('commentform', '', 'plugins/Images');
      $sender->Form->Action = $oldAction;
   }
}
