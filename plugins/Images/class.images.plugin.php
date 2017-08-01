<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

class ImagesPlugin extends Gdn_Plugin {
   /// Methods ///

   public function setup() {
      $this->structure();
   }

   public function structure() {
      include dirname(__FILE__).'/structure.php';
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function addJsFiles($sender = NULL) {
      if (!$sender)
         $sender = Gdn::controller();

      // Include JS necessary in the page.
      $sender->addJsFile('library/jQuery-FileUpload/js/vendor/jquery.ui.widget.js', 'plugins/Images');
      // The Templates plugin is included to render the upload/download listings.
      $sender->addJsFile('library/Javascript-Templates/tmpl.min.js', 'plugins/Images');
      // The Load Image plugin is included for the preview images and image resizing functionality.
      $sender->addJsFile('library/Javascript-LoadImage/load-image.min.js', 'plugins/Images');
      // The Canvas to Blob plugin is included for image resizing functionality.
      $sender->addJsFile('library/JavaScript-Canvas-to-Blob/canvas-to-blob.min.js', 'plugins/Images');
      // The Iframe Transport is required for browsers without support for XHR file uploads.
      $sender->addJsFile('library/jQuery-FileUpload/js/jquery.iframe-transport.js', 'plugins/Images');
      // The basic File Upload plugin.
      $sender->addJsFile('library/jQuery-FileUpload/js/jquery.fileupload.js', 'plugins/Images');
      // The File Upload file processing plugin.
      $sender->addJsFile('library/jQuery-FileUpload/js/jquery.fileupload-fp.js', 'plugins/Images');
      // The File Upload user interface plugin.
      $sender->addJsFile('library/jQuery-FileUpload/js/jquery.fileupload-ui.js', 'plugins/Images');
      // The localization script.
      $sender->addJsFile('library/jQuery-FileUpload/js/locale.js', 'plugins/Images');
      // The main application script.
      $sender->addJsFile('upload.js', 'plugins/Images');
      // The XDomainRequest Transport is included for cross-domain file deletion for IE8+.
      $sender->Head->addString('<!--[if gte IE 8]><script src="'.url('plugins/Images/library/jQuery-FileUpload/js/cors/jquery.xdr-transport.js').'"></script><![endif]-->');
   }

   /// Event Handlers ///

   public function assetModel_styleCss_handler($sender, $args) {
      $sender->addCssFile('images.css', 'plugins/Images');
   }

   /**
    * Add the "new image" button after the new discussion button.
    */
   public function base_beforeNewDiscussionButton_handler($sender) {
      $newDiscussionModule = &$sender->EventArguments['NewDiscussionModule'];
      if (Gdn::session()->checkPermission('Plugins.Images.Add'))
         $newDiscussionModule->addButton(t('New Image'), 'post/image');
   }

   /**
    * Display the Image label on the discussion list.
    */
   public function base_beforeDiscussionMeta_handler($sender) {
      $discussion = $sender->EventArguments['Discussion'];
      if (strcasecmp(getValue('Type', $discussion), 'Image') == 0)
         echo tag($discussion, 'Type', 'Image');
   }

   /**
    * Add the image form to vanilla's post page.
    */
   public function postController_afterForms_handler($sender) {
      $forms = $sender->data('Forms');
      if (!is_array($forms))
         $forms = [];

      $forms[] = ['Name' => 'Image', 'Label' => sprite('SpImage').t('New Image'), 'Url' => 'post/image'];
		$sender->setData('Forms', $forms);

   }

   /**
    * Create the new image method on post controller.
    */
   public function postController_image_create($sender) {
      // Check permission
      $sender->permission('Vanilla.Discussions.Add');
      $sender->permission('Plugins.Images.Add');

      $imageModel = new ImageModel();

      // Override CategoryID if categories are disabled
      $sender->CategoryID = getValue(0, $sender->RequestArgs);
      $useCategories = $sender->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
      if (!$useCategories)
         $sender->CategoryID = 0;

      $sender->Category = CategoryModel::categories($sender->CategoryID);
      if (!is_object($sender->Category))
         $sender->Category = NULL;

      if ($useCategories)
			$categoryData = CategoryModel::categories();

      // Set the model on the form
      $sender->Form->setModel($imageModel);
      if (!$sender->Form->isPostBack()) {
         if ($sender->Category !== NULL)
            $sender->Form->setData(['CategoryID' => $sender->Category->CategoryID]);
      } else { // Form was submitted
         $formValues = $sender->Form->formValues();
         $discussionID = getValue('DiscussionID', $formValues);
         $newDiscussion = $discussionID == 0;
         $commentIDs = [];
         $discussionID = $imageModel->save($formValues);
         $commentIDs = $imageModel->CommentIDs;
         $sender->Form->setValidationResults($imageModel->validationResults());
         if ($sender->Form->errorCount() == 0) {
            $discussion = $sender->DiscussionModel->getID($discussionID);
            if ($newDiscussion) {
               // Redirect to the new discussion
               redirectTo(discussionUrl($discussion).'#latest');
            } elseif (count($commentIDs) > 0) {
               // Load/return the newly added comments.
               sort($commentIDs);
               $firstCommentID = array_shift($commentIDs);
               $offset = $sender->CommentModel->getOffset($firstCommentID);
               $comments = $sender->CommentModel->get($discussionID, 30, $offset);
               $sender->setData('Comments', $comments);
               $sender->setData('NewComments', TRUE);
               // $Sender->ClassName = 'DiscussionController';
               // $Sender->ControllerName = 'discussion';
               // $Sender->View = 'discussionitems';

               // Make sure to set the user's discussion watch records
               $countComments = $sender->CommentModel->getCount($discussionID);
               $limit = count($commentIDs);
               $sender->Offset = $countComments - $limit;
               $sender->CommentModel->setWatch($discussion, $limit, $sender->Offset, $countComments);
               $sender->render('discussionitems', '', 'plugins/Images');
               return;
            }
         }
      }
      // Set up the page and render
      $sender->title(t('New Image'));
		$sender->setData('Breadcrumbs', [['Name' => $sender->data('Title'), 'Url' => '/post/image']]);
      $this->addJsFiles();
      $sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugin/Reactions');

      $sender->render('discussionform', '', 'plugins/Images');
   }

   /**
    * If the discussion type is "image", use the images view (if available)
    * @param type $sender
    */
   public function base_beforeCommentRender_handler($sender) {
      $discussion = $sender->EventArguments['Discussion'];
      if (getValue('Type', $discussion) == 'Image') {
         // $this->ClassName = 'DiscussionController';
         // $this->ControllerName = 'discussion';
         $this->View = 'discussionlist';
         $this->ApplicationFolder = 'plugins/Images';
      }
   }

   public function postController_uploadImage_create($sender) {
      error_reporting(E_ALL | E_STRICT);

      $paths = [
          'Upload' => PATH_UPLOADS.'/image-tmp/',
          'Thumb' => PATH_UPLOADS.'/image-tmp/thumbnails/'];
      foreach ($paths as $path) {
         touchFolder($path);
      }

      $upload_handler = new VanillaUploadHandler([
          'script_url' => url('post/uploadimage', TRUE),
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
            $url = getIncomingValue('inputUrl');
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
   public function discussionController_render_before($sender) {
      $discussion = $sender->data('Discussion');
      if (getValue('Type', $discussion) != 'Image')
         return;

      // Include JS necessary in the page.
//      $this->addJsFiles();

      // If the current discussion is of type "Image", switch to the images view
      $this->addJsFiles();
      $sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions');

      $sender->View = PATH_PLUGINS.'/Images/views/discussion.php';

      $sender->CssClass .= ' NoPanel';
   }

   public function rootController_render_before($sender) {
      if (inArrayI($sender->RequestMethod, ['bestof', 'bestof2'])) {
         $sender->addJsFile('tile.js', 'plugins/Images');
      }
   }

   /* Add a toggle item to the form menu. */
   public function discussionController_beforeCommentForm_handler($sender) {
      return;
      $formToggleMenu = $sender->EventArguments['FormToggleMenu'];
      $formToggleMenu->addLabel(sprite('SpImage').' '.t('Image'), 'NewImageForm');
      // Is this discussion an image-type? If so, make the default response to post another image.
      if (getValue('Type', $sender->data('Discussion')) == 'Image')
         $formToggleMenu->currentLabelCode('NewImageForm');
   }

   /* Render the comment file upload form. */
   public function discussionController_afterCommentFormMenu_handler($sender) {
      $oldAction = $sender->Form->Action;
      $sender->Form->Action = url('post/image');
      echo $sender->fetchView('commentform', '', 'plugins/Images');
      $sender->Form->Action = $oldAction;
   }
}
