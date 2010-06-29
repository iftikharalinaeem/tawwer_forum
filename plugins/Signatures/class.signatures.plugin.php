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
$PluginInfo['Signatures'] = array(
   'Name' => 'Signatures',
   'Description' => 'This plugin allows users to attach their own signatures to their posts.',
   'Version' => '1.1',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class SignaturesPlugin extends Gdn_Plugin {

   public function ProfileController_AfterAddSideMenu_Handler(&$Sender) {
      $SideMenu = $Sender->EventArguments['SideMenu'];
      $Session = Gdn::Session();
      $ViewingUserID = $Session->UserID;
      
      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', T('Signature Settings'), '/profile/signature', FALSE, array('class' => 'Popup'));
      }
   }
   
   public function ProfileController_Signature_Create(&$Sender) {
      $Session = Gdn::Session();
      $ViewingUserID = $Session->UserID;
      
      $Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigArray = array(
         'Plugin.Signature.Sig'           => NULL,
         'Plugin.Signature.HideAll'       => NULL,
         'Plugin.Signature.HideImages'    => NULL
      );
      
      $SQL = Gdn::SQL();
      $UserSig = $SQL
         ->Select('*')
         ->From('UserMeta')
         ->Where('UserID', $ViewingUserID)
         ->Like('Name', 'Plugin.Signature.%')
         ->Get();
         
      $UserMeta = array();
      if ($UserSig->NumRows())
         while ($MetaRow = $UserSig->NextRow(DATASET_TYPE_ARRAY))
            $UserMeta[$MetaRow['Name']] = $MetaRow['Value'];
      unset($UserSig);
      
      if ($Sender->Form->AuthenticatedPostBack() === FALSE)
         $ConfigArray = array_merge($ConfigArray, $UserMeta);
      
      $ConfigurationModel->SetField($ConfigArray);
      
      // Set the model on the form.
      $Sender->Form->SetModel($ConfigurationModel);
      
      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
      } else {
         $Values = $Sender->Form->FormValues();
         $FrmValues = array_intersect_key($Values, $ConfigArray);
         if (sizeof($FrmValues)) {
            foreach ($FrmValues as $UserMetaKey => $UserMetaValue) {
               try {
                  $SQL->Insert('UserMeta', array(
                        'UserID' => $ViewingUserID,
                        'Name'   => $UserMetaKey,
                        'Value'  => $UserMetaValue
                     ));
               } catch (Exception $e) {
                  $SQL
                  ->Update('UserMeta')
                  ->Set('Value', $UserMetaValue)
                  ->Where('UserID', $ViewingUserID)
                  ->Where('Name', $UserMetaKey)
                  ->Put();
               }
            }
         }
         
         $Sender->StatusMessage = T("Your changes have been saved.");
      }

      $Sender->Render($this->GetView('signature.php'));
   }
   
   public function DiscussionController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   public function PostController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   protected function PrepareController(&$Controller) {
      // Short circuit if not needed
      if ($this->_HideAllSignatures($Controller)) return;
      
      $Controller->AddCssFile($this->GetResource('design/signature.css', FALSE, FALSE));
   }
   
   public function DiscussionController_BeforeDiscussionRender_Handler(&$Sender) {
      $this->CacheSignatures($Sender);
   }
   
   public function PostController_BeforeCommentRender_Handler(&$Sender) {
      $this->CacheSignatures($Sender);
   }
   
   protected function CacheSignatures(&$Sender) {
      // Short circuit if not needed
      if ($this->_HideAllSignatures($Sender)) return;
      
      $Discussion = $Sender->Data('Discussion');
      $Comments = $Sender->Data('CommentData');
      $UserIDList = array();
      
      $UserIDList[$Discussion->InsertUserID] = 1;
      if ($Comments) {
         while ($Comment = $Comments->NextRow()) {
            $ThisUserID = $Comment->InsertUserID;
            $UserIDList[$ThisUserID] = 1;
         }
      }
      
      $UserSignatures = array();
      if (sizeof($UserIDList)) {
         $SQL = Gdn::SQL();
         $Signatures = $SQL
            ->Select('*')
            ->From('UserMeta')
            ->WhereIn('UserID', array_values($UserIDList))
            ->Where('Name', 'Plugin.Signature.Sig')
            ->Get();
         
         while ($UserSig = $Signatures->NextRow())
            $UserSignatures[$UserSig->UserID] = $UserSig->Value;
      }
      $Sender->SetData('Plugin-Signatures-UserSignatures', $UserSignatures);
   }
   
   public function GetUserSignature($UserID, $Default = NULL) {
      $SQL = Gdn::SQL();
      $UserSig = $SQL
         ->Select('*')
         ->From('UserMeta')
         ->Where('UserID', $UserID)
         ->Where('Name', 'Plugin.Signature.Sig')
         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         
      return (is_array($UserSig)) ? $UserSig['Value'] : $Default;
   }
   
   public function DiscussionController_BeforeCommentDisplay_Handler(&$Sender) {
      $this->_DrawSignature($Sender);
   }
   
   public function PostController_BeforeCommentDisplay_Handler(&$Sender) {
      $this->_DrawSignature($Sender);
   }
   
   protected function _DrawSignature(&$Sender) {
      if ($this->_HideAllSignatures($Sender)) return;
      
      if (isset($Sender->EventArguments['Discussion'])) 
         $Data = $Sender->EventArguments['Discussion'];
         
      if (isset($Sender->EventArguments['Comment'])) 
         $Data = $Sender->EventArguments['Comment'];
      
      $SourceUserID = $Data->InsertUserID;
      $UserSignatures = $Sender->Data('Plugin-Signatures-UserSignatures');
      
      if (isset($UserSignatures[$SourceUserID])) {
         $HideImages = ArrayValue('Plugin.Signature.HideImages', $Sender->Data('Plugin-Signatures-ViewingUserData'), FALSE);
         $UserSig = $HideImages ? preg_replace('/^\n/m','',str_replace("\r\n","\n",$this->_StripOnly($UserSignatures[$SourceUserID], array('img')))) : $UserSignatures[$SourceUserID];
         $Sender->UserSignature = Gdn_Format::Html($UserSig);
         $Display = $Sender->FetchView($this->GetView('usersig.php'));
         unset($Sender->UserSignature);
         $Data->Body .= $Display;
      }
   }
   
   protected function _HideAllSignatures(&$Sender) {
      // Short circuit if not needed
      if (!$Sender->Data('Plugin-Signatures-ViewingUserData')) {
         $Session = Gdn::Session();
         $ViewingUserID = $Session->UserID;
         $UserSig = $this->GetUserMeta($ViewingUserID, 'Plugin.Signature.%');
         $Sender->SetData('Plugin-Signatures-ViewingUserData',$UserSig);
      }
      
      $HideSigs = ArrayValue('Plugin.Signature.HideAll', $Sender->Data('Plugin-Signatures-ViewingUserData'), FALSE);
      if ($HideSigs == "TRUE") return TRUE;
      return FALSE;
   }
   
   protected function _StripOnly($str, $tags, $stripContent = false) {
      $content = '';
      if(!is_array($tags)) {
         $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
         if(end($tags) == '') array_pop($tags);
      }
      foreach($tags as $tag) {
         if ($stripContent)
             $content = '(.+</'.$tag.'[^>]*>|)';
          $str = preg_replace('#</?'.$tag.'[^>]*>'.$content.'#is', '', $str);
      }
      return $str;
   }

   public function Setup() {
      // Nothing to do here!
   }
   
   public function Structure() {
      // Nothing to do here!
   }
         
}