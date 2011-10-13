<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AmazonS3'] = array(
   'Name' => 'Amazon S3 Integration',
   'Description' => 'Changes most uploads on a site to use <a href="http://aws.amazon.com/s3">Amazon S3</a> instead of the /uploads folder.',
   'Version' => '1.0a',
   'RequiredApplications' => array('Vanilla' => '2.0.17.7a'),
   'SettingsUrl' => '/settings/amazons3',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class AmazonS3Plugin extends Gdn_Plugin {
   /// PROPERTIES ///

   /// METHODS ///

   /**
    *
    * @return S3
    */
   public function S3() {
      static $S3 = NULL;
      if ($S3 === NULL) {
         $S3 = new S3(C('Plugins.AmazonS3.AccessID'), C('Plugins.AmazonS3.Secret'));
      }
      return $S3;
   }

   /// EVENT HANDLERS ///

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function SettingsController_AmazonS3_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
          'Plugins.AmazonS3.AccessID',
          'Plugins.AmazonS3.Secret',
          'Plugins.AmazonS3.Bucket'
      ));

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Amazon S3 Settings'));
      $Sender->ConfigurationModule = $Conf;
//      $Conf->RenderAll();
      $Sender->Render('Settings', '', 'plugins/AmazonS3');
   }

   public function Gdn_Upload_CopyLocal_Handler($Sender, $Args) {
      $Parsed = $Args['Parsed'];
      if ($Parsed['Type'] != 's3')
         return;

      $S3 = $this->S3();
      $Bucket = C('Plugins.AmazonS3.Bucket');
      $DestPath = PATH_UPLOADS.'/s3/'.$Parsed['Name'];
      if (!file_exists(dirname($DestPath)))
         mkdir(dirname($DestPath), 0777, TRUE);
      $S3->getObject($Bucket, $Parsed['Name'], $DestPath);
      $Args['Path'] = $DestPath;
   }

   public function Gdn_Upload_Delete_Handler($Sender, $Args) {
      if ($Args['Parsed']['Type'] != 's3')
         return;

      $S3 = $this->S3();
      $Bucket = C('Plugins.AmazonS3.Bucket');
      $S3->deleteObject($Bucket, $Args['Parsed']['Name']);
      $Args['Handled'] = TRUE;
   }

   public function Gdn_Upload_SaveAs_Handler($Sender, $Args) {
      $S3 = $this->S3();

      $Path = $Args['Path'];
      $Bucket = C('Plugins.AmazonS3.Bucket');
      $Result = $S3->putObjectFile($Path, $Bucket, $Args['Parsed']['Name'], S3::ACL_PUBLIC_READ);
      if ($Result) {
         $Parsed = Gdn_Upload::Parse('~s3/'.$Args['Parsed']['Name']);
         $Args['Parsed'] = $Parsed;
         @unlink($Path);
         $Args['Handled'] = TRUE;
      } else {
         throw new Exception('There was an error saving the file to S3.', 500);
      }
   }

   public function Gdn_Upload_GetUrls_Handler($Sender, $Args) {
      $Bucket = C('Plugins.AmazonS3.Bucket');
      if ($Bucket) {
         $Args['Urls']['s3'] = 'http://s3.amazonaws.com/'.trim($Bucket, '/');
      }
   }


   public function Gdn_UploadImage_SaveImageAs_Handler($Sender, $Args) {
      $S3 = $this->S3();

      $Path = $Args['Path'];
      $Bucket = C('Plugins.AmazonS3.Bucket');
      $Result = $S3->putObjectFile($Path, $Bucket, $Args['Parsed']['Name'], S3::ACL_PUBLIC_READ);
      if ($Result) {
         $Parsed = Gdn_Upload::Parse('~s3/'.$Args['Parsed']['Name']);
         $Args['Parsed'] = $Parsed;
         @unlink($Path);
      } else {
         throw new Exception('There was an error saving the file to S3.', 500);
      }
   }
}