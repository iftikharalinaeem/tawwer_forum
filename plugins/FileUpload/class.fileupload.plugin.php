<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['FileUpload'] = array(
   'Description' => 'This plugin enables file uploads and attachments to discussions, comments and conversations.',
   'Version' => '1.0',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => array('Plugins.Attachments.Upload.Allow','Plugins.Attachments.Download.Allow'),
   'SettingsUrl' => '/dashboard/plugin/fileupload',
   'SettingsPermission' => 'Garden.AdminUser.Only',
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class FileUploadPlugin extends Gdn_Plugin {

   /**
    * Adds "Media" menu option to the Forum menu on the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', 'Forum');
      $Menu->AddLink('Forum', 'Media', 'plugin/fileupload', 'Garden.AdminUser.Only');
   }
   
   public function PluginController_FileUpload_Create(&$Sender) {
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('FileUpload');
      $Sender->AddSideMenu('plugin/fileupload');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Toggle(&$Sender) {
      $FileUploadStatus = Gdn::Config('Plugins.FileUpload.Enabled', FALSE);

      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array('FileUploadStatus'));
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      if ($Sender->Form->AuthenticatedPostBack()) {
         $FileUploadStatus = ($Sender->Form->GetValue('FileUploadStatus') == 'ON') ? TRUE : FALSE;
         SaveToConfig('Plugins.FileUpload.Enabled', $FileUploadStatus);
      }
      
      $Sender->SetData('FileUploadStatus', $FileUploadStatus);
      $Sender->Form->SetData(array(
         'FileUploadStatus'  => $FileUploadStatus
      ));
      $Sender->Render($this->GetView('toggle.php'));
   }
   
   public function Controller_Index(&$Sender) {
      $Sender->AddCssFile($this->GetWebResource('css/fileupload.css'));
      $Sender->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      $Sender->AddCssFile('admin.css');
      
      $this->EnableSlicing($Sender);
      
      $PermissionModel = Gdn::PermissionModel();
      $RoleModel = new RoleModel();
      $Roles = $RoleModel->Get();
      $Sender->SetData('Roles',$Roles);
/*
      
      $RoleGrid = array(
         '_Role'     => array(
            '_Columns'  => array(
               'Download'  => 1,
               'Upload'    => 1
            ),
            '_Info' => array(
               'Name'      => 'Role'
            ),
            '_Rows'        => array()
         )
      );
      $PermissionName = 'FileUpload.%s.%s';
      while ($Role = $Roles->NextRow()) {
         if (!$Role->CanSession) continue;
         $Permissions = $PermissionModel->GetPermissions($Role->RoleID);
         
         $RoleGrid['_Role']['_Rows'][$Role->Name] = 1;

         $UploadPermission = sprintf($PermissionName,$Role->Name,'Upload.File');
         $DownloadPermission = sprintf($PermissionName,$Role->Name,'Download.File');
         $RoleGrid['_Role'][$Role->Name.'.Upload'] = array(
            'Value'     => $Permissions->$UploadPermission,
            'PostValue' => $UploadPermission
         );
         
         $RoleGrid['_Role'][$Role->Name.'.Download'] = array(
            'Value'     => $Permissions->$DownloadPermission,
            'PostValue' => $DownloadPermission
         );

      }
      $Sender->FileUploadPermissions = $RoleGrid;
*/
      
      $Sender->Render($this->GetView('fileupload.php'));
   }

   public function DiscussionController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   public function PostController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   protected function PrepareController(&$Controller) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
      
      $Controller->AddCssFile($this->GetResource('css/fileupload.css', FALSE, FALSE));
      $Controller->AddJsFile('js/library/jquery.class.js');
      $Controller->AddJsFile($this->GetResource('js/fileupload.js', FALSE, FALSE));
   }
   
   /**
    * PostController_BeforeFormButtons_Handler function.
    *
    * Event hook that allows plugin to insert the file uploader UI into the 
    * Post Discussion and Post Comment forms.
    * 
    * @access public
    * @param mixed &$Sender
    * @return void
    */
   public function PostController_BeforeFormButtons_Handler(&$Sender) {
      $this->DrawAttachFile($Sender);
   }
   
   public function DiscussionController_BeforeFormButtons_Handler(&$Sender) {
      $this->DrawAttachFile($Sender);
   }
   
   public function DrawAttachFile(&$Sender) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
      
      $PostMaxSize = Gdn_Upload::UnformatFileSize(ini_get('post_max_size'));
      $FileMaxSize = Gdn_Upload::UnformatFileSize(ini_get('upload_max_filesize'));
      
      $this->MaxUploadSize = ($PostMaxSize > $FileMaxSize) ? $PostMaxSize : $FileMaxSize;
      $this->GetResource('views/attach_file.php', TRUE);
   }
   
   public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
      // Cache the list of media. Don't want to do multiple queries!
      $this->CacheAttachedMedia($Sender);
   }
   
   public function PostController_BeforeCommentRender_Handler(&$Sender) {
      // Cache the list of media. Don't want to do multiple queries!
      $this->CacheAttachedMedia($Sender);
   }
   
   protected function CacheAttachedMedia(&$Sender) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
      
      $Comments = $Sender->Data('CommentData');
      $CommentIDList = array();
      
      if ($Comments)
         while ($Comment = $Comments->NextRow())
            $CommentIDList[] = $Comment->CommentID;
      
      $MediaModel = new MediaModel();
      $MediaData = $MediaModel->PreloadDiscussionMedia($Sender->DiscussionID, $CommentIDList);

      $MediaArray = array();
      while ($Media = $MediaData->NextRow())
         $MediaArray[$Media->ForeignTable.'/'.$Media->ForeignID][] = $Media;
         
      $Sender->SetData('FileUploadMedia', $MediaArray);
   }
   
   public function DiscussionController_AfterCommentBody_Handler(&$Sender) {
      $this->AttachUploadsToComment($Sender);
   }
   
   public function PostController_AfterCommentBody_Handler(&$Sender) {
      $this->AttachUploadsToComment($Sender);
   }
   
   protected function AttachUploadsToComment(&$Sender) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
      
      $Type = strtolower($RawType = $Sender->EventArguments['Type']);
      $MediaList = $Sender->Data('FileUploadMedia');
      $Param = (($Type == 'comment') ? 'CommentID' : 'DiscussionID');
      $MediaKey = $Type.'/'.$Sender->EventArguments[$RawType]->$Param;
      if (array_key_exists($MediaKey, $MediaList)) {
         $this->CommentMediaList = $MediaList[$MediaKey];
         $this->GetResource('views/link_files.php', TRUE);
      }
   }
   
   public function DiscussionController_Download_Create(&$Sender) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
   
      list($MediaID) = $Sender->RequestArgs;
      $MediaModel = new MediaModel();
      $Media = $MediaModel->GetID($MediaID);
      
      if (!$Media) return;
      
      $Filename = Gdn::Request()->Filename();
      if (!$Filename) $Filename = $Media->Name;
      
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: inline;filename='.urlencode($Filename));
      
      $DownloadPath = FileUploadPlugin::FindLocalMedia($Media, TRUE, TRUE);
      if (file_exists($DownloadPath)) {
         readfile($DownloadPath);
      } else {
         throw new Exception('File could not be streamed: missing file ('.$DownloadPath.').');
      }
      exit();
   }
   
   public function PostController_AfterCommentSave_Handler(&$Sender) {
      if (!$Sender->EventArguments['Comment']) return;
      
      $CommentID = $Sender->EventArguments['Comment']->CommentID;
      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      $AllFilesData = Gdn::Request()->GetValue('AllUploads');
      
      $this->AttachAllFiles($AttachedFilesData, $AllFilesData, $CommentID, 'comment');
   }
   
   public function PostController_AfterDiscussionSave_Handler(&$Sender) {
      if (!$Sender->EventArguments['Discussion']) return;
      
      $DiscussionID = $Sender->EventArguments['Discussion']->DiscussionID;
      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      $AllFilesData = Gdn::Request()->GetValue('AllUploads');
      
      $this->AttachAllFiles($AttachedFilesData, $AllFilesData, $DiscussionID, 'discussion');
   }
   
   protected function AttachAllFiles($AttachedFilesData, $AllFilesData, $ForeignID, $ForeignTable) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
      
      if (!$AttachedFilesData) return;
      
      $SuccessFiles = array();
      foreach ($AttachedFilesData as $FileID) {
         $Attached = $this->AttachFile($FileID, $ForeignID, $ForeignTable);
         if ($Attached)
            $SuccessFiles[] = $FileID;
      }
         
      // clean up failed and unattached files
      $DeleteIDs = array_diff($AllFilesData, $SuccessFiles);
      foreach ($DeleteIDs as $DeleteID) {
         $this->TrashFile($DeleteID);
      }
   }
   
   protected function AttachFile($FileID, $ForeignID, $ForeignType) {
      $MediaModel = new MediaModel();
      $Media = $MediaModel->GetID($FileID);
      if ($Media) {
         $Media->ForeignID = $ForeignID;
         $Media->ForeignTable = $ForeignType;
         $PlacementStatus = $this->PlaceMedia($Media, Gdn::Session()->UserID);
         if ($PlacementStatus) {
            $MediaModel->Save($Media);
            return TRUE;
         }
      }
      return FALSE;
   }
   
   protected function TrashFile($FileID) {
      $MediaModel = new MediaModel();
      $Media = $MediaModel->GetID($FileID);
      
      if ($Media) {
         $MediaModel->Delete($Media);
         $Deleted = FALSE;
         
         if (!$Deleted) {
            $DirectPath = PATH_UPLOADS.DS.$Media->Path;
            if (file_exists($DirectPath))
               $Deleted = @unlink($DirectPath);
         }
         
         if (!$Deleted) {
            $CalcPath = FileUploadPlugin::FindLocalMedia($Media, TRUE, TRUE);
            if (file_exists($CalcPath))
               $Deleted = @unlink($CalcPath);
         }
         
      }
   }
   
   protected function PlaceMedia(&$Media, $UserID) {
      $NewFolder = FileUploadPlugin::FindLocalMediaFolder($Media->MediaID, $UserID, TRUE, FALSE);
      
      $CurrentPath = array();
      foreach ($NewFolder as $FolderPart) {
         array_push($CurrentPath, $FolderPart);
         $TestFolder = implode(DS, $CurrentPath);
         if (!is_dir($TestFolder))
            @mkdir($TestFolder);
         if (!is_dir($TestFolder))
            return false;
      }
      $FileParts = pathinfo($Media->Name);
      $NewFilePath = implode(DS,array($TestFolder,$Media->MediaID.'.'.$FileParts['extension']));
      $Success = @rename(PATH_UPLOADS.DS.$Media->Path, $NewFilePath);
      if (!$Success) {
         return false;
      }
      
      $NewFilePath = FileUploadPlugin::FindLocalMedia($Media, FALSE, TRUE);
      $Media->Path = $NewFilePath;
      
      return true;
   }
   
   public static function FindLocalMediaFolder($MediaID, $UserID, $Absolute = FALSE, $ReturnString = FALSE) {
      $DispersionFactor = 20;
      $FolderID = $UserID % 20;
      $ReturnArray = array('FileUpload',$FolderID,$UserID);
      
      if ($Absolute)
         array_unshift($ReturnArray, PATH_UPLOADS);
      
      return ($ReturnString) ? implode(DS,$ReturnArray) : $ReturnArray;
   }
   
   public static function FindLocalMedia($Media, $Absolute = FALSE, $ReturnString = FALSE) {
      $ArrayPath = FileUploadPlugin::FindLocalMediaFolder($Media->MediaID, $Media->InsertUserID, $Absolute, FALSE);
      
      $FileParts = pathinfo($Media->Name);
      $RealFileName = $Media->MediaID.'.'.$FileParts['extension'];
      array_push($ArrayPath, $RealFileName);
      
      return ($ReturnString) ? implode(DS, $ArrayPath) : $ArrayPath;
   }
   
   /**
    * PostController_Upload_Create function.
    * 
    * Controller method that allows plugin to handle ajax file uploads
    *
    * @access public
    * @param mixed &$Sender
    * @return void
    */
   public function PostController_Upload_Create(&$Sender) {
      if (!Gdn::Config('Plugins.FileUpload.Enabled')) return;
      
      list($FieldName) = $Sender->RequestArgs;
      
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      
      $Sender->FieldName = $FieldName;
      $Sender->ApcKey = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_POST,'APC_UPLOAD_PROGRESS');
      $MediaModel = new MediaModel();
      
      if ($Sender->Form->IsPostBack()) {
      
         // this will hold the IDs and filenames of the items we were sent. booyahkashaa.
         $MediaResponse = array();
      
         $FileData = Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_FILES, $FieldName, FALSE);
         if ($FileData) {
            // Validate the file upload now.
            $FileErr  = $FileData['error'];
            $FileType = $FileData['type'];
            $FileName = $FileData['name'];
            $FileTemp = $FileData['tmp_name'];
            $FileSize = $FileData['size'];

            if ($FileErr) {
               $MediaResponse = array(
                  'Status'          => 'failed',
                  'ErrorCode'       => $FileErr,
                  'Filename'        => $FileName,
                  'ProgressKey'     => $Sender->ApcKey
               );
            } else {
                           
               $ScratchFolder = array('FileUpload','scratch');
               $ScratchPath = PATH_UPLOADS.DS.CombinePaths($ScratchFolder);
               if (!is_dir($ScratchPath))
                  @mkdir($ScratchPath);
                  
               if (!is_dir($ScratchPath)) { break; }
               
               $ScratchFileName = CombinePaths(array($ScratchPath,basename($FileTemp)));
               $MoveSuccess = @move_uploaded_file($FileTemp, $ScratchFileName);
               
               if (!$MoveSuccess) { continue; }
               
               $MediaID = $MediaModel->Save(array(
                  'Name'            => $FileName,
                  'Type'            => $FileType,
                  'Size'            => $FileSize,
                  'InsertUserID'    => Gdn::Session()->UserID,
                  'DateInserted'    => time(),
                  'StorageMethod'   => 'local',
                  'Path'            => CombinePaths(array_merge($ScratchFolder,array(basename($FileTemp))))
               ));
               
               $MediaResponse = array(
                  'Status'          => 'success',
                  'MediaID'         => $MediaID,
                  'Filename'        => $FileName,
                  'ProgressKey'     => $Sender->ApcKey
               );
               
            }
         }
         
         $Sender->SetJSON('MediaResponse', $MediaResponse);
      }
      
      $Sender->Render($this->GetView('confirm_file.php'));
   }
   
   /**
    * PostController_Checkupload_Create function.
    *
    * Controller method that allows an AJAX call to check the progress of a file
    * upload that is currently in progress.
    * 
    * @access public
    * @param mixed &$Sender
    * @return void
    */
   public function PostController_Checkupload_Create(&$Sender) {
      list($ApcKey) = $Sender->RequestArgs;
      
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      
      $KeyData = explode('_',$ApcKey);
      array_shift($KeyData);
      $UploaderID = implode('_',$KeyData);
      
      $UploadStatus = apc_fetch('upload_'.$ApcKey, $Success);
      
      $Progress = array(
         'key'          => $ApcKey,
         'uploader'     => $UploaderID
      );
      
/*
      if ($Success) {
         $Progress['progress'] = ($UploadStatus['current'] / $UploadStatus['total']) * 100;
         $Progress['total'] = $UploadStatus['total'];
      } else {
         $Progress['progress'] = 0;
         $Progress['total'] = -1;
      }
*/
      
      if (!$Success)
         $UploadStatus = array(
            'current'   => 0,
            'total'     => -1
         );
         
      $Progress['progress'] = ($UploadStatus['current'] / $UploadStatus['total']) * 100;
      $Progress['total'] = $UploadStatus['total'];
         
      
      $Progress['format_total'] = Gdn_Format::Bytes2String($Progress['total'],1);
      $Progress['cache'] = $UploadStatus;
      
      $Sender->SetJSON('Progress', $Progress);
      $Sender->Render($this->GetView('confirm_file.php'));
   }
   
   public function Setup() {

      $Structure = Gdn::Structure();
      $Structure
         ->Table('Media')
         ->PrimaryKey('MediaID')
         ->Column('Name', 'varchar(255)')
         ->Column('Type', 'varchar(64)')
         ->Column('Size', 'int(11)')
         ->Column('StorageMethod', 'varchar(24)')
         ->Column('Path', 'varchar(255)')
         ->Column('InsertUserID', 'int(11)')
         ->Column('DateInserted', 'datetime')
         ->Column('ForeignID', 'int(11)', TRUE)
         ->Column('ForeignTable', 'varchar(24)', TRUE)
         ->Set(FALSE, FALSE);
 
      Gdn_FileCache::SafeCache('library','class.mediamodel.php',$this->GetResource('models/class.mediamodel.php'));
      
      SaveToConfig('Plugins.FileUpload.Enabled', TRUE);
   }

   public function OnDisable() {
      SaveToConfig('Plugins.FileUpload.Enabled', FALSE);
   }
   
}
