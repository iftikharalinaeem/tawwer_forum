<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
 */
if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['InpsumInserter'] = array(
   'Name' => 'InpsumInserter',
   'Description' => "InpsumInserter",
   'Version' => '0.0.1',
   'RequiredApplications' => array('Vanilla' => '2.1.18'),
   'SettingsPermission' => 'Garden.Settings.Manage',
   'SettingsUrl' => '/plugin/IpsumInserter',
   'Author' => 'John Ashton',
   'AuthorEmail' => 'john@vanillaforums.com',
   'AuthorUrl' => 'http://www.github.com/John0x00'
);


class IpsumInserterPlugin extends Gdn_Plugin {

   /**
    * Creates the Virtual Controller
    *
    * @param Controller $Sender
    */
   public function PluginController_IpsumInserter_Create($Sender) {

      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('IpsumInserter');
      $Sender->AddSideMenu('plugin/IpsumInserter');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   public function Controller_Index($Sender) {

      $Sender->Form = new Gdn_Form();

      // If form is being submitted
      if ($Sender->Form->IsPostBack() && $Sender->Form->AuthenticatedPostBack() === TRUE) {

         // Form Validation
         $Sender->Form->ValidateRule('DiscussionNumber', 'function:ValidateRequired', 'DiscussionNumber is required');
         $Sender->Form->ValidateRule('DiscussionNumber', 'function:ValidateInteger', 'DiscussionNumber must be numeric');
         $Sender->Form->ValidateRule('CommentNumber', 'function:ValidateRequired', 'CommentNumber is required');
         $Sender->Form->ValidateRule('CommentNumber', 'function:ValidateInteger', 'CommentNumber must be numeric');

         // If no errors
         if ($Sender->Form->ErrorCount() == 0) {
            $FormValues = $Sender->Form->FormValues();


            $DiscussionModel = new DiscussionModel();
            $CommentModel = new CommentModel();

            for ($i=0; $i < $FormValues['DiscussionNumber']; $i++) {
               $Ipsum = $this->GetIpsum(700, $FormValues['IpsumType']);
               $Fields = array(
                  'Name' => substr($Ipsum, 0, rand(25, 100)),
                  'Body' => substr($Ipsum, 0, rand(100, 700)),
                  'CategoryID' => 1
               );
               $DiscussionID = $DiscussionModel->Save($Fields);

               for ($b=0; $b < $FormValues['CommentNumber']; $b++) {
                  $Ipsum = $this->GetIpsum(500, $FormValues['IpsumType']);
                  $Fields = array(
                     'DiscussionID' => $DiscussionID,
                     'Body' => substr($Ipsum, 0, rand(100, 700))
                  );
                  $CommentModel->Save($Fields);
               }
            }
            $Sender->SetData('Message', 'Discussions and Comments Created');
            $Sender->InformMessage('Discussions and Comments Created.');

         }
      }

      $Sender->Render($this->GetView('form.php'));
      return;


   }

   public function GetIpsum($MaxLength = 500, $IpsumType = 'lorem') {
      if ($IpsumType == 'gangsta') {
         $Ipsum = $this->GetGanstaIpsum();
      } else {
         $Ipsum = $this->GetLoremIpsum();
      }

      return substr($Ipsum, 0, $MaxLength);
   }

   public function GetLoremIpsum() {
      $Proxy = new ProxyRequest();

      $Options['URL'] = 'http://www.lipsum.com/feed/html';
      $Response = $Proxy->Request($Options);

      $Ipsum = stristr($Response, "<div id=\"lipsum\">\n<p>\n");
      $Ipsum = str_replace("<div id=\"lipsum\">\n<p>\n", '', $Ipsum);
      $Ipsum = stristr($Ipsum, "\n</p></div>\n<div id=\"generated\">", TRUE);
      $Ipsum = str_replace("</p>\n", '', $Ipsum);
      $Ipsum = str_replace("<p>", '', $Ipsum);

      return $Ipsum;
   }

   public function GetGanstaIpsum() {
      $Proxy = new ProxyRequest();

      $Options['URL'] = 'http://lorizzle.nl/?feed=1';
      $Response = $Proxy->Request($Options);

      $Ipsum = stristr($Response, '<div class="lipsum"><p>');
      $Ipsum = str_replace('<div class="lipsum"><p>', '' , $Ipsum);
      $Ipsum = stristr($Ipsum, '</p></div>', TRUE);

      return $Ipsum;
   }
}

