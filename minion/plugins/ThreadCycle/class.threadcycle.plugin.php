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
      $CommentsPerPage = C('Vanilla.Comments.PerPage', 40);
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      
      // Close the thread
      $DiscussionModel = new DiscussionModel();
      $DiscussionModel->SetField($DiscussionID, 'Closed', TRUE);
      
      // Determine speed
      $StartTime = strtotime(GetValue('DateInserted', $Discussion));
      $EndTime = time();
      $Elapsed = $EndTime - $StartTime;
      $CPM = (GetValue('CountComments', $Discussion) / $Elapsed) * 60;
      
      $MinWarp = 1;
      $MaxWarp = 11;
      
      // Rate determination
//      $TargetWarp = 6.5;
//      $TargetComments = ;
//      $CPMPerWarp = ;
//      $MinWarpCPM = 0.4;
//      $MaxWarpCPM = ;
      
      $Scales = array(
          array(
             'min'         => 0,
             'max'         => 0.1,
             'name'        => 'thrusters'
          ),
          array(
             'min'         => 0.1,
             'max'         => $MinWarpCPM,
             'name'        => 'impulse',
             'format'      => '{speed} {scale}',
             'divisions'   => 4,
             'divtype'     => 'fractions'
          ),
          array(
             'min'         => 0.4,
             'max'         => $MaxWarpCPM,
             'name'        => 'warp',
             'format'      => '{scale} {speed}',
             'divisions'   => 10
          ),
          array(
             'min'         => $MaxWarpCPM,
             'max'         => null,
             'name'        => 'transwarp'
          )
      );
      
      $Scale = null;
      foreach ($Scales as $ScaleInfo) {
         $Max = $ScaleInfo['max'];
         $Min = $ScaleInfo['min'];
         if ($CPM >= $Min && $CPM < $Max) {
            $Scale = $ScaleInfo['name'];
            $Format = GetValue('format', $ScaleInfo, '{scale}');
            $Speed = 1;
            
            $Divisions = GetValue('divisions', $ScaleInfo, null);
            if ($Divisions && $Max) {
               $DivType = GetValue('divtype', $ScaleInfo, 'whole');
               switch ($DivType) {
                  case 'fractions':
                     //$Range = 
                     break;
                  
                  case 'whole':
                  default:
                     break;
               }
            }
            break;
         }
      }
      
      // Find the last page of commenters.
      $Commenters = Gdn::SQL()->Select('InsertUserID', 'DISTINCT', 'UserID')
         ->From('Comment')
         ->Where('DiscussionID', $DiscussionID)
         ->OrderBy('DateInserted', 'desc')
         ->Limit($CommentsPerPage)
         ->Get()->ResultArray();
      
      Gdn::UserModel()->JoinUsers($Commenters, array('UserID'), array(
         'Join'   => array('UserID', 'Name', 'Email', 'Photo', 'Jailed', 'Points')
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
      
      // Sort by online, descending
      usort($Eligible, array('ThreadCyclePlugin', 'CompareUsersByLastOnline'));
      
      // Get the top 10 by online, and choose the 2 by most points
      $Eligible = array_slice($Eligible, 0, 10);
      usort($Eligible, array('ThreadCyclePlugin', 'CompareUsersByPoints'));
      $Primary = GetValue(0, $Eligible, array());
      $Secondary = Getvalue(1, $Eligible, array());
      
      // Alert everyone
      $Message = T("This thread is no longer active, and will be recycled.\n");
      $Acknowledge = T("Thread has been recycled.\n");
      
      $Options = array(
         'Primary'   => &$Primary,
         'Secondary' => &$Secondary
      );
      
      if (sizeof($Primary)) {
         $Message .= $PrimaryMessage = T(" {Primary.Mention} will create the new thread\n");
         $Acknowledge .= str_replace('.Mention', '.Anchor', $PrimaryMessage);
         
         $Primary['Mention'] = "@\"{$Primary['Name']}\"";
         $Primary['Anchor'] = UserAnchor($Primary);
      }
      
      if (sizeof($Secondary)) {
         $Message .= $SecondaryMessage = T(" {Secondary.Mention} is backup\n");
         $Acknowledge .= str_replace('.Mention', '.Anchor', $SecondaryMessage);
         
         $Secondary['Mention'] = "@\"{$Secondary['Name']}\"";
         $Secondary['Anchor'] = UserAnchor($Secondary);
      }
      
      $Message = FormatString($Message, $Options);
      MinionPlugin::Instance()->Message($Primary, $Discussion, $Message, FALSE);
      
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
   
   /**
    * Add to rules
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Sanctions_Handler($Sender) {
      
      // Don't care about the rule bar
      
      $Type = GetValue('Type', $Sender->EventArguments, 'rules');
      if ($Type == 'bar') return;
      
      // Show a warning if there are rules in effect
      
      $ThreadCycle = $Sender->Monitoring($Sender->EventArguments['Discussion'], 'ThreadCycle', NULL);
      
      // Nothing happening?
      if (!$ThreadCycle)
         return;

      $Rules = &$Sender->EventArguments['Rules'];
      
      // Thread is queued for recycled
      $Page = GetValue('Page', $ThreadCycle);
      $Rules[] = Wrap("<b>Thread Recycle</b>: page {$Page}", 'span', array('class' => 'MinionRule'));
      
   }
   
}