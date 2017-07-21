<?php
/**
 * @copyright 2014 Vanilla Forums Inc.
 * @license Proprietary
 */
if (!defined('APPLICATION')) exit();

class IpsumInserterPlugin extends Gdn_Plugin {

   /**
    * Creates the Virtual Controller
    *
    * @param Controller $sender
    */
   public function PluginController_IpsumInserter_Create($sender) {

      $sender->Permission('Garden.Settings.Manage');
      $sender->Title('IpsumInserter');
      $sender->AddSideMenu('plugin/IpsumInserter');
      $sender->Form = new Gdn_Form();
      $this->Dispatch($sender, $sender->RequestArgs);
   }

   public function Controller_Index($sender) {

      $sender->Form = new Gdn_Form();

      // If form is being submitted
      if ($sender->Form->IsPostBack() && $sender->Form->AuthenticatedPostBack() === TRUE) {

         // Form Validation
         $sender->Form->ValidateRule('DiscussionNumber', 'function:ValidateRequired', 'DiscussionNumber is required');
         $sender->Form->ValidateRule('DiscussionNumber', 'function:ValidateInteger', 'DiscussionNumber must be numeric');
         $sender->Form->ValidateRule('CommentNumber', 'function:ValidateRequired', 'CommentNumber is required');
         $sender->Form->ValidateRule('CommentNumber', 'function:ValidateInteger', 'CommentNumber must be numeric');

         // If no errors
         if ($sender->Form->ErrorCount() == 0) {
            $formValues = $sender->Form->FormValues();


            $discussionModel = new DiscussionModel();
            $commentModel = new CommentModel();

            for ($i=0; $i < $formValues['DiscussionNumber']; $i++) {
               $ipsum = $this->GetIpsum(700, $formValues['IpsumType']);
               $fields = [
                  'Name' => substr($ipsum, 0, rand(25, 100)),
                  'Body' => substr($ipsum, 0, rand(100, 700)),
                  'CategoryID' => 1
               ];
               $discussionID = $discussionModel->Save($fields);

               for ($b=0; $b < $formValues['CommentNumber']; $b++) {
                  $ipsum = $this->GetIpsum(500, $formValues['IpsumType']);
                  $fields = [
                     'DiscussionID' => $discussionID,
                     'Body' => substr($ipsum, 0, rand(100, 700))
                  ];
                  $commentModel->Save($fields);
               }
            }
            $sender->SetData('Message', 'Discussions and Comments Created');
            $sender->InformMessage('Discussions and Comments Created.');

         }
      }

      $sender->Render($this->GetView('form.php'));
      return;


   }

   public function GetIpsum($maxLength = 500, $ipsumType = 'lorem') {
      if ($ipsumType == 'gangsta') {
         $ipsum = $this->GetGanstaIpsum();
      } else {
         $ipsum = $this->GetLoremIpsum();
      }

      return substr($ipsum, 0, $maxLength);
   }

   public function GetLoremIpsum() {
      $proxy = new ProxyRequest();

      $options['URL'] = 'http://www.lipsum.com/feed/html';
      $response = $proxy->Request($options);

      $ipsum = stristr($response, "<div id=\"lipsum\">\n<p>\n");
      $ipsum = str_replace("<div id=\"lipsum\">\n<p>\n", '', $ipsum);
      $ipsum = stristr($ipsum, "\n</p></div>\n<div id=\"generated\">", TRUE);
      $ipsum = str_replace("</p>\n", '', $ipsum);
      $ipsum = str_replace("<p>", '', $ipsum);

      return $ipsum;
   }

   public function GetGanstaIpsum() {
      $proxy = new ProxyRequest();

      $options['URL'] = 'http://lorizzle.nl/?feed=1';
      $response = $proxy->Request($options);

      $ipsum = stristr($response, '<div class="lipsum"><p>');
      $ipsum = str_replace('<div class="lipsum"><p>', '' , $ipsum);
      $ipsum = stristr($ipsum, '</p></div>', TRUE);

      return $ipsum;
   }
}

