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
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class FileUploadPlugin extends Gdn_Plugin {

   /**
    * Adds "Media" menu option to the Forum menu on the dashboard.
    */
/*
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', 'Forum');
      $Menu->AddLink('Forum', 'Media', 'plugin/fileupload', 'Garden.AdminUser.Only');
   }
*/

   public function DiscussionController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   public function PostController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   protected function PrepareController(&$Controller) {
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
      $this->GetResource('views/attach_file.php', TRUE);
   }
   
   public function DiscussionController_BeforeFormButtons_Handler(&$Sender) {
      $this->GetResource('views/attach_file.php', TRUE);
   }
   
   public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
      // Cache the list of media. Don't want to do multiple queries!
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
      list($MediaID) = $Sender->RequestArgs;
      $MediaModel = new MediaModel();
      $Media = $MediaModel->GetID($MediaID);
      
      if (!$Media) return;
      
      $Filename = Gdn::Request()->Filename();
      if (!$Filename) $Filename = $Media->Name;
      
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: inline;filename='.$Filename);
      readfile($Media->Path);
      exit();
   }
   
   public function PostController_AfterCommentSave_Handler(&$Sender) {
      $CommentID = $Sender->EventArguments['Comment']->CommentID;
      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      if (!$AttachedFilesData) return;
      foreach ($AttachedFilesData as $FileID)
         $this->AttachFile($FileID, $CommentID, 'comment');
   }
   
   public function PostController_AfterDiscussionSave_Handler(&$Sender) {
      $DiscussionID = $Sender->EventArguments['Discussion']->DiscussionID;
      $AttachedFilesData = Gdn::Request()->GetValue('AttachedUploads');
      if (!$AttachedFilesData) return;
      foreach ($AttachedFilesData as $FileID)
         $this->AttachFile($FileID, $DiscussionID, 'discussion');
   }
   
   protected function AttachFile($FileID, $ForeignID, $ForeignType) {
      $SQL = Gdn::Database()->SQL();
      $MediaModel = new MediaModel();
      $Media = $MediaModel->GetID($FileID);
      if ($Media) {
         $Media->ForeignID = $ForeignID;
         $Media->ForeignTable = $ForeignType;
         $this->PlaceMedia($Media, Gdn::Session()->UserID);
         $MediaModel->Save($Media);
      }
   }
   
   protected function PlaceMedia(&$Media, $UserID) {
      $NewFolder = FileUploadPlugin::FindLocalMedia($Media->MediaID, $UserID);
      
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
      rename($Media->Path, $NewFilePath);
      $Media->Path = $NewFilePath;
   }
   
   public static function FindLocalMedia($MediaID, $UserID) {
      $DispersionFactor = 20;
      $FolderID = $UserID % 20;
      return array(PATH_UPLOADS,'attachments',$FolderID,$UserID);
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
            if ($FileErr) { continue; }
            
            $FileType = $FileData['type'];
            $FileName = $FileData['name'];
            $FileTemp = $FileData['tmp_name'];
            $FileSize = $FileData['size'];
            
            $TemporaryScratchFolder = CombinePaths(array(PATH_UPLOADS,'scratch'));
            if (!is_dir($TemporaryScratchFolder))
               @mkdir($TemporaryScratchFolder);
               
            if (!is_dir($TemporaryScratchFolder)) { break; }
            
            $ScratchFileName = CombinePaths(array($TemporaryScratchFolder,basename($FileTemp)));
            $MoveSuccess = @move_uploaded_file($FileTemp, $ScratchFileName);
            
            if (!$MoveSuccess) { continue; }
            
            $MediaID = $MediaModel->Save(array(
               'Name'            => $FileName,
               'Type'            => $FileType,
               'Size'            => $FileSize,
               'InsertUserID'    => Gdn::Session()->UserID,
               'DateInserted'    => time(),
               'StorageMethod'   => 'local',
               'Path'            => $ScratchFileName
            ));
            
            $MediaResponse = array(
               'MediaID'      => $MediaID,
               'Filename'     => $FileName,
               'ProgressKey'  => $Sender->ApcKey
            );

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
      
      $UploadStatus = apc_fetch('upload_'.$ApcKey);
      
      if (is_array($UploadStatus)) {
         $NewProgress = ($UploadStatus['current'] / $UploadStatus['total']) * 100;
      } else {
         $NewProgress = 0;
      }
      
      $Progress = array(
         'progress'     => $NewProgress,
         'key'          => $ApcKey,
         'uploader'     => $UploaderID,
         'total'        => $UploadStatus['total'],
         'format_total' => Gdn_Format::Bytes2String($UploadStatus['total'],0)
      );
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
   }

   public function OnDisable() {}
   
}
