<?php if (!defined('APPLICATION')) exit();

/**
 * ThreadCycle Plugin
 * 
 * This plugin uses Minion to automatically close threads after N pages.
 * 
 * Changes: 
 *  1.0     Release
 *  1.1     Improve new thread creator choices
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['ThreadCycle'] = array(
   'Name' => 'Minion: ThreadCycle',
   'Description' => "Provide command to automatically cycle a thread after N pages.",
   'Version' => '1.1',
   'RequiredApplications' => array(
      'Vanilla' => '2.1a'
    ),
   'RequiredPlugins' => array(
      'Minion' => '1.4.2',
      'Online' => '1.6.3'
   ),
   'MobileFriendly' => TRUE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class ThreadCyclePlugin extends Gdn_Plugin {
   
   /**
    * Cycle this thread
    * 
    * @param array $Discussion
    */
   public function CycleThread($Discussion) {
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      
      // Close the thread
      $DiscussionModel = new DiscussionModel();
      $DiscussionModel->SetField($DiscussionID, 'Closed', TRUE);
      
      // Find the last page of commenters.
      $CommentsPerPage = C('Vanilla.Comments.PerPage', 40);
      $Commenters = Gdn::SQL()->Select('InsertUserID', 'DISTINCT', 'UserID')
         ->From('Comment')
         ->Where('DiscussionID', $DiscussionID)
         ->OrderBy('DateInserted', 'desc')
         ->Limit($CommentsPerPage)
         ->Get()->ResultArray();
      
      Gdn::UserModel()->JoinUsers($Commenters, array('UserID'), array(
         'Join'   => array('Name', 'Jailed', 'Points')
      ));
      
      // Weed out jailed and offline people
      $Eligible = array();
      foreach ($Commenters as $Commenter) {
         if ($Commenter['Jailed'])
            continue;
         
         $UserOnline = OnlinePlugin::Instance()->GetUser($Commenter['UserID']);
         if (!$UserOnline) 
            continue;
         
         $Commenter['LastOnline'] = time() - strtotime($UserOnline['Timestamp']);
         $Eligible[] = $Commenter;
      }
      unset($Commenters);
      
      // Sort by points, descending
      usort($Eligible, array('ThreadCyclePlugin', 'CompareUsersByPoints'));
      
      // Get the top 10 by points, and choose the 2 most recently online
      $Eligible = array_slice($Eligible, 0, 10);
      usort($Eligible, array('ThreadCyclePlugin', 'CompareUsersByLastOnline'));
      
      // Alert everyone
      $Message = T("This thread is no longer active, and will be recycled.\n{PrimaryMessage}{SecondaryMessage}");
      $Acknowledge = T("Thread has been recycled.\n");
      
      $Options = array();
      if (sizeof($Eligible) >= 1) {
         $PrimaryMessage = T("{Primary.Mention} will create the new thread\n");
         $Acknowledge .= $PrimaryMessage;
         $Primary = $Eligible[0];
         $Primary['Mention'] = "@{$Primary['Name']}";
         $Options['Primary'] = $Primary;
         $Options['PrimaryMessage'] = FormatString($PrimaryMessage, array(
            'Primary'   => $Primary
         ));
      }
      
      if (sizeof($Eligible) >= 2) {
         $SecondaryMessage .= T("{Secondary.Mention} is backup\n");
         $Acknowledge .= $SecondaryMessage;
         $Secondary = $Eligible[1];
         $Secondary['Mention'] = "@{$Secondary['Name']}";
         $Options['Secondary'] = $Secondary;
         $Options['SecondaryMessage'] = FormatString($SecondaryMessage, array(
            'Secondary' => $Secondary
         ));
      }
      
      $Message = FormatString($Message, $Options);
      MinionPlugin::Instance()->Message($Primary, $Discussion, $Message);
      
      $Acknowledged = FormatString($Acknowledge, $Options);
      MinionPlugin::Instance()->Log($Acknowledged, $Discussion);
      
      MinionPlugin::Instance()->Monitor($Discussion, array(
         'ThreadCycle' => NULL
      ));
   }
   
   public static function CompareUsersByPoints($a, $b) {
      return $b['Points'] - $a['Points'];
   }
   
   public static function CompareUsersByLastOnline($a, $b) {
      return $a['LastOnline'] - $a['LastOnline'];
   }
   
   /*
    * MINION INTERFACE
    */
   
   /**
    * Parse a token from the current state
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Token_Handler($Sender) {
      $State = &$Sender->EventArguments['State'];

      if (!$State['Method'] && in_array($State['CompareToken'], array('recycle')))
         $Sender->Consume($State, 'Method', 'threadcycle');
      
      // Gather 
      if (GetValue('Method', $State) == 'threadcycle' && in_array($State['CompareToken'], array('pages', 'page'))) {
         $Sender->Consume($State, 'Gather', array(
            'Node'   => 'Page',
            'Delta'  => ''
         ));
      }
      
   }
   
   /**
    * Parse gathering tokens from the current state
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_TokenGather_Handler($Sender) {
      $State = &$Sender->EventArguments['State'];
      
      switch (GetValueR('Gather.Node', $State)) {
         case 'Page':
            
            // Add token
            $State['Gather']['Delta'] .= " {$State['Token']}";
            $Sender->Consume($State);

            // If we're closed, close up
            $CurrentDelta = trim($State['Gather']['Delta']);
            if (strlen($State['Gather']['Delta']) && is_numeric($CurrentDelta)) {
               $State['Targets']['Page'] = $CurrentDelta;
               $State['Gather'] = FALSE;
               break;
            }

            if (!strlen($State['Token'])) {
               $State['Gather'] = FALSE;
               continue;
            }

            break;
      }
   }
   
   /**
    * Parse custom minion commands
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Command_Handler($Sender) {
      $Actions = &$Sender->EventArguments['Actions'];
      $State = &$Sender->EventArguments['State'];
      
      switch ($State['Method']) {
         case 'threadcycle':
            
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('threadcycle', 'Garden.Moderation.Manage', $State);
            break;
      }
      
   }
   
   /**
    * Perform custom minion actions
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Action_Handler($Sender) {
      $Action = $Sender->EventArguments['Action'];
      $State = $Sender->EventArguments['State'];
      
      switch ($Action) {
         
         case 'threadcycle':
            
            if (!array_key_exists('Discussion', $State['Targets']))
               return;
            
            $Discussion = $State['Targets']['Discussion'];
            $ThreadCycle = $Sender->Monitoring($Discussion, 'ThreadCycle', FALSE);
            
            // Trying to call off a threadcycle
            if ($State['Toggle'] == 'off') {
               if (!$ThreadCycle) return;
               
               // Call off the hunt
               $Sender->Monitor($Discussion, array(
                  'ThreadCycle'  => NULL
               ));
               
               $Sender->Acknowledge($State['Sources']['Discussion'], FormatString(T("This thread will not be automatically recycled."), array(
                  'Discussion'   => $Discussion
               )));
               
            // Trying start a threadcycle
            } else {
               
               $CyclePage = GetValue('Page', $State['Targets'], FALSE);
               if ($CyclePage) {
                  
                  // Pick somewhere to end the discussion
                  $CommentsPerPage = C('Vanilla.Comments.PerPage', 40);
                  $MinComments = ($CyclePage - 1) * $CommentsPerPage;
                  $CommentNumber = $MinComments + mt_rand(1,$CommentsPerPage-1);
                  
                  // Monitor the thread
                  $Sender->Monitor($Discussion, array(
                     'ThreadCycle'    => array(
                        'Started'   => time(),
                        'Page'      => $CyclePage,
                        'Comment'   => $CommentNumber
                     )
                  ));
                  
                  $Acknowledge = T("Thread will be recycled after {Page}.");
                  $Acknowledged = FormatString($Acknowledge, array(
                     'Page'         => sprintf(Plural($CyclePage, '%d page', '%d pages'), $CyclePage),
                     'Discussion'   => $State['Targets']['Discussion']
                  ));

                  $Sender->Acknowledge($State['Sources']['Discussion'], $Acknowledged);
                  $Sender->Log($Acknowledged, $State['Targets']['Discussion'], $State['Sources']['User']);
                  
               } else {
                  // Cycle immediately
                  $this->CycleThread($Discussion);
               }

            }
            
            break;
      }
   }
   
   /**
    * Determine if we're at the comment that should trigger recycling
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Monitor_Handler($Sender) {
      $Discussion = $Sender->EventArguments['Discussion'];
      $ThreadCycle = $Sender->Monitoring($Discussion, 'ThreadCycle', FALSE);
      if (!$ThreadCycle) return;
      
      $CycleCommentNumber = GetValue('Comment', $ThreadCycle);
      $Comments = GetValue('CountComments', $Discussion);
      if ($Comments == $CycleCommentNumber) {
         $this->CycleThread($Discussion);
      }
   }
   
}