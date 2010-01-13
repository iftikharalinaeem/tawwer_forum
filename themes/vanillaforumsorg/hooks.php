<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ThemeHooks implements Gdn_IPlugin {
   public function Setup() {
      return TRUE;
   }
   
   public function DiscussionsController_AfterDiscussionTitle_Handler(&$Sender) {
      $Discussion = ArrayValue('Discussion', $Sender->EventArguments);
      if ($Discussion) {
         echo '<div id="FirstComment" style="display: none;">'.Format::To($Discussion->FirstComment, $Discussion->FirstCommentFormat).'</div>';
      }
   }
   
   public function PluginController_VFOrgUserInfo_Create(&$Sender) {
      ?>
      <div class="UserOptions">
         <div>
            <?php
               $Session = Gdn::Session();
               $Authenticator = Gdn::Authenticator();
               if ($Session->IsValid()) {
                  $Name = '<em>'.$Session->User->Name.'</em>';
                  $CountNotifications = $Session->User->CountNotifications;
                  if (is_numeric($CountNotifications) && $CountNotifications > 0)
                     $Name .= '<span>'.$CountNotifications.'</span>';
                     
                  echo Anchor($Name, '/profile/'.$Session->UserID.'/'.$Session->User->Name, 'Username');

                  $Inbox = '<em>Inbox</em>';
                  $CountUnreadConversations = $Session->User->CountUnreadConversations;
                  if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
                     $Inbox .= '<span>'.$CountUnreadConversations.'</span>';
            
                  echo Anchor($Inbox, '/messages/all', 'Inbox');

                  if ($Session->CheckPermission('Garden.Settings.Manage'))
                     echo Anchor('Dashboard', '/garden/settings', 'Dashboard');
                  
                  echo Anchor('Sign Out', str_replace('{Session_TransientKey}', $Session->TransientKey(), $Authenticator->SignOutUrl()), 'Leave');
               } else {
                  echo Anchor('Sign In', $Authenticator->SignInUrl($this->SelfUrl), 'SignInPopup');
                  echo Anchor('Apply for Membership', $Authenticator->RegisterUrl($this->SelfUrl), 'Register');
               }
            ?>
         </div>
      </div>
      <?php   
   }
}