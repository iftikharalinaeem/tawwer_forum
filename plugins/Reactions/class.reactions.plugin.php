<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Reactions'] = array(
   'Name' => 'Reactions',
   'Description' => "Adds reaction options to discussions & comments.",
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class ReactionsPlugin extends Gdn_Plugin {

   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
   }
   
   public function DiscussionController_Render_Before($Sender) {
      $Sender->ReactionsVersion = 2;
      
      if ($Sender->ReactionsVersion == 1) {
         $Sender->AddCssFile($this->GetResource('design/style-1.css', FALSE, FALSE));
      } else {
         $Sender->AddCssFile($this->GetResource('design/style.css', FALSE, FALSE));
         $this->AddJs($Sender);
      }
   }
   
   public function DiscussionController_AfterCommentBody_Handler($Sender) {
      if ($Sender->ReactionsVersion == 2) {
      // Here's version 2
      ?>
      <div class="Reactions">
         <div class="Flag">
            <div class="Handle">
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Flag</label></a>
            </div>
            <div class="Options">
               <strong>Flag &raquo;</strong>
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Abuse</label></a>
               <a href="#"><span class="ReactSprite ReactSpam HasCount"></span> <label>Spam</label> <span class="Count">1</span></a>
               <a href="#"><span class="ReactSprite ReactTroll HasCount"></span> <label>Troll</label> <span class="Count">2</span></a>
            </div>
         </div>
         <div class="React">
            <div class="Handle">
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>React</label></a>
            </div>
            <div class="Options">
               <a href="#"><span class="ReactSprite ReactOffTopic"></span> <label>Off Topic</label></a>
               <a href="#"><span class="ReactSprite ReactDisagree"></span> <label>Disagree</label></a>
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>Agree</label></a>
               <a href="#"><span class="ReactSprite ReactAwesome HasCount"></span> <label>Awesome</label> <span class="Count">6</span></a>
               <strong>&laquo; React</strong>
            </div>
         </div>
      </div>
      <?php
      } else {
      // Here's version 1
      ?>
      <div class="Reactions">
         <div class="Flag">
            <div class="Closed">
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Flag</label></a>
            </div>
            <div class="Open">
               <a href="#"><span class="ReactSprite ReactFlag"></span> <label>Abuse</label></a>
               <a href="#"><span class="ReactSprite ReactSpam HasCount"></span> <label>Spam</label> <span class="Count">1</span></a>
               <a href="#"><span class="ReactSprite ReactTroll HasCount"></span> <label>Troll</label> <span class="Count">2</span></a>
            </div>
         </div>
         <div class="React">
            <div class="Closed">
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>React</label></a>
            </div>
            <div class="Open">
               <a href="#"><span class="ReactSprite ReactOffTopic"></span> <label>Off Topic</label></a>
               <a href="#"><span class="ReactSprite ReactDisagree"></span> <label>Disagree</label></a>
               <a href="#"><span class="ReactSprite ReactAgree"></span> <label>Agree</label></a>
               <a href="#"><span class="ReactSprite ReactAwesome HasCount"></span> <label>Awesome</label> <span class="Count">6</span></a>
            </div>
         </div>
      </div>
      <?php
      }
   }
   
   private function AddJs($Sender) {
      $String = <<<'EOD'
      <script type="text/javascript">
      jQuery(document).ready(function($) {
         $('.Reactions .Handle').live('click', function() {
            var flagContainer = $(this).parents('.Reactions').find('.Flag');
            var reactContainer = $(this).parents('.Reactions').find('.React');
            
            if ($(this).parents('.React').length > 0) {
               reactContainer.find('.Handle').hide(); // Hide react handle
               flagContainer.find('.Options').hide(); // Hide flag options
               flagContainer.find('.Handle').show(); // Show flag handle
               reactContainer.find('.Options').show('slide', {direction: 'right'}, 200); // Show react options
            } else {
               flagContainer.find('.Handle').hide(); // Hide flag handle
               reactContainer.find('.Options').hide(); // Hide react options
               reactContainer.find('.Handle').show(); // Show react handle
               flagContainer.find('.Options').show('slide', {direction: 'left'}, 200); // Show flag options
            }
            return false;
         });
         $('.Flag .Options strong').live('click', function() {
            var flagContainer = $(this).parents('.Flag');
            var reactContainer = $(this).parents('.Reactions').find('.React');

            reactContainer.find('.Handle').hide(); // Hide react handle
            flagContainer.find('.Options').hide(); // Hide flag options
            flagContainer.find('.Handle').show(); // Show flag handle
            reactContainer.find('.Options').show('slide', {direction: 'right'}, 200); // Show react options

            return false;
         });
      });
      </script>
EOD;
      $Sender->Head->AddString($String);
   }
}