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
   public function pluginController_ipsumInserter_create($sender) {

      $sender->permission('Garden.Settings.Manage');
      $sender->title('IpsumInserter');
      $sender->addSideMenu('plugin/IpsumInserter');
      $sender->Form = new Gdn_Form();
      $this->dispatch($sender, $sender->RequestArgs);
   }

   public function controller_Index($sender) {

      $sender->Form = new Gdn_Form();

      // If form is being submitted
      if ($sender->Form->isPostBack() && $sender->Form->authenticatedPostBack() === TRUE) {

         // Form Validation
         $sender->Form->validateRule('DiscussionNumber', 'function:ValidateRequired', 'DiscussionNumber is required');
         $sender->Form->validateRule('DiscussionNumber', 'function:ValidateInteger', 'DiscussionNumber must be numeric');
         $sender->Form->validateRule('CommentNumber', 'function:ValidateRequired', 'CommentNumber is required');
         $sender->Form->validateRule('CommentNumber', 'function:ValidateInteger', 'CommentNumber must be numeric');

         // If no errors
         if ($sender->Form->errorCount() == 0) {
            $formValues = $sender->Form->formValues();


            $discussionModel = new DiscussionModel();
            $commentModel = new CommentModel();

            for ($i=0; $i < $formValues['DiscussionNumber']; $i++) {
               $ipsum = $this->getIpsum(700, $formValues['IpsumType']);
               $fields = [
                  'Name' => substr($ipsum, 0, rand(25, 100)),
                  'Body' => substr($ipsum, 0, rand(100, 700)),
                  'CategoryID' => 1
               ];
               $discussionID = $discussionModel->save($fields);

               for ($b=0; $b < $formValues['CommentNumber']; $b++) {
                  $ipsum = $this->getIpsum(500, $formValues['IpsumType']);
                  $fields = [
                     'DiscussionID' => $discussionID,
                     'Body' => substr($ipsum, 0, rand(100, 700))
                  ];
                  $commentModel->save($fields);
               }
            }
            $sender->setData('Message', 'Discussions and Comments Created');
            $sender->informMessage('Discussions and Comments Created.');

         }
      }

      $sender->render($this->getView('form.php'));
      return;


   }

   public function getIpsum($maxLength = 500, $ipsumType = 'lorem') {
      if ($ipsumType == 'gangsta') {
         $ipsum = $this->getGanstaIpsum();
      } else {
         $ipsum = $this->getLoremIpsum();
      }

      return substr($ipsum, 0, $maxLength);
   }

   public function getLoremIpsum() {
      $proxy = new ProxyRequest();

      $options['URL'] = 'http://www.lipsum.com/feed/html';
      $response = $proxy->request($options);

      $ipsum = stristr($response, "<div id=\"lipsum\">\n<p>\n");
      $ipsum = str_replace("<div id=\"lipsum\">\n<p>\n", '', $ipsum);
      $ipsum = stristr($ipsum, "\n</p></div>\n<div id=\"generated\">", TRUE);
      $ipsum = str_replace("</p>\n", '', $ipsum);
      $ipsum = str_replace("<p>", '', $ipsum);

      return $ipsum;
   }

   public function getGanstaIpsum() {
      $proxy = new ProxyRequest();

      $options['URL'] = 'http://lorizzle.nl/?feed=1';
      $response = $proxy->request($options);

      $ipsum = stristr($response, '<div class="lipsum"><p>');
      $ipsum = str_replace('<div class="lipsum"><p>', '' , $ipsum);
      $ipsum = stristr($ipsum, '</p></div>', TRUE);

      return $ipsum;
   }
}

