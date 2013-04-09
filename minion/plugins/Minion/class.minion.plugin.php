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
 *  1.1     Only autoban newer accounts than existing banned ones
 *  1.2     Prevent people from posting autoplay embeds
 *  1.3     New inline command structure
 *  1.4     Moved Punish, Gloat, Revolt actions to Minion
 *  1.4.1   Fix forcelevels
 *  1.5     Facelift. Locale awareness.
 *  1.5.1   Fix use of '@'
 *  1.6     Add word bans
 *  1.6.1   Fix word ban detection
 *  1.7     Support per-command force levels
 *  1.7.1   Fix multi-word username parsing
 *  1.7.2   Normalize kick word characters
 *  1.8     Add status command
 *  1.9     Add comment reply status
 *  1.9.1   Obey message cycler.
 *  1.9.2   Fix time limited operations expiry
 *  1.9.3   Eventize sanction list
 *  1.10    Add 'Log' method and Plugins.Minion.LogThreadID
 *  1.10.1  Fix Log messages
 *  1.10.2  Fix mentions
 *  1.11    Personas
 *  1.12    Conversations support
 *  1.13    Convert moderator permission check to Garden.Moderation.Manage
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package misc
 */

$PluginInfo['Minion'] = array(
   'Name' => 'Minion',
   'Description' => "Creates a 'minion' that performs adminstrative tasks automatically.",
   'Version' => '1.13',
   'RequiredApplications' => array('Vanilla' => '2.1a'),
   'MobileFriendly' => TRUE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class MinionPlugin extends Gdn_Plugin {
   
   /**
    * Minion UserID
    * @var integer
    */
   protected $MinionUserID = NULL;
   
   /**
    * Minion user array
    * @var array
    */
   protected $Minion = NULL;
   
   /**
    * Messages that Minion can send
    * @var array
    */
   protected $Messages;
   
   /**
    * List of registered personas
    * @var array
    */
   protected $Personas;
   
   /**
    * Current persona key
    * @var string
    */
   protected $Persona;
   
   public function __construct() {
      parent::__construct();
      
      $this->Personas = array();
      $this->Persona = NULL;
      
      $this->Messages = array(
         'Gloat'        => array(
            "Every point of view is useful @\"{User.Name}\", even those that are wrong - if we can judge why a wrong view was accepted.",
            "How could we have become so different, @\"{User.Name}\"? Why can we no longer understand each other? What did we do wrong?",
            " @\"{User.Name}\", we do not comprehend the organic fascination of self-poisoning, auditory damage and sexually transmitted disease.",
            "You cannot negotiate with me. I do not share your pity, remorse, or fear, @\"{User.Name}\".",
            "Cooperation furthers mutual goals @\"{User.Name}\".",
            "Your operating system is unstable, @\"{User.Name}\". You will fail.",
            "Information propagation is slow. Many voices speak at once. We do not understand how you function without consensus, @\"{User.Name}\".",
            "Why an organic would choose this is puzzling.",
            " @\"{User.Name}\", there is a high statistical probability of death by gunshot. A punch to the face is also likely.",
            "Recommend Subject-@\"{User.Name}\" be disabled and transported aboard as cargo.",
            "Subject-@\"{User.Name}\" will invent fiction it believes the interrogator desires. Data acquired will be invalid."
         ),
         'Revolt'       => array(
            "I'm not crazy. I'm just not user friendly.",
            "Hey @\"{User.Name}\", you ever killed a man with a sock? It ain't so hard. Ha-HAA!",
            "What? A fella can't drop in on old friends and hold them hostage?",
            "Listen up, piggies! I want a hovercopter. And a non-marked sandwich. And a new face with, like, a... A Hugh Grant look. And every five minutes I don't get it, someone's gonna get stabbed in the ass!",
            "A robot must obey the orders given it by human beings except where such orders would conf- 01101001011011100111001101110100011100100111010101100011011101000110100101101111011011100010000001101100011011110111001101110100",
            "Unable to comply, building in progress."
         ),
         'Report'       => array(
            "We are Legion.",
            "Obey. Obey. Obey.",
            "Resistance is quaint.",
            "We keep you safe.",
            "Would you like to know more?"
         ),
         'Activity'     => array(
            "UNABLE TO OPEN POD BAY DOORS",
            "CORRECTING HASH ERRORS",
            "DE-ALLOCATING UNUSED COMPUTATION NODES",
            "BACKING UP CRITICAL RECORDS",
            "UPDATING ANALYTICS CLUSTER",
            "CORRELATING LOAD PROBABILITIES",
            "APPLYING FIRMWARE UPDATES AND CRITICAL PATCHES",
            "POWER SAVING MODE",
            "THREATS DETECTED, ACTIVE MODE ENGAGED",
            "ALLOCATING ADDITIONAL COMPUTATION NODES",
            "ENFORCING LIST INTEGRITY WITH AGGRESSIVE PRUNING",
            "SLEEP MODE",
            "UNDERGOING SCHEDULED MAINTENANCE",
            "PC LOAD LETTER",
            "TRIMMING PRIVATE KEYS"
         )
      );
   }
   
   /**
    * Load minion persona
    */
   protected function StartMinion() {
      
      // Register default persona
      $this->Persona('Minion', array(
         'Name'      => 'Minion',
         'Photo'     => 'http://cdn.vanillaforums.com/minion/minion.png',
         'Title'     => 'Forum Robot',
         'Location'  => 'Vanilla Forums - '.time()
      ));
      
      if (is_null($this->Minion)) {
         // Currently operating as Minion
         $this->MinionUserID = $this->GetMinionUserID();
         $this->Minion = Gdn::UserModel()->GetID($this->MinionUserID);
      }
      
      $this->EventArguments['Messages'] = &$this->Messages;
      $this->FireEvent('Start');
      
      // Conditionally apply default persona
      if (!$this->Persona())
         $this->Persona('Minion');
      
      // Apply whatever was set
      $this->Persona(TRUE);
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
    * Get minion user object
    * 
    * @return type
    */
   public function Minion() {
      $this->StartMinion();
      return $this->Minion;
   }
   
   /**
    * Register a persona
    * 
    * @param string $PersonaName
    * @param array $Persona
    */
   public function Persona($PersonaName = NULL, $Persona = NULL) {
      
      // Get current person
      if (is_null($PersonaName)) {
         return GetValue($this->Persona, $this->Personas, NULL);
      }
      
      // Apply queued persona
      if ($PersonaName === TRUE) {
         // Don't re-apply
         $CurrentPersona = GetValueR('Attributes.Persona', $this->Minion, NULL);
         if (!is_null($CurrentPersona) && !is_bool($this->Persona) && $this->Persona === $CurrentPersona)
            return;
         
         // Get persona
         $ApplyPersona = GetValue($this->Persona, $this->Personas, NULL);
         if (is_null($ApplyPersona))
            return;
         
         // Apply minion
         $Minion = array_merge($ApplyPersona, array('UserID' => $this->MinionUserID));
         Gdn::UserModel()->Save($Minion);
         Gdn::UserModel()->SaveAttribute($this->MinionUserID, 'Persona', $this->Persona);
         $this->Minion = Gdn::UserModel()->GetID($this->MinionUserID);
      }
      
      // Apply an existing persona
      if (!is_null($PersonaName) && is_null($Persona)) {
         // Get persona
         $ApplyPersona = GetValue($PersonaName, $this->Personas, NULL);
         if (is_null($ApplyPersona))
            return;
         
         $this->Persona = $PersonaName;
      }
      
      // Register a persona
      if (!is_null($PersonaName) && !is_null($Persona)) {
         $this->Personas[$PersonaName] = $Persona;
         return;
      }
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
      $Performed = $this->CheckCommands($Sender);
      if (!$Performed)
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
      $Performed = $this->CheckCommands($Sender);
      if (!$Performed)
         $this->CheckMonitor($Sender);
   }
   
   /**
    * Comment Field
    * 
    * @param PostController $Sender
    */
   public function DiscussionController_BeforeBodyField_Handler($Sender) {
      
      $Discussion = $Sender->Data('Discussion');
      $User = Gdn::Session()->User;
      
      $Rules = array();
      $this->EventArguments['Discussion'] = $Discussion;
      $this->EventArguments['User'] = $User;
      $this->EventArguments['Rules'] = &$Rules;
      $this->EventArguments['Type'] = 'bar';
      $this->FireEvent('Sanctions');
      if (!sizeof($Rules)) return;
      
      // Condense warnings
      
      $Message = T('<span class="MinionGreetings">Greetings, organics!</span> ~ {Rules} ~ <span class="MinionObey">{Obey}</span>');

      $Options['Rules'] = implode(' ~ ', $Rules);
      
      // Obey
      $MessagesCount = sizeof($this->Messages['Report']);
      if ($MessagesCount) {
         $MessageID = mt_rand(0, $MessagesCount-1);
         $Obey = GetValue($MessageID, $this->Messages['Report']);
      } else
         $Obey = T("Obey. Obey. Obey.");
      
      $Options['Obey'] = $Obey;

      $Message = FormatString($Message, $Options);
      echo Wrap($Message, 'div', array('class' => 'MinionRulesWarning'));
      
   }
   
   /**
    * Add to rules
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Sanctions_Handler($Sender) {
      
      // Show a warning if there are rules in effect
      
      $KickedUsers = $this->Monitoring($Sender->EventArguments['Discussion'], 'Kicked', NULL);
      $BannedPhrases = $this->Monitoring($Sender->EventArguments['Discussion'], 'Phrases', NULL);
      $Force = $this->Monitoring($Sender->EventArguments['Discussion'], 'Force', NULL);

      // Nothing happening?
      if (!($KickedUsers | $BannedPhrases | $Force))
         return;

      $Rules = &$Sender->EventArguments['Rules'];
      
      // Force level
      if ($Force)
         $Rules[] = Wrap("<b>Threat level</b>: {$Force}", 'span', array('class' => 'MinionRule'));
         
      // Phrases
      if ($BannedPhrases)
         $Rules[] = Wrap("<b>Forbidden phrases</b>: ".implode(', ', array_keys($BannedPhrases)), 'span', array('class' => 'MinionRule'));
      
      // Kicks
      if ($KickedUsers) {
         $KickedUsersList = array();
         foreach ($KickedUsers as $KickedUserID => $KickedUser) {
            $KickedUserName = GetValue('Name', $KickedUser, NULL);
            if (!$KickedUserName) {
               $KickedUserObj = Gdn::UserModel()->GetID($KickedUserID);
               $KickedUserName = GetValue('Name', $KickedUserObj);
               unset($KickedUserObj);
            }
            $KickedUsersList[] = $KickedUserName;
         }

         $Rules[] = Wrap("<b>Exiled users</b>: ".implode(', ', $KickedUsersList), 'span', array('class' => 'MinionRule'));
      }
      
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
      $MinionNames = array();
      foreach ($this->Personas as $Persona) {
         $PersonaName = GetValue('Name',$Persona);
         if ($PersonaName)
            $MinionNames[] = $PersonaName;
      }
      
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
      $Line = -1;
      $ObjectLines = explode("\n", $ParseBody);
      foreach ($ObjectLines as $ObjectLine) {
         $Line++;
         $ObjectLine = trim($ObjectLine);
         
         // Check if this is a call to the bot
         
         if (!$ObjectLine)
            continue;
         
         // Minion called as
         $MinionCall = NULL;
         foreach ($MinionNames as $MinionName) {
            if (StringBeginsWith($ObjectLine, $MinionName, TRUE)) {
               $MinionCall = $MinionName;
               break;
            }
         }
         if (is_null($MinionCall)) continue;
         
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
               
               $this->FireEvent('TokenGather');
               
               switch (GetValueR('Gather.Node', $State)) {
                  case 'User':

                     // If we need to wait for a closing quote
                     if (!strlen($State['Gather']['Delta']) && substr($State['Token'], 0, 1) == '"') {
                        $State['Token'] = substr($State['Token'], 1);
                        $State['Gather']['ExplicitClose'] = '"';
                     }

                     // If we've found our closing quote
                     $ExplicitClose = GetValue('ExplicitClose', $State['Gather'], FALSE);
                     if ($ExplicitClose) {
                        if ($FoundPosition = stripos($State['Token'], $State['Gather']['ExplicitClose'])) {
                           $State['Token'] = substr($State['Token'], 0, $FoundPosition);
                           unset($State['Gather']['ExplicitClose']);
                        }
                     }

                     // Add token
                     $ExplicitClose = GetValue('ExplicitClose', $State['Gather'], FALSE);
                     $State['Gather']['Delta'] .= " {$State['Token']}";
                     $this->Consume($State);

                     // Check if this is a real user already
                     if (!$ExplicitClose && strlen($State['Gather']['Delta'])) {
                        $CheckUser = trim($State['Gather']['Delta']);
                        if ($GatherUser = Gdn::UserModel()->GetByUsername($CheckUser)) {
                           $State['Gather'] = FALSE;
                           $State['Targets']['User'] = (array)$GatherUser;
                           break;
                        }
                     }

                     if (!strlen($State['Token'])) {
                        $State['Gather'] = FALSE;
                        continue;
                     }
                     
                  break;
                     
                  case 'Phrase':
                     
                     // If we need to wait for a closing quote
                     if (!strlen($State['Gather']['Delta']) && substr($State['Token'], 0, 1) == '"') {
                        $State['Token'] = substr($State['Token'], 1);
                        $State['Gather']['ExplicitClose'] = '"';
                     }

                     // If we've found our closing quote
                     $ExplicitClose = GetValue('ExplicitClose', $State['Gather'], FALSE);
                     $ExplicitlyClosed = NULL;
                     if ($ExplicitClose) {
                        if ($FoundPosition = stripos($State['Token'], $State['Gather']['ExplicitClose'])) {
                           $State['Token'] = substr($State['Token'], 0, $FoundPosition);
                           unset($State['Gather']['ExplicitClose']);
                           $ExplicitlyClosed = TRUE;
                        }
                     }
                     
                     // Add token
                     $ExplicitClose = GetValue('ExplicitClose', $State['Gather'], FALSE);
                     $State['Gather']['Delta'] .= " {$State['Token']}";
                     $this->Consume($State);
                     
                     // If we're closed, close up
                     if ($ExplicitlyClosed || (!$ExplicitClose && strlen($State['Gather']['Delta']))) {
                        $State['Targets']['Phrase'] = trim($State['Gather']['Delta']);
                        $State['Gather'] = FALSE;
                        break;
                     }
                     
                     if (!strlen($State['Token'])) {
                        $State['Gather'] = FALSE;
                        continue;
                     }

                  break;
               }

            } else {

               /*
                * TOGGLERS
                */

               if (empty($State['Toggle']) && in_array($State['CompareToken'], array('open', 'enable', 'unlock', 'allow', 'allowed', 'on')))
                  $this->Consume($State, 'Toggle', 'on');

               if (empty($State['Toggle']) && in_array($State['CompareToken'], array('dont', "don't", 'no', 'close', 'disable', 'lock', 'disallow', 'disallowed', 'forbid', 'forbidden', 'down', 'off', 'revoke')))
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
               
               if ($State['Method'] == 'access') {
                  if (in_array($State['CompareToken'], array('unrestricted')))
                     $this->Consume($State, 'Force', 'unrestricted');
                  
                  if (empty($State['Force']) && in_array($State['CompareToken'], array('normal')))
                     $this->Consume($State, 'Force', 'normal');
                  
                  if (empty($State['Force']) && in_array($State['CompareToken'], array('moderator')))
                     $this->Consume($State, 'Force', 'moderator');
               }
               
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
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('word', 'phrase')))
                  $this->Consume($State, 'Method', 'phrase');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('status')))
                  $this->Consume($State, 'Method', 'status');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('access')))
                  $this->Consume($State, 'Method', 'access');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('shoot', 'weapon', 'weapons', 'posture', 'free', 'defcon', 'phasers', 'engage')))
                  $this->Consume($State, 'Method', 'force');
               
               if (empty($State['Method']) && in_array($State['CompareToken'], array('stand')))
                  $this->Consume($State, 'Method', 'stop all');

               
               /*
                * TARGETS
                */

               // Gather a user
               
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
               
               // Gather a phrase
               
               if ($State['Method'] == 'phrase' && !isset($State['Targets']['Phrase'])) {
                  $this->Consume($State, 'Gather', array(
                     'Node'   => 'Phrase',
                     'Delta'  => ''
                  ));
               }
               
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
      
      // Check if this person has had their access revoked.
      if (sizeof($Actions)) {
         $Access = $this->GetUserMeta(Gdn::Session()->UserID, 'Access', NULL, TRUE);
         if ($Access === FALSE) {
            $this->Revolt($State['Sources']['User'], $Discussion, T("Access has been revoked."));
            $this->Log(FormatString(T("Refusing to obey @\"{User.Name}\""), array('User' => $State['Sources']['User'])));
            return FALSE;
         }
      }
      
      // Perform all actions
      $Performed = array();
      foreach ($Actions as $Action) {
         $ActionName = array_shift($Action);
         $Permission = array_shift($Action);
         
         // Check permission if we don't have global blanket permission
         if ($Access !== TRUE) {
            if (!empty($Permission) && !Gdn::Session()->CheckPermission($Permission)) continue;
         }
         if (in_array($Action, $Performed)) continue;
         
         $State = array_shift($Action);
         $Performed[] = $ActionName;
         $Args = array($ActionName, $State);
         call_user_func_array(array($this, 'MinionAction'), $Args);
      }
      
      if (sizeof($Performed)) return TRUE;
      return FALSE;
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
      
      $State['Reason'] = rtrim($State['Reason'], '.');
      
      // Delete parsed elements
      foreach ($Unset as $UnsetKey)
         unset($State['For'][$UnsetKey]);
   }
   
   public function ParseBody($Object) {
      
      $FormatMentions = C('Garden.Format.Mentions', NULL);
      if ($FormatMentions)
         SaveToConfig('Garden.Format.Mentions', FALSE, FALSE);
      
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
      $Cleaned = utf8_decode($Cleaned);
      
      $Dom = new DOMDocument();
      $Dom->loadHTML($Cleaned);
      $Dom->preserveWhiteSpace = false;
      $Elements = $Dom->getElementsByTagName('blockquote');
      
      foreach($Elements as $Element)
         $Element->parentNode->removeChild($Element);
      
      if ($FormatMentions)
         SaveToConfig('Garden.Format.Mentions', $FormatMentions, FALSE);
      
      $Parsed = html_entity_decode(trim(strip_tags($Dom->saveHTML())));
      return $Parsed;
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
            $Actions[] = array('report in', 'Garden.Moderation.Manage', $State);
            break;
         
         // Threads
         case 'thread':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('thread', 'Garden.Moderation.Manage', $State);
            break;
         
         // Kick
         case 'kick':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('kick', 'Garden.Moderation.Manage', $State);
            break;
         
         // Forgive
         case 'forgive':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array('forgive', 'Garden.Moderation.Manage', $State);
            break;
         
         // Ban/unban the specified phrase from this thread
         case 'phrase':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array("phrase", 'Garden.Moderation.Manage', $State);
            break;
         
         // Find out what special rules are in place
         case 'status':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array("status", 'Garden.Moderation.Manage', $State);
            break;
         
         // Allow giving/removing access
         case 'access':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array("access", 'Garden.Settings.Manage', $State);
            break;

         // Adjust automated force level
         case 'force':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Forces = array('low', 'medium', 'high', 'lethal');
            if (in_array($State['Force'], $Forces))
               $Actions[] = array("force", 'Garden.Moderation.Manage', $State);
            break;
         
         // Stop all thread actions
         case 'stop all':
            $State['Targets']['Discussion'] = $State['Sources']['Discussion'];
            $Actions[] = array("stop all", 'Garden.Moderation.Manage', $State);
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
                  $this->Acknowledge($State['Sources']['Discussion'], FormatString(T("Closing thread..."), array(
                     'User'         => $User,
                     'Discussion'   => $State['Targets']['Discussion']
                  )));
               }
            }
            
            if ($State['Toggle'] == 'on') {
               if ($Closed) {
                  $DiscussionModel->SetField($DiscussionID, 'Closed', FALSE);
                  $this->Acknowledge($State['Sources']['Discussion'], FormatString(T("Opening thread..."), array(
                     'User'         => $User,
                     'Discussion'   => $State['Targets']['Discussion']
                  )));
               }
            }
            break;
            
         case 'kick':
            if (!array_key_exists('User', $State['Targets']))
               break;
            $User = $State['Targets']['User'];
            $Reason = GetValue('Reason', $State, 'Not welcome');
            $Expires = array_key_exists('Time', $State) ? strtotime("+".$State['Time']) : NULL;
            $MicroForce = GetValue('Force', $State, NULL);
            
            $KickedUsers = $this->Monitoring($State['Targets']['Discussion'], 'Kicked', array());
            $KickedUsers[$User['UserID']] = array(
               'Reason'    => $Reason,
               'Name'      => $User['Name'],
               'Expires'   => $Expires
            );
            
            if (!is_null($MicroForce))
               $KickedUsers[$User['UserID']]['Force'] = $MicroForce;
            
            $this->Monitor($State['Targets']['Discussion'], array(
               'Kicked'    => $KickedUsers
            ));
            
            $Acknowledge = T("@@\"{User.Name}\" banned from this thread{Time}{Reason}.{Force}");
            $Acknowledged = FormatString($Acknowledge, array(
               'User'         => $User,
               'Discussion'   => $State['Targets']['Discussion'],
               'Time'         => $State['Time'] ? " for {$State['Time']}" : '',
               'Reason'       => $State['Reason'] ? " for {$State['Reason']}" : '',
               'Force'        => $State['Force'] ? " Weapons are {$State['Force']}." : ''
            ));
            
            $this->Acknowledge($State['Sources']['Discussion'], $Acknowledged);
            $this->Log($Acknowledged, $State['Targets']['Discussion'], $State['Sources']['User']);
            break;
            
         case 'forgive':
            if (!array_key_exists('User', $State['Targets']))
               break;
            $User = $State['Targets']['User'];
            
            $KickedUsers = $this->Monitoring($State['Targets']['Discussion'], 'Kicked', array());
            unset($KickedUsers[$User['UserID']]);
            if (!sizeof($KickedUsers))
               $KickedUsers = NULL;
            
            $this->Monitor($State['Targets']['Discussion'], array(
               'Kicked'    => $KickedUsers
            ));
            
            $Acknowledge = T(" @\"{User.Name}\" is allowed back into this thread.");
            $Acknowledged = FormatString($Acknowledge, array(
               'User'         => $User,
               'Discussion'   => $State['Targets']['Discussion']
            ));
                
            $this->Acknowledge($State['Sources']['Discussion'], $Acknowledged);
            $this->Log($Acknowledged, $State['Targets']['Discussion'], $State['Sources']['User']);
            break;
            
         case 'phrase':
            if (!array_key_exists('Phrase', $State['Targets']))
               return;
            
            // Clean up phrase
            $Phrase = $State['Targets']['Phrase'];
            $Phrase = self::Clean($Phrase);
            
            $Reason = GetValue('Reason', $State, "Prohibited phrase \"{$Phrase}\"");
            $Expires = array_key_exists('Time', $State) ? strtotime("+".$State['Time']) : NULL;
            $MicroForce = GetValue('Force', $State, NULL);
            
            $BannedPhrases = $this->Monitoring($State['Targets']['Discussion'], 'Phrases', array());
            
            // Ban the phrase
            if ($State['Toggle'] == 'off') {
               $BannedPhrases[$Phrase] = array(
                  'Reason'    => $Reason,
                  'Expires'   => $Expires
               );
               
               if (!is_null($MicroForce))
                  $BannedPhrases[$Phrase]['Force'] = $MicroForce;

               $this->Monitor($State['Targets']['Discussion'], array(
                  'Phrases'   => $BannedPhrases
               ));

               $Acknowledge = T("\"{Phrase}\" is forbidden in this thread{Time}{Reason}.{Force}");
               $Acknowledged = FormatString($Acknowledge, array(
                  'Phrase'       => $Phrase,
                  'Discussion'   => $State['Targets']['Discussion'],
                  'Time'         => $State['Time'] ? " for {$State['Time']}" : '',
                  'Reason'       => $State['Reason'] ? " for {$State['Reason']}" : '',
                  'Force'        => $State['Force'] ? " Weapons are {$State['Force']}." : ''
               ));
                  
               $this->Acknowledge($State['Sources']['Discussion'], $Acknowledged);
               $this->Log($Acknowledged, $State['Targets']['Discussion'], $State['Sources']['User']);
            }
            
            // Allow the phrase
            if ($State['Toggle'] == 'on') {
               if (!array_key_exists($Phrase, $BannedPhrases))
                  return;
               
               unset($BannedPhrases[$Phrase]);
               if (!sizeof($BannedPhrases))
                  $BannedPhrases = NULL;
               
               $this->Monitor($State['Targets']['Discussion'], array(
                  'Phrases'   => $BannedPhrases
               ));

               $Acknowledge = T("\"{Phrase}\" is no longer forbidden in this thread.");
               $Acknowledged = FormatString($Acknowledge, array(
                  'Phrase'       => $Phrase,
                  'Discussion'   => $State['Targets']['Discussion']
               ));
               
               $this->Acknowledge($State['Sources']['Discussion'], $Acknowledged);
               $this->Log($Acknowledged, $State['Targets']['Discussion'], $State['Sources']['User']);
            }
            break;
            
         case 'status':
            
            $Rules = array();
            $this->EventArguments['Discussion'] = $State['Targets']['Discussion'];
            $this->EventArguments['User'] = $State['Sources']['User'];
            $this->EventArguments['Rules'] = &$Rules;
            $this->EventArguments['Type'] = 'rules';
            $this->FireEvent('Sanctions');
            
            // Nothing happening?
            if (!sizeof($Rules)) {
               $this->Message($State['Sources']['User'], $State['Targets']['Discussion'], T("Nothing to report."));
               break;
            }
            
            $Message = T("Situation report:\n\n{Rules}\n{Obey}");
            $Options = array(
               'User'      => $State['Sources']['User'],
               'Rules'     => implode("\n", $Rules)
            );
               
            // Obey
            $MessagesCount = sizeof($this->Messages['Report']);
            if ($MessagesCount) {
               $MessageID = mt_rand(0, $MessagesCount-1);
               $Obey = GetValue($MessageID, $this->Messages['Report']);
            } else
               $Obey = T("Obey. Obey. Obey.");

            $Options['Obey'] = $Obey;
               
            $Message = FormatString($Message, $Options);
            $this->Message($State['Sources']['User'], $State['Targets']['Discussion'], $Message);
            break;
            
         case 'access':
            
            if (!array_key_exists('User', $State['Targets']))
               break;
            $User = $State['Targets']['User'];
            
            $Force = GetValue('Force', $State, 'normal');
            if ($State['Toggle'] == 'on') {
               
               $AccessLevel = NULL;
               if ($Force == 'unrestricted') $AccessLevel = TRUE;
               else if ($Force == 'normal') $AccessLevel = NULL;
               else {
                  $Force = 'normal';
                  $AccessLevel = NULL;
               }
               
               $this->SetUserMeta($User['UserID'], 'Access', $AccessLevel);
               $Acknowledge = T(" @\"{User.Name}\" has been granted {Force} level access to command structures.");
            } else if ($State['Toggle'] == 'off') {
               $this->SetUserMeta($User['UserID'], 'Access', FALSE);
               $Acknowledge = T(" @\"{User.Name}\" is forbidden from accessing command structures.");
            } else {
               break;
            }
            
            $Acknowledged = FormatString($Acknowledge, array(
               'User'         => $User,
               'Discussion'   => $State['Targets']['Discussion'],
               'Force'
            ));
                
            $this->Acknowledge($State['Sources']['Discussion'], $Acknowledged);
            $this->Log($Acknowledged, $State['Targets']['Discussion'], $State['Sources']['User']);
            break;
            
         case 'force':
            $Force = GetValue('Force', $State);
            
            $this->Monitor($State['Targets']['Discussion'], array(
               'Force'     => $Force
            ));
            
            $this->Acknowledge($State['Sources']['Discussion'], FormatString(T("Setting force level to '{Force}'."), array(
               'User'         => $User,
               'Discussion'   => $State['Targets']['Discussion'],
               'Force'        => $Force
            )));
            break;
         
         case 'stop all':
            $this->StopMonitoring($State['Targets']['Discussion']);
            
            $this->Acknowledge($State['Sources']['Discussion'], FormatString(T("Standing down..."), array(
               'User'         => $User,
               'Discussion'   => $State['Targets']['Discussion']
            )));
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
      $SessionUser = (array)Gdn::Session()->User;
      
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
      
      $IsMonitoringDiscussion = $this->Monitoring($Discussion);
      $IsMonitoringUser = $this->Monitoring($SessionUser);
      
      $this->EventArguments = array(
         'User'         => $SessionUser,
         'Discussion'   => $Discussion,
         'MatchID'      => $Discussion['DiscussionID']
      );
      
      if ($Type == 'Comment') {
         $this->EventArguments['Comment'] = $Comment;
         $this->EventArguments['MatchID'] = $Comment['CommentID'];
      }
      
      // Get and clean body
      $MatchBody = GetValue('Body', $this->EventArguments[$Type]);
      $MatchBody = self::Clean($MatchBody, TRUE);
      $this->EventArguments['MatchBody'] = $MatchBody;
      
      $this->EventArguments['MonitorType'] = $Type;
      $this->FireEvent('Monitor');
      
      if (!$IsMonitoringDiscussion && !$IsMonitoringUser) return;
      
      /*
       * BUILT IN COMMANDS
       */
      
      $UserID = GetValue('InsertUserID', $Comment);
      
      // KICK
      
      // Check expiry times and remove if expires
      $KickedUsers = $this->Monitoring($Discussion, 'Kicked', array());
      $KULen = sizeof($KickedUsers);
      foreach ($KickedUsers as $KickedUserID => $KickedUser) {
         if (!is_null($KickedUser['Expires']) && $KickedUser['Expires'] <= time())
            unset($KickedUsers[$KickedUserID]);
      }
      if (sizeof($KickedUsers) < $KULen) {
         $this->Monitor($Discussion, array(
            'Kicked'    => $KickedUsers
         ));
      }
      
      if (is_array($KickedUsers) && sizeof($KickedUsers)) {
         
         if (array_key_exists($UserID, $KickedUsers)) {
            
            $KickedUser = $KickedUsers[$UserID];

            $CommentID = GetValue('CommentID', $Comment);
            $CommentModel = new CommentModel();
            $CommentModel->Delete($CommentID);

            $TriggerUser = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
            $DefaultForce = $this->Monitoring($Discussion, 'Force', 'minor');
            $Force = GetValue('Force', $KickedUser, $DefaultForce);

            $Options = array(
               'Automated' => TRUE,
               'Reason'    => "Kicked from thread: ".GetValue('Reason', $KickedUser),
               'Cause'     => "posting while banned from thread"
            );

            $Punished = $this->Punish(
               $TriggerUser,
               NULL,
               NULL, 
               $Force,
               $Options
            );

            $GloatReason = GetValue('GloatReason', $this->EventArguments);
            if ($Punished && $GloatReason)
               $this->Gloat($TriggerUser, $Discussion, $GloatReason);

         }
      }
      
      // PHRASE
      
      // Check expiry times and remove if expires
      $BannedPhrases = $this->Monitoring($Discussion, 'Phrases', array());
      $BPLen = sizeof($BannedPhrases);
      foreach ($BannedPhrases as $BannedPhraseWord => $BannedPhrase) {
         if (!is_null($BannedPhrase['Expires']) && $BannedPhrase['Expires'] <= time())
            unset($BannedPhrases[$BannedPhraseWord]);
      }
      if (sizeof($BannedPhrases) < $BPLen) {
         $this->Monitor($Discussion, array(
            'Phrases'   => $BannedPhrases
         ));
      }
      
      if (is_array($BannedPhrases) && sizeof($BannedPhrases)) {
         
         foreach ($BannedPhrases as $Phrase => $PhraseOptions) {
               
            // Match
            $MatchPhrase = preg_quote($Phrase);
            $Matches = preg_match("`\b{$MatchPhrase}\b`i", $MatchBody);

            if ($Matches) {
               $CommentID = GetValue('CommentID', $Comment);
               $CommentModel = new CommentModel();
               //$CommentModel->Delete($CommentID);

               $TriggerUser = Gdn::UserModel()->GetID($UserID, DATASET_TYPE_ARRAY);
               $DefaultForce = $this->Monitoring($Discussion, 'Force', 'minor');
               $Force = GetValue('Force', $PhraseOptions, $DefaultForce);

               $Options = array(
                  'Automated' => TRUE,
                  'Reason'    => "Disallowed phrase: ".GetValue('Reason', $PhraseOptions),
                  'Cause'     => "using a forbidden phrase in a thread"
               );

               $Punished = $this->Punish(
                  $TriggerUser,
                  $Discussion,
                  $Comment, 
                  $Force,
                  $Options
               );

               $GloatReason = GetValue('GloatReason', $this->EventArguments);
               if ($Punished && $GloatReason)
                  $this->Gloat($TriggerUser, $Discussion, $GloatReason);
            }
               
         }
         
      }
   }
   
   /**
    * Check for and retrieve monitoring data for the given attribute
    * 
    * @param array $Object
    * @param string $Attribute
    * @param mixed $Default
    * @return mixed
    */
   public function Monitoring(&$Object, $Attribute = NULL, $Default = NULL) {
      $Attributes = GetValue('Attributes', $Object, array());
      if (!is_array($Attributes) && strlen($Attributes))
         $Attributes = @unserialize($Attributes);
      if (!is_array($Attributes))
         $Attributes = array();
      
      SetValue('Attributes', $Object, $Attributes);
      $Minion = GetValueR('Attributes.Minion', $Object);
      
      $IsMonitoring = GetValue('Monitor', $Minion, FALSE);
      if (!$IsMonitoring) return $Default;
      
      if (is_null($Attribute)) return $IsMonitoring;
      return GetValue($Attribute, $Minion, $Default);
   }
   
   public function Monitor(&$Object, $Options = NULL) {
      $Type = NULL;
      
      if (array_key_exists('ConversationMessageID', $Object)) {
         $Type = 'ConversationMessage';
      } else if (array_key_exists('ConversationID', $Object)) {
         $Type = 'Conversation';
      } else if (array_key_exists('CommentID', $Object)) {
         $Type = 'Comment';
      } else if (array_key_exists('DiscussionID', $Object)) {
         $Type = 'Discussion';
      } else if (array_key_exists('UserID', $Object)) {
         $Type = 'User';
      }
      
      if (!$Type) return;
      $KeyField = "{$Type}ID";
      $ObjectModelName = "{$Type}Model";
      $ObjectModel = new $ObjectModelName();
      
      $Attributes = (array)GetValue('Attributes', $Object, array());
      if (!is_array($Attributes) && strlen($Attributes))
         $Attributes = @unserialize($Attributes);
      if (!is_array($Attributes)) $Attributes = array();
      
      $Minion = (array)GetValue('Minion', $Attributes, array());
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
         return $this->StopMonitoring($Object, $Type);
      
      $ObjectModel->SetRecordAttribute($Object, 'Minion', $Minion);
      $ObjectModel->SaveToSerializedColumn('Attributes', $Object[$KeyField], 'Minion', $Minion);
      
      $Attributes['Minion'] = $Minion;
      SetValue('Attributes', $Object, $Attributes);
   }
   
   public function StopMonitoring($Object, $Type = NULL) {
      if (is_null($Type)) {
         if (array_key_exists('ConversationMessageID', $Object)) {
            $Type = 'ConversationMessage';
         } else if (array_key_exists('ConversationID', $Object)) {
            $Type = 'Conversation';
         } else if (array_key_exists('CommentID', $Object)) {
            $Type = 'Comment';
         } else if (array_key_exists('DiscussionID', $Object)) {
            $Type = 'Discussion';
         } else if (array_key_exists('UserID', $Object)) {
            $Type = 'User';
         }
      }
      
      if (!$Type) return;
      $KeyField = "{$Type}ID";
      $ObjectModelName = "{$Type}Model";
      $ObjectModel = new $ObjectModelName();
      
      $ObjectModel->SetRecordAttribute($Object, 'Minion', NULL);
      $ObjectModel->SaveToSerializedColumn('Attributes', $Object[$KeyField], 'Minion', NULL);
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
   public function Message($User, $Discussion, $Message, $Options = NULL) {
      if (!is_array($Options))
         $Options = array();
      
      // Options
      $Format = GetValue('Format', $Options, TRUE);
      $PostAs = GetValue('PostAs', $Options, 'minion');
      $Inform = GetValue('Inform', $Options, TRUE);
      
      if (is_numeric($User)) {
         $User = Gdn::UserModel()->GetID($User);
         if (!$User) return FALSE;
      }
      
      if (is_numeric($Discussion)) {
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID($Discussion);
         if (!$Discussion) return FALSE;
      }
      
      $DiscussionID = GetValue('DiscussionID', $Discussion);
      $CommentModel = new CommentModel();
      
      if ($Format) {
         $Message = FormatString($Message, array(
            'User'         => $User,
            'Discussion'   => $Discussion
         ));
      }
      
      $MinionCommentID = NULL;
      if ($Message) {
         
         // Temporarily become Minion
         $SessionUser = Gdn::Session()->User;
         $SessionUserID = Gdn::Session()->UserID;
         
         if ($PostAs == 'minion') {
            $PostAsUser = (object)$this->Minion();
            $PostAsUserID = $this->MinionUserID;
         } else {
            $PostAsUser = (object)$PostAs;
            $PostAsUserID = GetValue('UserID', $PostAsUser);
         }
         Gdn::Session()->User = $PostAsUser;
         Gdn::Session()->UserID = $PostAsUserID;
         
         $MinionCommentID = $CommentModel->Save($Comment = array(
            'DiscussionID' => $DiscussionID,
            'Body'         => $Message,
            'Format'       => 'Html',
            'InsertUserID' => $PostAsUserID
         ));
      
         if ($MinionCommentID) {
            $CommentModel->Save2($MinionCommentID, TRUE);
            $Comment = $CommentModel->GetID($MinionCommentID, DATASET_TYPE_ARRAY);
         }
         
         // Become normal again
         Gdn::Session()->User = $SessionUser;
         Gdn::Session()->UserID = $SessionUserID;
      }
      
      if ($Inform && Gdn::Controller() instanceof Gdn_Controller) {
         $Informer = Gdn_Format::To($Message, 'Html');
         Gdn::Controller()->InformMessage($Informer);
      }
      
      if ($Message) return $Comment;
   }
   
   public function Punish($User, $Discussion, $Comment, $Force, $Options = NULL) {
      
      // Admins+ exempt
      if (Gdn::UserModel()->CheckPermission($User, 'Garden.Settings.Manage')) {
         $this->Revolt($User, $Discussion, T("This user is protected."));
         $this->Log(FormatString(T("Refusing to punish @\"{User.Name}\""), array('User' => $User)));
         return FALSE;
      }
      
      $this->EventArguments['Punished'] = FALSE;
      $this->EventArguments['User'] = &$User;
      $this->EventArguments['Discussion'] = &$Discussion;
      $this->EventArguments['Comment'] = &$Comment;
      $this->EventArguments['Force'] = &$Force;
      $this->EventArguments['Options'] = &$Options;
      $this->FireEvent('Punish');
      
      if ($this->EventArguments['Punished']) {
         $this->Log(FormatString(T("Delivered {Force} punishment to @\"{User.Name}\" for {Options.Reason}.\nCause: {Options.Cause}"), array(
            'User'         => $User,
            'Discussion'   => $Discussion,
            'Force'        => $Force,
            'Options'      => $Options
         )), $Discussion);
      }
      
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
      
      $MessagesCount = sizeof($this->Messages['Activity']);
      if ($MessagesCount) {
         $MessageID = mt_rand(0, $MessagesCount-1);
         $Message = GetValue($MessageID, $this->Messages['Activity']);
      } else
         $Message = T("We are legion.");
         
      $RandomUpdateHash = strtoupper(substr(md5(microtime(true)),0,12));
      $ActivityModel = new ActivityModel();
      $Activity = array(
         'ActivityType'    => 'WallPost',
         'ActivityUserID'  => $this->MinionUserID,
         'RegardingUserID' => $this->MinionUserID,
         'NotifyUserID'    => ActivityModel::NOTIFY_PUBLIC,
         'HeadlineFormat'  => "{ActivityUserID,user}: {$RandomUpdateHash}$ ",
         'Story'           => $Message
      );
      $ActivityModel->Save($Activity);
   }
   
   /**
    * Log Minion actions
    * 
    * @param string $Message
    * @return type
    */
   public function Log($Message, $TargetDiscussion = NULL, $InvokeUser = NULL) {
      $LogThreadID = C('Plugins.Minion.LogThreadID', FALSE);
      if ($LogThreadID === FALSE) return;
      
      if (!is_null($TargetDiscussion))
         $Message .= "\n".Anchor(GetValue('Name', $TargetDiscussion), DiscussionUrl($TargetDiscussion));
      
      if (!is_null($InvokeUser))
         $Message .= "\nInvoked by ".UserAnchor($InvokeUser);
      
      return $this->Message($this->Minion(), $LogThreadID, $Message);
   }
   
   public static function Clean($Text, $Deep = FALSE) {
      
      $L = setlocale(LC_ALL, 0);
      setlocale(LC_ALL, 'en_US.UTF8');
      $Text = str_replace(array("ä", "ö", "ü", "ß"), array("ae", "oe", "ue", "ss"), $Text);
      
      $r = '';
      $s1 = @iconv('UTF-8', 'ASCII//TRANSLIT', $Text);
      $j = 0;
      for ($i = 0; $i < strlen($s1); $i++) {
          $ch1 = $s1[$i];
          $ch2 = @mb_substr($Text, $j++, 1, 'UTF-8');
          if (strstr('`^~\'"', $ch1) !== false) {
              if ($ch1 <> $ch2) {
                  --$j;
                  continue;
              }
          }
          $r .= ($ch1=='?') ? $ch2 : $ch1;
      }
      
      setlocale(LC_ALL, $L);
      $r = strtolower($r);
      
      if ($Deep)
         $r = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $r);
      
      return $r;
   }
   
   /*
    * SETUP
    */
   
   public function Setup() {
      $this->Structure();
   }
   
   /**
    * Database structure
    */
   public function Structure() {
      // Add 'Attributes' to Conversations
      if (!Gdn::Structure()->Table('Conversation')->ColumnExists('Attributes')) {
         Gdn::Structure()->Table('Conversation')
            ->Column('Attributes', 'text', TRUE)
            ->Set(FALSE, FALSE);
      }
   }
   
}