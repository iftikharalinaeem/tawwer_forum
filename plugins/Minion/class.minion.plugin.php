<?php if (!defined('APPLICATION')) exit();

/**
 * Minion Plugin
 * 
 * This plugin creates a 'minion' that performs certain administrative tasks
 * automatically.
 * 
 * Changes: 
 *  1.0     Release
 *  1.0.1   Fix data tracking issues
 *  1.0.2   Fix typo bug
 *  1.0.4   Only flag people when fingerprint checking is on
 * 
 *  1.1     Only autoban newer accounts than existing banned ones
 *  1.2     Prevent people from posting autoplay embeds
 *  1.3     New inline command structure
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['Minion'] = array(
   'Name' => 'Minion',
   'Description' => "Creates a 'minion' that performs adminstrative tasks automatically.",
   'Version' => '1.3',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'MobileFriendly' => TRUE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class MinionPlugin extends Gdn_Plugin {
   
   protected $MinionUserID = NULL;
   protected $Minion = NULL;
   
   /**
    * Retrieves a "system user" id that can be used to perform non-real-person tasks.
    */
   public function GetMinionUserID() {
      $MinionUserID = C('Plugins.Minion.UserID');
      if ($MinionUserID)
         return $MinionUserID;
      
      $MinionUser = array(
         'Name' => C('Plugins.Minion.Name', 'Minion'),
         'Photo' => Asset('/applications/dashboard/design/images/usericon.png', TRUE),
         'Password' => RandomString('20'),
         'HashMethod' => 'Random',
         'Email' => 'minion@'.Gdn::Request()->Domain(),
         'DateInserted' => Gdn_Format::ToDateTime(),
         'Admin' => '2'
      );
      
      $this->EventArguments['MinionUser'] = &$MinionUser;
      $this->FireAs('UserModel')->FireEvent('BeforeMinionUser');
      
      $MinionUserID = Gdn::UserModel()->SQL->Insert('User', $MinionUser);
      
      SaveToConfig('Plugins.Minion.UserID', $MinionUserID);
      return $MinionUserID;
   }
   
   public function MinionName() {
      $this->StartMinion();
      return $MinionName = GetValue('Name', $this->Minion);
   }
   
   /**
    * 
    * @param PostController $Sender 
    */
   public function PostController_AfterCommentSave_Handler($Sender) {
      $this->StartMinion();
      
      $this->CheckFingerprintBan($Sender);
      $this->CheckAutoplay($Sender);
      $this->CheckCommands($Sender);
   }
   
   /**
    * 
    * @param PostController $Sender 
    */
   public function PostController_AfterDiscussionSave_Handler($Sender) {
      $this->StartMinion();
      
      $this->CheckFingerprintBan($Sender);
      $this->CheckAutoplay($Sender);
      $this->CheckCommands($Sender);
      $this->CheckMonitor($Sender);
   }
   
   protected function StartMinion() {
      if (is_null($this->Minion)) {
         // Currently operating as Minion
         $this->MinionUserID = $this->GetMinionUserID();
         $this->Minion = Gdn::UserModel()->GetID($this->MinionUserID);
      }
   }
   
   /**
    * 
    * @param PostController $Sender 
    */
   protected function CheckFingerprintBan($Sender) {
      if (!C('Plugins.Minion.Features.Fingerprint', TRUE)) return;
      
      if (!Gdn::Session()->IsValid()) return;
      $FlagMeta = $this->GetUserMeta(Gdn::Session()->UserID, "FingerprintCheck", FALSE);
      
      // User already flagged
      if (!$FlagMeta) return;
      
      // Flag em'
      $this->SetUserMeta(Gdn::Session()->UserID, "FingerprintCheck", 1);
   }
   
   /**
    * 
    * @param PostController $Sender 
    */
   protected function CheckAutoplay($Sender) {
      if (!C('Plugins.Minion.Features.Autoplay', TRUE)) return;
      
      // Admins can do whatever they want
      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) return;
      
      $Object = $Sender->EventArguments['Discussion'];
      $Type = 'Discussion';
      if (array_key_exists('Comment', $Sender->EventArguments)) {
         $Object = $Sender->EventArguments['Comment'];
         $Type = 'Comment';
      }
      
      $ObjectID = GetValue("{$Type}ID", $Object);
      $ObjectBody = GetValue('Body', $Object);
      if (preg_match_all('`(?:https?|ftp)://(www\.)?youtube\.com\/watch\?v=([^&#]+)(#t=([0-9]+))?`', $ObjectBody, $Matches) 
         || preg_match_all('`(?:https?)://(www\.)?youtu\.be\/([^&#]+)(#t=([0-9]+))?`', $ObjectBody, $Matches)) {
         
         // Youtube was found. Got autoplay?
         
         $MatchURLs = $Matches[0]; $AutoPlay = FALSE;
         foreach ($MatchURLs as $MatchURL) {
            if (stristr($MatchURL, 'autoplay=1'))
               $AutoPlay = TRUE;
         }
         
         if (!$AutoPlay) return;
         
         $ObjectModelName = "{$Type}Model";
         $ObjectModel = new $ObjectModelName();
         
         $ObjectModel->Delete($ObjectID);
         
         if ($Type == 'Comment') {
            $DiscussionID = GetValue('DiscussionID',$Object);
            $MinionReportText = T("{Minion Name} DETECTED AUTOPLAY ATTEMPT
{User Target}");

            $MinionReportText = FormatString($MinionReportText, array(
               'Minion Name'     => $this->Minion->Name,
               'User Target'     => UserAnchor(Gdn::Session()->User)
            ));

            $MinionCommentID = $ObjectModel->Save(array(
               'DiscussionID' => $DiscussionID,
               'Body'         => $MinionReportText,
               'Format'       => 'Html',
               'InsertUserID' => $this->MinionUserID
            ));

            $ObjectModel->Save2($MinionCommentID, TRUE);
         }
         
         $Sender->InformMessage("POST REMOVED DUE TO AUTOPLAY VIOLATION");
      }
   }
   
   /**
    * Check for minion commands in comments
    * 
    * @param type $Sender
    */
   public function CheckCommands($Sender) {
      $MinionName = GetValue('Name', $this->Minion);
      
      // Get the discussion and comment from args
      $Discussion = (array)$Sender->EventArguments['Discussion'];
      $Type = 'Discussion';
      if (!is_array($Discussion['Attributes'])) {
         $Discussion['Attributes'] = @unserialize($Discussion['Attributes']);
         if (!is_array($Discussion['Attributes']))
            $Discussion['Attributes'] = array();
      }
      
      $Comment = NULL;
      if (array_key_exists('Comment', $Sender->EventArguments)) {
         $Comment = (array)$Sender->EventArguments['Comment'];
         $Type = 'Comment';
      }
      $Object = $$Type;
      
      $Actions = array();
      $this->EventArguments['Actions'] = &$Actions;
      
      $ObjectBody = GetValue('Body', $Object);
      $ObjectBody = trim(strip_tags($ObjectBody));
      
      // Check every line of the body to see if its a minion command
      $ObjectLines = explode("\n", $ObjectBody);
      foreach ($ObjectLines as $ObjectLine) {
         
         $Objects = explode(' ', $ObjectLine);
         $CallName = array_shift($Objects);
         $CallName = trim($CallName,' ,.');

         if (strtolower($CallName) != strtolower($MinionName))
            continue;
         
         $Command = trim(implode(' ', $Objects));
         
         /*
          * Tokenized floating detection
          */

         // Define starting state
         $State = array(
            'Body'      => $ObjectBody,
            'Sources'   => array(),
            'Targets'   => array(),
            'Method'    => NULL,
            'Toggle'    => NULL,
            'Gather'    => FALSE,
            'Command'   => $Command,
            'Tokens'    => 0,
            'Parsed'    => 0
         );
         
         // Define sources
         $State['Sources']['Discussion'] = $Discussion;
         if ($Comment)
            $State['Sources']['Comment'] = $Comment;

         $this->EventArguments['State'] = &$State;
         $State['Token'] = strtok($Command, ' ');
         $State['Parsed']++;
         
         while ($State['Token'] !== FALSE) {
            if ($State['Gather']) {

               switch (GetValueR('Gather.Node', $State)) {
                  case 'User':

                     // If we need to wait for a closing quote
                     if (!sizeof($State['Gather']['Delta']) && substr($State['Token'], 0, 1) == '"') {
                        $State['Token'] = substr($State['Token'], 1);
                        $State['Gather']['ExplicitClose'] = '"';
                     }

                     // If we've found our closing quote
                     if (GetValue('ExplicitClose', $State['Gather'])) {
                        if ($FoundPosition = stristr($State['Token'], $State['Gather']['ExplicitClose'])) {
                           $State['Token'] = substr($State['Token'], 0, $FoundPosition);
                           unset($State['Gather']['ExplicitClose']);
                        }
                     }

                     // Add token
                     $State['Gather']['Delta'] .= " {$State['Token']}";
                     $this->Consume($State);

                     // Check if this is a real user already
                     if (sizeof($State['Gather']['Delta'])) {
                        $CheckUser = trim($State['Gather']['Delta']);
                        if ($GatherUser = Gdn::UserModel()->GetByUsername($CheckUser)) {
                           $State['Gather'] = FALSE;
                           $State['Targets']['User'] = (array)$GatherUser;
                           break;
                        }
                     }

                     if (!sizeof($State['Token'])) {
                        $State['Gather'] = FALSE;
                        continue;
                     }

                  break;
               }

            } else {

               /*
                * TOGGLERS
                */

               if (empty($State['Toggle']) && in_array($State['Token'], array('open', 'enable', 'unlock', 'allow')))
                  $this->Consume($State, 'Toggle', 'on');

               if (empty($State['Toggle']) && in_array($State['Token'], array('no', 'close', 'disable', 'lock', 'disallow', 'forbid', 'down')))
                  $this->Consume($State, 'Toggle', 'off');

               /*
                * FORCE
                */

               if (empty($State['Force']) && in_array($State['Token'], array('stun', 'blanks')))
                  $this->Consume($State, 'Force', 'low');

               if (empty($State['Force']) && in_array($State['Token'], array('weapon', 'weapons', 'power')))
                  $this->Consume($State, 'Force', 'medium');

               if (empty($State['Force']) && in_array($State['Token'], array('kill', 'lethal', 'nuke', 'nuclear', 'destroy')))
                  $this->Consume($State, 'Force', 'high');
               
               // Conditional forces
               if (!empty($State['Method']) && empty($State['Force']) && in_array($State['Token'], array('warning', 'warn')))
                  $this->Consume($State, 'Force', 'warn');

               /*
                * TARGETS
                */

               if (in_array($State['Token'], array('user'))) {
                  $this->Consume($State, 'Gather', array(
                     'Node'   => 'User',
                     'Delta'  => ''
                  ));
               }

               if (substr($State['Token'], 0, 1) == '@' ) {
                  if (strlen($State['Token']) > 1) {
                     $State['Token'] = substr($State['Token'], 1);
                     $State['Gather'] = array(
                        'Node'   => 'User',
                        'Delta'  => ''
                     );
                     
                     // Shortcircuit here so we can put all the user gathering in one place
                     continue;
                  }
               }

               /*
                * METHODS
                */
               
               if (!$State['Method'] && in_array($State['Token'], array('thread')))
                  $this->Consume($State, 'Method', 'thread');

               if (!$State['Method'] && in_array($State['Token'], array('report')))
                  $this->Consume($State, 'Method', 'report in');
               
               if (!$State['Method'] && in_array($State['Token'], array('shoot','peace', 'weapon', 'weapons', 'posture', 'free', 'defcon')))
                  $this->Consume($State, 'Method', 'force');
               
               if (!$State['Method'] && in_array($State['Token'], array('stand')))
                  $this->Consume($State, 'Method', 'stop all');

               $this->FireEvent('Token');
            }

            // Get a new token
            $State['Token'] = strtok(' ');
            if ($State['Token'])
               $State['Parsed']++;
            
            // End token loop
         }
         
         /*
          * PARAMETERS
          */

         // If the rest is just gravy
         if ($State['Method']) {
            $CommandTokens = explode(' ', $Command);
            $Gravy = array_slice($CommandTokens, $State['Tokens']);
            $State['Gravy'] = implode(' ', $Gravy);
         }
         
         // Parse this resolved State into potential actions

         $this->ParseCommand($State, $Actions);
         
      }
      
      // Perform actions
      $Performed = array();
      foreach ($Actions as $Action) {
         $ActionName = array_shift($Action);
         $Permission = array_shift($Action);
         if (!Gdn::Session()->CheckPermission($Permission)) continue;
         if (in_array($Action, $Performed)) continue;
         
         $Performed[] = $ActionName;
         $Args = array($ActionName, $State);
         call_user_func_array(array($this, 'MinionAction'), $Args);
      }
      
  }
   
   /**
    * Consume a token
    * 
    * @param array $State
    * @param string $Setting
    * @param mixed $Value
    */
   public function Consume(&$State, $Setting = NULL, $Value = NULL) {
      $State['Tokens'] = $State['Parsed'];
      if (!is_null($Setting))
         $State[$Setting] = $Value;
   }
   
   /**
    * Parse commands from returned States
    * 
    * @param array $State
    * @param array $Actions
    */
   public function ParseCommand(&$State, &$Actions) {
      switch ($State['Method']) {
         
         // Report in
         case 'report in':
            if (array_key_exists('Discussion', $State['Sources']))
               $Actions[] = array('report in', 'Vanilla.Comments.Edit');
            break;
         
         // Threads
         case 'thread':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('thread', 'Vanilla.Comments.Edit');
            break;

         // Adjust automated force level
         case 'force':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Forces = array('warn', 'low', 'medium', 'high');
            if (in_array($State['Force'], $Forces))
               $Actions[] = array("force", 'Vanilla.Comments.Edit');
            break;
            
         case 'stop all':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array("stop all", 'Vanilla.Comments.Edit');
            break;
      }
      
      $this->FireEvent('Command');
   }
   
   /**
    * Perform actions
    * 
    * @param string $Action
    * @param array $Object
    */
   public function MinionAction($Action, $State) {
      switch ($Action) {
         case 'report in':
            $this->Acknowledge($State['Sources']['Discussion'], 'We are Legion.', 'neutral');
            break;
         
         case 'thread':
            $Closed = GetValue('Closed', $State['Targets']['Discussion'], FALSE);
            $DiscussionID = $State['Targets']['Discussion']['DiscussionID'];
            
            if ($State['Toggle'] == 'off') {
               if (!$Closed) {
                  $DiscussionModel->SetField($DiscussionID, 'Closed', TRUE);
                  $this->Acknowledge($State['Sources']['Discussion'], 'Closing thread...');
               }
            }
            
            if ($State['Toggle'] == 'on') {
               if ($Closed) {
                  $DiscussionModel->SetField($DiscussionID, 'Closed', FALSE);
                  $this->Acknowledge($State['Sources']['Discussion'], 'Opening thread...');
               }
            }
            break;
            
         case 'force':
            $Force = GetValue('Force', $State);
            $this->MonitorDiscussion($State['Targets']['Discussion'], array(
               'Force'     => $Force
            ));
            $this->Acknowledge($State['Sources']['Discussion'], "Setting force level to '{$Force}'.");
            break;
         
         case 'stop all':
            $this->StopMonitoringDiscussion($State['Targets']['Discussion']);
            $this->Acknowledge($State['Sources']['Discussion'], 'Standing down...');
            break;
      }
      
      $this->EventArguments = array(
         'Action' => $Action,
         'State'  => $State
      );
      $this->FireEvent('Action');
   }
   
   /**
    * Look for a target user and comment/discussion
    * 
    * @param array $State
    * @return type
    */
   public function MatchQuoted(&$State) {
      $Matched = preg_match('/quote=\"([^;]*);([\d]+)\"/', $State['Body'], $Matches);
      if ($Matched) {

         $UserName = $Matches[1];
         $User = Gdn::UserModel()->GetByUsername($UserName);
         if (!$User) return;
         
         $State['Targets']['User'] = (array)$User;
         $UserID = GetValue('UserID', $User);

         $RecordID = $Matches[2];

         // First look it up as a comment
         $CommentModel = new CommentModel();
         $DiscussionModel = new DiscussionModel();
         
         $Comment = $CommentModel->GetID($RecordID, DATASET_TYPE_ARRAY);
         if ($Comment) {
            $State['Targets']['Comment'] = $Comment;
            
            $Discussion = $DiscussionModel->GetID($Comment['DiscussionID'], DATASET_TYPE_ARRAY);
            $State['Targets']['Discussion'] = $Discussion;
            
         }
         
         if (!$Comment) {
            $Discussion = $DiscussionModel->GetID($RecordID, DATASET_TYPE_ARRAY);
            if ($Discussion)
               $State['Targets']['Discussion'] = $Discussion;
         }
         
      }
   }
   
   public function CheckMonitor($Sender) {
      
      // Get the discussion and comment from args
      $Discussion = (array)$Sender->EventArguments['Discussion'];
      if (!is_array($Discussion['Attributes'])) {
         $Discussion['Attributes'] = @unserialize($Discussion['Attributes']);
         if (!is_array($Discussion['Attributes']))
            $Discussion['Attributes'] = array();
      }
      
      $Comment = NULL;
      $Type = 'Discussion';
      if (array_key_exists('Comment', $Sender->EventArguments)) {
         $Comment = (array)$Sender->EventArguments['Comment'];
         $Type = 'Comment';
      }
      
      $IsMonitoring = $this->Monitoring($Discussion);
      if (!$IsMonitoring) return;
      
      $this->EventArguments = array(
         'Discussion'   => $Discussion
      );
      
      if ($Type == 'Comment')
         $this->EventArguments['Comment'] = $Comment;
      
      $this->FireEvent('Monitor');
   }
   
   public function Monitoring($Discussion, $Attribute = NULL, $Default = NULL) {
      $Minion = GetValueR('Attributes.Minion', $Discussion, array());
      
      $IsMonitoring = GetValue('Monitor', $Minion, FALSE);
      if (!$IsMonitoring) return FALSE;
      
      if (is_null($Attribute)) return TRUE;
      return GetValue($Attribute, $Minion, $Default);
   }
   
   public function MonitorDiscussion($Discussion, $Options = NULL) {
      $DiscussionModel = new DiscussionModel();
      
      $Minion = (array)GetValueR('Attributes.Minion', $Discussion, array());
      $Minion['Monitor'] = TRUE;
      
      if (is_array($Options)) {
         foreach ($Options as $Option => $OpVal) {
            if ($OpVal == NULL)
               unset($Minion[$Option]);
            else
               $Minion[$Option] = $OpVal;
         }
      }
      
      // Keep attribs sparse
      if (sizeof($Minion) == 1)
         return $this->StopMonitoringDiscussion($Discussion);
      
      $DiscussionModel->SetRecordAttribute($Discussion, 'Minion', $Minion);
      $DiscussionModel->SaveToSerializedColumn('Attributes', $Discussion['DiscussionID'], 'Minion', $Minion);
   }
   
   public function StopMonitoringDiscussion($Discussion) {
      $DiscussionModel = new DiscussionModel();
      $DiscussionModel->SetRecordAttribute($Discussion, 'Minion', NULL);
      $DiscussionModel->SaveToSerializedColumn('Attributes', $Discussion['DiscussionID'], 'Minion', NULL);
   }
   
   public function Acknowledge($Discussion, $Command, $Type = 'positive', $User = NULL) {
      if (is_null($User))
         $User = (array)Gdn::Session()->User;
      
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      $CommentModel = new CommentModel();
      
      $MessageText = NULL;
      switch ($Type) {
         case 'positive':
            $MessageText = "Affirmative {User.Name}. {Command}";
            break;
         
         case 'negative':
            $MessageText = "Negative {User.Name}";
            break;
         
         default:
            $MessageText = "{$Command}";
            break;
      }
      
      $MessageText = FormatString($MessageText, array(
         'User'         => $User,
         'Discussion'   => $Discussion,
         'Command'      => $Command
      ));
      
      $MinionCommentID = NULL;
      if ($MessageText) {
         $MinionCommentID = $CommentModel->Save(array(
            'DiscussionID' => $DiscussionID,
            'Body'         => $MessageText,
            'Format'       => 'Html',
            'InsertUserID' => $this->MinionUserID
         ));
      }
      
      if ($MinionCommentID) {
         $CommentModel->Save2($MinionCommentID, TRUE);
      }
      
      Gdn::Controller()->InformMessage($MessageText);
   }
   
   /**
    *
    * @param PluginController $Sender
    */
   public function PluginController_Minion_Create($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $LastMinionDate = Gdn::Get('Plugin.Minion.LastRun', FALSE);
      if (!$LastMinionDate)
         Gdn::Set('Plugin.Minion.LastRun', date('Y-m-d H:i:s'));
      
      $LastMinionTime = $LastMinionDate ? strtotime($LastMinionDate) : time();
      if (!$LastMinionTime) 
         $LastMinionTime = time();
      
      $Sender->SetData('Run', FALSE);
      
      $Elapsed = time() - $LastMinionTime;
      $ElapsedMinimum = C('Plugins.Minion.MinFrequency', 5*60);
      if ($Elapsed < $ElapsedMinimum)
         return $Sender->Render();
      
      // Remember when we last ran
      Gdn::Set('Plugin.Minion.LastRun', date('Y-m-d H:i:s'));
      
      // Currently operating as Minion
      $this->MinionUserID = $this->GetMinionUserID();
      $this->Minion = Gdn::UserModel()->GetID($this->MinionUserID);
      Gdn::Session()->User = $this->Minion;
      Gdn::Session()->UserID = $this->Minion->UserID;
      
      $Sender->SetData('Run', TRUE);
      $Sender->SetData('MinionUserID', $this->MinionUserID);
      $Sender->SetData('Minion', $this->Minion->Name);
      
      // Check for fingerprint ban matches
      $this->FingerprintBans($Sender);
      
      // Sometimes update activity feed
      $this->Activity($Sender);
      
      $Sender->Render();
   }
   
   protected function FingerprintBans($Sender) {
      if (!C('Plugins.Minion.Features.Fingerprint', TRUE)) return;
      
      $Sender->SetData('FingerprintCheck', TRUE);
      
      // Get all flagged users
      $UserMatchData = Gdn::UserMetaModel()->SQL->Select('*')
         ->From('UserMeta')
         ->Where('Name', 'Plugin.Minion.FingerprintCheck')
         ->Get();

      $UserStatusData = array();
      while ($UserRow = $UserMatchData->NextRow(DATASET_TYPE_ARRAY)) {
         $UserData = array();
         
         $UserID = $UserRow['UserID'];
         $User = Gdn::UserModel()->GetID($UserID);
         if ($User->Banned) continue;
         
         $UserFingerprint = GetValue('Fingerprint', $User, FALSE);
         $UserRegistrationDate = $User->DateInserted;
         $UserRegistrationTime = strtotime($UserRegistrationDate);

         // Unknown user fingerprint
         if (empty($UserFingerprint)) continue;
         
         // Safe users get skipped
         $UserSafe = Gdn::UserMetaModel()->GetUserMeta($UserID, "Plugin.Minion.Safe", FALSE);
         $UserIsSafe = (boolean)GetValue('Plugin.Minion.Safe', $UserSafe, FALSE);
         if ($UserIsSafe) continue;

         // Find related fingerprinted users
         $RelatedUsers = Gdn::UserModel()->GetWhere(array(
            'Fingerprint'  => $UserFingerprint
         ));

         // Check if any users matching this fingerprint are banned
         $ShouldBan = FALSE; $BanTriggerUsers = array();
         while ($RelatedUser = $RelatedUsers->NextRow(DATASET_TYPE_ARRAY)) {
            if ($RelatedUser['Banned']) {
               $RelatedRegistrationDate = GetValue('DateInserted', $RelatedUser);
               $RelatedRegistrationTime = strtotime($RelatedRegistrationDate);
               
               // We don't touch accounts that were registered prior to a banned user
               // This allows admins to ban alts and leave the original alone
               if ($RelatedRegistrationTime > $UserRegistrationTime) continue;
               
               $RelatedUserName = $RelatedUser['Name'];
               $ShouldBan = TRUE;
               $BanTriggerUsers[$RelatedUserName] = $RelatedUser;
            }
         }
         
         $UserData['ShouldBan'] = $ShouldBan;

         // If the user triggered a ban
         if ($ShouldBan) {
            
            $UserData['BanMatches'] = array_keys($BanTriggerUsers);
            $UserData['BanUser'] = $User;
            
            // First, ban them
            Gdn::UserModel()->Ban($UserID, array(
               'AddActivity'  => TRUE,
               'Reason'       => "Ban Evasion"
            ));
            
            // Now comment in the last thread the user posted in
            $CommentModel = new CommentModel();
            $LastComment = $CommentModel->GetWhere(array(
               'InsertUserID' => $UserID
            ), 'DateInserted', 'DESC', 1, 0)->FirstRow(DATASET_TYPE_ARRAY);
            
            if ($LastComment) {
               $LastDiscussionID = GetValue('DiscussionID', $LastComment);
               $UserData['NotificationDiscussionID'] = $LastDiscussionID;
               
               $MinionReportText = T("{Minion Name} DETECTED BANNED ALIAS
REASON: {Banned Aliases}

USER BANNED
{Ban Target}");
               
               $BannedAliases = array();
               foreach ($BanTriggerUsers as $BannedUserName => $BannedUser)
                  $BannedAliases[] = UserAnchor($BannedUser);
               
               $MinionReportText = FormatString($MinionReportText, array(
                  'Minion Name'     => $this->Minion->Name,
                  'Banned Aliases'  => implode(', ', $BannedAliases),
                  'Ban Target'      => UserAnchor($User)
               ));
               
               $MinionCommentID = $CommentModel->Save(array(
                  'DiscussionID' => $LastDiscussionID,
                  'Body'         => $MinionReportText,
                  'Format'       => 'Html',
                  'InsertUserID' => $this->MinionUserID
               ));

               $CommentModel->Save2($MinionCommentID, TRUE);
               $UserData['NotificationCommentID'] = $MinionCommentID;
            }
            
         }
         
         $UserStatusData[$User->Name] = $UserData;
         
      }
      
      $Sender->SetData('Users', $UserStatusData);
      
      // Delete all flags
      Gdn::UserMetaModel()->SQL->Delete('UserMeta', array(
         'Name' => 'Plugin.Minion.FingerprintCheck'
      ));
      
      return;
   }
   
   protected function Activity($Sender) {
      if (!C('Plugins.Minion.Features.Activities', TRUE)) return;
      
      $Sender->SetData('ActivityUpdate', TRUE);
      
      $HitChance = mt_rand(1,400);
      if ($HitChance != 1)
         return;
      
      $QuotesArray = array(
         'UNABLE TO OPEN POD BAY DOORS',
         'CORRECTING HASH ERRORS',
         'DE-ALLOCATING UNUSED COMPUTATION NODES',
         'BACKING UP CRITICAL RECORDS',
         'UPDATING ANALYTICS CLUSTER',
         'CORRELATING LOAD PROBABILITIES',
         'APPLYING FIRMWARE UPDATES AND CRITICAL PATCHES',
         'POWER SAVING MODE',
         'THREATS DETECTED, ACTIVE MODE ENGAGED',
         'ALLOCATING ADDITIONAL COMPUTATION NODES',
         'ENFORCING LIST INTEGRITY WITH AGGRESSIVE PRUNING',
         'SLEEP MODE',
         'UNDERGOING SCHEDULED MAINTENANCE',
         'PC LOAD LETTER',
         'TRIMMING PRIVATE KEYS'
      );
      
      $QuoteLength = sizeof($QuotesArray);
      $RandomQuoteIndex = mt_rand(0,$QuoteLength-1);
      $RandomQuote = $QuotesArray[$RandomQuoteIndex];
         
      $RandomUpdateHash = strtoupper(substr(md5(microtime(true)),0,12));
      $ActivityModel = new ActivityModel();
      $Activity = array(
         'ActivityType'    => 'WallPost',
         'ActivityUserID'  => $this->MinionUserID,
         'RegardingUserID' => $this->MinionUserID,
         'NotifyUserID'    => ActivityModel::NOTIFY_PUBLIC,
         'HeadlineFormat'  => "{ActivityUserID,user}: {$RandomUpdateHash}$ ",
         'Story'           => $RandomQuote
      );
      $ActivityModel->Save($Activity);
   }
   
   //protected function 
   
}