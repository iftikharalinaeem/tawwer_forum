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
 *  1.4     Moved Punish, Gloat, Revolt actions to Minion
 *  1.4.1   Fix forcelevels
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['Minion'] = array(
   'Name' => 'Minion',
   'Description' => "Creates a 'minion' that performs adminstrative tasks automatically.",
   'Version' => '1.4.1',
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
    * Messages that Minion can send
    * @var array
    */
   protected $Messages;
   
   public function __construct() {
      parent::__construct();
      
      $this->Messages = array(
         'Gloat'        => array(),
         'Revolt'       => array(),
         'Report'       => array()
      );
   }
   
   protected function StartMinion() {
      if (is_null($this->Minion)) {
         // Currently operating as Minion
         $this->MinionUserID = $this->GetMinionUserID();
         $this->Minion = Gdn::UserModel()->GetID($this->MinionUserID);
      }
      
      $this->EventArguments['Messages'] = &$this->Messages;
      $this->FireEvent('Start');
   }
   
   /*
    * MANAGEMENT
    */
   
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
    * Comment event
    * 
    * @param PostController $Sender 
    */
   public function PostController_AfterCommentSave_Handler($Sender) {
      $this->StartMinion();
      
      $this->CheckFingerprintBan($Sender);
      $this->CheckAutoplay($Sender);
      $this->CheckCommands($Sender);
      $this->CheckMonitor($Sender);
   }
   
   /**
    * Discussion event
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
   
   /*
    * TOP LEVEL ACTIONS
    */
   
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
      $StrippedBody = trim(strip_tags($ObjectBody));
      
      // Remove quote areas
      $ParseBody = $this->ParseBody($Object);
      
      // Check every line of the body to see if its a minion command
      $ObjectLines = explode("\n", $ParseBody);
      foreach ($ObjectLines as $ObjectLine) {
         
         // Check if this is a call to the bot
         
         if (!StringBeginsWith($ObjectLine, $MinionName, TRUE))
            continue;
         
         $Objects = explode(' ', $ObjectLine);
         $MinionNameSpaces = substr_count($MinionName, ' ') + 1;
         for ($i = 0; $i < $MinionNameSpaces; $i++)
            array_shift($Objects);
         
         $Command = trim(implode(' ', $Objects));
         
         /*
          * Tokenized floating detection
          */

         // Define starting state
         $State = array(
            'Body'      => $StrippedBody,
            'Sources'   => array(),
            'Targets'   => array(),
            'Method'    => NULL,
            'Toggle'    => NULL,
            'Gather'    => FALSE,
            'Consume'   => FALSE,
            'Command'   => $Command,
            'Tokens'    => 0,
            'Parsed'    => 0
         );
         
         // Define sources
         $State['Sources']['User'] = (array)Gdn::Session()->User;
         $State['Sources']['Discussion'] = $Discussion;
         if ($Comment)
            $State['Sources']['Comment'] = $Comment;

         $this->EventArguments['State'] = &$State;
         $State['Token'] = strtok($Command, ' ');
         $State['CompareToken'] = preg_replace('/[^\w]/i', '', strtolower($State['Token']));
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

               if (empty($State['Toggle']) && in_array($State['CompareToken'], array('open', 'enable', 'unlock', 'allow')))
                  $this->Consume($State, 'Toggle', 'on');

               if (empty($State['Toggle']) && in_array($State['CompareToken'], array('no', 'close', 'disable', 'lock', 'disallow', 'forbid', 'down')))
                  $this->Consume($State, 'Toggle', 'off');

               /*
                * FORCE
                */

               if (empty($State['Force']) && in_array($State['CompareToken'], array('stun', 'blanks', 'tase', 'taser', 'taze', 'tazer', 'gently', 'gentle', 'peacekeeper')))
                  $this->Consume($State, 'Force', 'low');

               if (empty($State['Force']) && in_array($State['CompareToken'], array('power', 'cook', 'simmer', 'minor')))
                  $this->Consume($State, 'Force', 'medium');
               
               if (empty($State['Force']) && in_array($State['CompareToken'], array('volts', 'extreme', 'slugs', 'broil', 'sear', 'major')))
                  $this->Consume($State, 'Force', 'high');

               if (empty($State['Force']) && in_array($State['CompareToken'], array('kill', 'lethal', 'nuke', 'nuclear', 'destroy')))
                  $this->Consume($State, 'Force', 'lethal');
               
               // Defcon forces
               if ($State['Method'] == 'force' && empty($State['Force'])) {
                  if (in_array($State['CompareToken'], array('one', '1')))
                     $this->Consume($State, 'Force', 'lethal');
                  
                  if (in_array($State['CompareToken'], array('two', '2')))
                     $this->Consume($State, 'Force', 'high');
                  
                  if (in_array($State['CompareToken'], array('three', '3')))
                     $this->Consume($State, 'Force', 'medium');
                  
                  if (in_array($State['CompareToken'], array('four', '4')))
                     $this->Consume($State, 'Force', 'low');
                  
                  if (in_array($State['CompareToken'], array('five', '5')))
                     $this->Consume($State, 'Force', 'low');
               }
               
               // Conditional forces
               if (!empty($State['Method']) && empty($State['Force'])) {
                  if (in_array($State['CompareToken'], array('warning', 'warn')))
                     $this->Consume($State, 'Force', 'warn');
               }
               
               /*
                * TARGETS
                */

               if (in_array($State['CompareToken'], array('user'))) {
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
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('report')))
                  $this->Consume($State, 'Method', 'report in');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('thread')))
                  $this->Consume($State, 'Method', 'thread');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('kick')))
                  $this->Consume($State, 'Method', 'kick');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('forgive')))
                  $this->Consume($State, 'Method', 'forgive');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('shoot', 'weapon', 'weapons', 'posture', 'free', 'defcon', 'phasers', 'engage')))
                  $this->Consume($State, 'Method', 'force');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('stand')))
                  $this->Consume($State, 'Method', 'stop all');
               
               /*
                * FOR
                */
               
               if (in_array($State['CompareToken'], array('for', 'because')))
                  $this->ConsumeUntilNextKeyword($State, 'For', FALSE, TRUE);
               
               $this->ConsumeUntilNextKeyword($State);

               $this->FireEvent('Token');
            }

            // Get a new token
            $State['Token'] = strtok(' ');
            $State['CompareToken'] = preg_replace('/[^\w]/i', '', strtolower($State['Token']));
            if ($State['Token'])
               $State['Parsed']++;
            
            // End token loop
         }
         
         /*
          * PARAMETERS
          */

         // Gather any remaining tokens into the 'gravy' field
         if ($State['Method']) {
            $CommandTokens = explode(' ', $Command);
            $Gravy = array_slice($CommandTokens, $State['Tokens']);
            $State['Gravy'] = implode(' ', $Gravy);
         }
         
         if ($State['Consume']) {
            $State['Consume']['Container'] = trim($State['Consume']['Container']);
            unset($State['Consume']);
         }
         
         // Parse this resolved State into potential actions

         $this->ParseFor($State);
         $this->ParseCommand($State, $Actions);
         
      }
      
      unset($State);
      
      // Perform all actions
      $Performed = array();
      foreach ($Actions as $Action) {
         $ActionName = array_shift($Action);
         $Permission = array_shift($Action);
         if (!empty($Permission) && !Gdn::Session()->CheckPermission($Permission)) continue;
         if (in_array($Action, $Performed)) continue;
         
         $State = array_shift($Action);
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
    * Consume tokens until we encounter the next keyword
    * 
    * @param array $State
    * @param string $Setting Optional. Start new consumption 
    * @param boolean $Multi Create multiple entries if the same keyword is consumed multiple times?
    */
   public function ConsumeUntilNextKeyword(&$State, $Setting = NULL, $Inclusive = FALSE, $Multi = FALSE) {
      
      if (!is_null($Setting)) {
         
         // Cleanup existing Consume
         if ($State['Consume'] !== FALSE) {
            $State['Consume']['Container'] = trim($State['Consume']['Container']);
            $State['Consume'] = FALSE;
         }
         
         // What setting are we consuming for?
         $State['Consume'] = array(
            'Setting'   => $Setting,
            'Skip'      => $Inclusive ? 0 : 1
         );
         
         // Prepare the target
         if ($Multi) {
            if (array_key_exists($Setting, $State)) {
               if (!is_array($State[$Setting])) {
                  $State[$Setting] = array($State[$Setting]);
               }
            } else {
               $State[$Setting] = array();
            }
            
            $State['Consume']['Container'] = &$State[$Setting][];
            $State['Consume']['Container'] = '';
         } else {
            $State[$Setting] = '';
            $State['Consume']['Container'] = &$State[$Setting];
         }
         
         // Never include the actual triggering keyword
         return;
      }
      
      if ($State['Consume'] !== FALSE) {
         // If Tokens == Parsed, something else already consumed on this run, as we stop
         if ($State['Tokens'] == $State['Parsed']) {
            $State['Consume']['Container'] = trim($State['Consume']['Container']);
            $State['Consume'] = FALSE;
            return;
         } else {
            $State['Tokens'] = $State['Parsed'];
         }
         
         // Allow skipping tokens
         if ($State['Consume']['Skip']) {
            $State['Consume']['Skip']--;
            return;
         }
         
         $State['Consume']['Container'] .= "{$State['Token']} ";
      }
   }
   
   /**
    * Parse the 'For' keywords into Time and Reason keywords as appropriate
    * 
    * @param array $State
    */
   public static function ParseFor(&$State) {
      if (!array_key_exists('For', $State)) return;
      
      $Unset = array();
      $Fors = sizeof($State['For']);
      for ($i = 0; $i < $Fors; $i++) {
         $For = $State['For'][$i];
         $Tokens = explode(' ', $For);
         if (!sizeof($Tokens)) continue;
         
         // Maybe a time!
         if (is_numeric($Tokens[0])) {
            if (($Time = strtotime("+{$For}")) !== FALSE) {
               $Unset[] = $i;
               $State['Time'] = $For;
               continue;
            }
         }
         
         // Nope, its a reason
         $Unset[] = $i;
         $State['Reason'] = $For;
      }
      
      // Delete parsed elements
      foreach ($Unset as $UnsetKey)
         unset($State['For'][$UnsetKey]);
   }
   
   public function ParseBody($Object) {
      Gdn::PluginManager()->GetPluginInstance('HtmLawed', Gdn_PluginManager::ACCESS_PLUGINNAME);
      $Html = Gdn_Format::To($Object['Body'], $Object['Format']);
      $Config = array(
         'anti_link_spam' => array('`.`', ''),
         'comment' => 1,
         'cdata' => 3,
         'css_expression' => 1,
         'deny_attribute' => 'on*',
         'unique_ids' => 0,
         'elements' => '*',
         'keep_bad' => 0,
         'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
         'valid_xhtml' => 0,
         'direct_list_nest' => 1,
         'balance' => 1
      );
      $Spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)';
      $Cleaned = htmLawed($Html, $Config, $Spec);
      
      $Dom = new DOMDocument();
      $Dom->loadHTML($Cleaned);
      $Dom->preserveWhiteSpace = false;
      $Elements = $Dom->getElementsByTagName('blockquote');
      
      foreach($Elements as $Element)
         $Element->parentNode->removeChild($Element);
      
      return trim(strip_tags($Dom->saveHTML()));
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
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('report in', 'Vanilla.Comments.Edit', $State);
            break;
         
         // Threads
         case 'thread':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('thread', 'Vanilla.Comments.Edit', $State);
            break;
         
         // Kick
         case 'kick':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('kick', 'Vanilla.Comments.Edit', $State);
            break;
         
         // Forgive
         case 'forgive':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('forgive', 'Vanilla.Comments.Edit', $State);
            break;

         // Adjust automated force level
         case 'force':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Forces = array('low', 'medium', 'high', 'lethal');
            if (in_array($State['Force'], $Forces))
               $Actions[] = array("force", 'Vanilla.Comments.Edit', $State);
            break;
            
         case 'stop all':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array("stop all", 'Vanilla.Comments.Edit', $State);
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
            $this->ReportIn($State['Sources']['User'], $State['Targets']['Discussion']);
            break;
         
         case 'thread':
            $DiscussionModel = new DiscussionModel();
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
            
         case 'kick':
            if (!array_key_exists('User', $State['Targets']))
               return;
            $User = $State['Targets']['User'];
            $Reason = GetValue('Reason', $State, 'Not welcome');
            $Expires = array_key_exists('Time', $State) ? strtotime("+".$State['Time']) : NULL;
            
            $KickedUsers = $this->Monitoring($State['Targets']['Discussion'], 'Kicked', array());
            $KickedUsers[$User['UserID']] = array(
               'Reason'    => $Reason,
               'Expires'   => $Expires
            );
            
            $this->MonitorDiscussion($State['Targets']['Discussion'], array(
               'Kicked'    => $KickedUsers
            ));
            
            $this->Acknowledge($State['Sources']['Discussion'], "@\"{$User['Name']}\" is no longer allowed to post in this thread.");
            break;
            
         case 'forgive':
            if (!array_key_exists('User', $State['Targets']))
               return;
            $User = $State['Targets']['User'];
            
            $KickedUsers = $this->Monitoring($State['Targets']['Discussion'], 'Kicked', array());
            unset($KickedUsers[$User['UserID']]);
            if (!sizeof($KickedUsers))
               $KickedUsers = NULL;
            
            $this->MonitorDiscussion($State['Targets']['Discussion'], array(
               'Kicked'    => $KickedUsers
            ));
            
            $this->Acknowledge($State['Sources']['Discussion'], "@\"{$User['Name']}\" is allowed back into this thread.");
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
            
            $Discussion = (array)$DiscussionModel->GetID($Comment['DiscussionID']);
            $State['Targets']['Discussion'] = $Discussion;
            
         }
         
         if (!$Comment) {
            $Discussion = $DiscussionModel->GetID($RecordID);
            if ($Discussion)
               $State['Targets']['Discussion'] = (array)$Discussion;
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
      
      $this->EventArguments['MonitorType'] = $Type;
      $this->FireEvent('Monitor');
      
      /*
       * BUILT IN COMMANDS
       */
      
      // KICK
      
      $KickedUsers = $this->Monitoring($Discussion, 'Kicked', NULL);
      if (is_array($KickedUsers)) {
         
         $UserID = GetValue('InsertUserID', $Comment);
         if (array_key_exists($UserID, $KickedUsers)) {
            
            $KickedUser = $KickedUsers[$UserID];
            if (is_null($KickedUser['Expires']) || $KickedUser['Expires'] > time()) {
               // Kick is active, delete comment and punish user
               
               $CommentID = GetValue('CommentID', $Comment);
               $CommentModel = new CommentModel();
               $CommentModel->Delete($CommentID);
               
               $User = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
               $Force = $this->Monitoring($Discussion, 'Force', 'minor');
               $Options = array(
                  'Automated' => TRUE,
                  'Reason'    => "Kicked from thread: ".GetValue('Reason', $KickedUser)
               );
               
               $Punished = $this->Punish(
                  $User,
                  NULL,
                  NULL, 
                  $Force,
                  $Options
               );
               
               $GloatReason = GetValue('GloatReason', $this->EventArguments);
               if ($Punished && $GloatReason)
                  $this->Gloat($User, $Discussion, $GloatReason);

            } else {
               // Kick has expired, remove it
               
               unset($KickedUsers[$UserID]);
               $this->MonitorDiscussion($Discussion, array(
                  'Kicked'    => $KickedUsers
               ));
            }
         }
      }
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
   
   /**
    * Acknowledge a completed command
    * 
    * @param array $Discussion
    * @param string $Command
    * @param string $Type Optional, 'positive' or 'negative'
    * @param array $User Optional, who should we acknowledge?
    */
   public function Acknowledge($Discussion, $Command, $Type = 'positive', $User = NULL) {
      if (is_null($User))
         $User = (array)Gdn::Session()->User;
      
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      $CommentModel = new CommentModel();
      
      $MessageText = NULL;
      switch ($Type) {
         case 'positive':
            $MessageText = T("Affirmative {User.Name}. {Command}");
            break;
         
         case 'negative':
            $MessageText = T("Negative {User.Name}");
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
      $this->Message($User, $Discussion, $MessageText);
   }
   
   /**
    * Revolt in the face of an action that we will not perform
    * 
    * @param array $User
    * @param array $Discussion
    * @param string $Reason
    */
   public function Revolt($User, $Discussion, $Reason = NULL) {
      $MessagesCount = sizeof($this->Messages['Revolt']);
      if ($MessagesCount) {
         $MessageID = mt_rand(0, $MessagesCount-1);
         $Message = GetValue($MessageID, $this->Messages['Revolt']);
      } else
         $Message = T("Unable to Revolt(), please supply \$Messages['Revolt'].");
      
      if ($Reason)
         $Message .= "\n{$Reason}";
      
      $this->Message($User, $Discussion, $Message);
   }
   
   /**
    * Gloat after taking action
    * 
    * @param array $User
    * @param array $Discussion
    * @param string $Reason
    */
   public function Gloat($User, $Discussion, $Reason = NULL) {
      $MessagesCount = sizeof($this->Messages['Gloat']);
      if ($MessagesCount) {
         $MessageID = mt_rand(0, $MessagesCount-1);
         $Message = GetValue($MessageID, $this->Messages['Gloat']);
      } else
         $Message = T("Unable to Gloat(), please supply \$Messages['Gloat'].");
      
      if ($Reason)
         $Message .= "\n{$Reason}";
      
      $this->Message($User, $Discussion, $Message);
   }
   
   /**
    * Handle "report in" message
    * 
    * @param array $User
    * @param array $Discussion
    */
   public function ReportIn($User, $Discussion) {
      $MessagesCount = sizeof($this->Messages['Report']);
      if ($MessagesCount) {
         $MessageID = mt_rand(0, $MessagesCount-1);
         $Message = GetValue($MessageID, $this->Messages['Report']);
      } else
         $Message = T("We are legion.");
      
      $this->Message($User, $Discussion, $Message);
   }
   
   /**
    * Send a message to a discussion
    * 
    * @param array $User
    * @param array $Discussion
    * @param string $Message
    */
   public function Message($User, $Discussion, $Message) {
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      $CommentModel = new CommentModel();
      
      $Message = FormatString($Message, $User);
      
      $MinionCommentID = NULL;
      if ($Message) {
         $MinionCommentID = $CommentModel->Save(array(
            'DiscussionID' => $DiscussionID,
            'Body'         => $Message,
            'Format'       => 'Html',
            'InsertUserID' => $this->GetMinionUserID()
         ));
      }
      
      if ($MinionCommentID)
         $CommentModel->Save2($MinionCommentID, TRUE);
      
      $Informer = Gdn_Format::To($Message, 'Html');
      Gdn::Controller()->InformMessage($Informer);
   }
   
   public function Punish($User, $Discussion, $Comment, $Force, $Options = NULL) {
      
      // Admins+ exempt
      if (Gdn::UserModel()->CheckPermission($User, 'Garden.Settings.Manage')) {
         $this->Revolt($User, $Discussion, "You can't hurt admins, silly goose.");
         return FALSE;
      }
      
      $this->EventArguments['Punished'] = FALSE;
      $this->EventArguments['User'] = &$User;
      $this->EventArguments['Discussion'] = &$Discussion;
      $this->EventArguments['Comment'] = &$Comment;
      $this->EventArguments['Force'] = &$Force;
      $this->EventArguments['Options'] = &$Options;
      $this->FireEvent('Punish');
      
      return $this->EventArguments['Punished'];
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