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
$PluginInfo['Quotes'] = array(
   'Name' => 'Quotes',
   'Description' => "This plugin allows users to quote each other's posts easily.",
   'Version' => '0.1',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class QuotesPlugin extends Gdn_Plugin {

   public function PluginController_Quotes_Create(&$Sender) {
      $Sender->Permission('Garden.AdminUser.Only');
		$this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Getquote(&$Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      
      $QuoteData = array(
         'status' => 'failed'
      );
      array_shift($Sender->RequestArgs);
      if (sizeof($Sender->RequestArgs)) {
         $QuoteData['selector'] = $Sender->RequestArgs[0];
         list($Type, $ID) = explode('_',$Sender->RequestArgs[0]);
         $Model = FALSE;
         switch (strtolower($Type)) {
            case 'comment':
               $Model = new CommentModel();
               break;
            
            case 'discussion':
               $Model = new DiscussionModel();
               $Data = $Model->GetID($ID);
               break;
               
            default:
               break;
         }
         if ($Model !== FALSE) {
            $Data = $Model->GetID($ID);
            $QuoteData = array_merge($QuoteData, array(
               'status'       => 'success',
               'body'         => $Data->Body,
               'format'       => $Data->Format,
               'authorid'     => $Data->InsertUserID,
               'authorname'   => $Data->InsertName
            ));
         }
      }
      $Sender->SetJson('Quote', $QuoteData);
      $Sender->Render($this->GetView('getquote.php'));
   }

   public function DiscussionController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   public function PostController_Render_Before(&$Sender) {
      $this->PrepareController($Sender);
   }
   
   protected function PrepareController(&$Sender) {
      $Sender->AddJsFile($this->GetResource('js/quotes.js', FALSE, FALSE));
      $Sender->AddCssFile($this->GetResource('css/quotes.css', FALSE, FALSE));
   }
   
   public function DiscussionController_CommentOptions_Handler(&$Sender) {
      $this->AddQuoteButton($Sender);
   }
   
   public function PostController_CommentOptions_Handler(&$Sender) {
      $this->AddQuoteButton($Sender);
   }
   
   protected function AddQuoteButton(&$Sender) {
      $ObjectID = !isset($Sender->EventArguments['Comment']) ? 'Discussion_'.$Sender->EventArguments['Discussion']->DiscussionID : 'Comment_'.$Sender->EventArguments['Comment']->CommentID;
      echo <<<QUOTE
      <span class="CommentQuote"><a href="javascript:QuotesPlugin.Quote('{$ObjectID}');">Quote</a></span>
QUOTE;
   }
   
   public function DiscussionController_BeforeCommentDisplay_Handler(&$Sender) {
      $this->RenderQuotes($Sender);
   }
   
   public function PostController_BeforeCommentDisplay_Handler(&$Sender) {
      $this->RenderQuotes($Sender);
   }
   
   protected function RenderQuotes(&$Sender) {
      if (isset($Sender->EventArguments['Discussion'])) 
         $Data = $Sender->EventArguments['Discussion'];
         
      if (isset($Sender->EventArguments['Comment'])) 
         $Data = $Sender->EventArguments['Comment'];
      
      switch ($Data->Format) {
         case 'Html':
            $Data->Body = preg_replace_callback('/(<blockquote rel="([\d\w_]{3,20})">)/', array($this, 'QuoteAuthorCallback'), $Data->Body);
            $Data->Body = str_replace('</blockquote>','</p></div></blockquote>',$Data->Body);
            break;
            
         case 'BBCode':
            $Data->Body = preg_replace_callback('/([quote="([\d\w_]{3,20})"])/', array($this, 'QuoteAuthorCallback'), $Data->Body);
            $Data->Body = str_replace('[/quote]','</p></div></blockquote>',$Data->Body);
            break;
            
         case 'Display':
         case 'Text':
         default:
            break;
      
      }
      
/*
      $SourceUserID = $Data->InsertUserID;
      $UserSignatures =& $Sender->Data('Plugin-Signatures-UserSignatures');
      
      if (isset($UserSignatures[$SourceUserID])) {
         $HideImages = ArrayValue('Plugin.Signature.HideImages', $Sender->Data('Plugin-Signatures-ViewingUserData'), FALSE);
         
         $UserSig = $UserSignatures[$SourceUserID];
         
         if ($HideImages) {
            // Strip img tags
            $UserSig = $this->_StripOnly($UserSig, array('img'));
         
            // Remove blank lines and spare whitespace
            
            $UserSig = trim($UserSig);
         }
         
         // Don't show empty sigs, brah
         if ($UserSig == '') return;
         
         $Sender->UserSignature = Gdn_Format::Html($UserSig);
         $Display = $Sender->FetchView($this->GetView('usersig.php'));
         unset($Sender->UserSignature);
         $Data->Body .= $Display;
      }
*/
   }
   
   /*
   <blockquote class="UserQuote"><div class="QuoteAuthor"><a href="/profile/3/McNugget" rel="nofollow">McNugget</a> said:</div><div class="QuoteText"><p>Combo</p></div></blockquote>
   */
   protected function QuoteAuthorCallback($Matches) {
      return <<<BLOCKQUOTE
      <blockquote class="UserQuote"><div class="QuoteAuthor"><a href="/profile/{$Matches[2]}" rel="nofollow">{$Matches[2]}</a> said:</div><div class="QuoteText"><p>
BLOCKQUOTE;
   }
   
   public function Setup() {
      // Nothing to do here!
   }
   
   public function Structure() {
      // Nothing to do here!
   }
         
}