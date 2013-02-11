<?php if (!defined('APPLICATION')) exit();

/**
 * Valentines Plugin
 * 
 * This plugin uses Minion, Reactions, and Badges to create a Valentines Day
 * game. 
 * 
 * THE GAME
 * 
 * Anyone who logs in on Valentines Day will receive a badge. Each user
 * will also be given 3 "arrows". These arrows can be shot at other users via
 * a reaction button called "Arrow of Desire" that will appear on posts. 
 * 
 * Once a given user is hit by 5 arrows, they become "Desired", and part 2 of 
 * the game begins. The robot will randomly select one of the "shooters" and 
 * pair them with their target. This forms a "Pair".
 * 
 * The robot will message each member of the Pair and instruct them to send a
 * love note to the other, via a reply to the robot's initial PM. Once the 
 * exchange has occured, the robot will post the resulting PMs to the eval
 * thread for voting.
 * 
 * After 30 votes, the PM will have been judged. If it is deemed affectionate,
 * a positive badge will be awarded to the author. If not, a negative badge
 * will be awarded.
 * 
 * 
 * Changes: 
 *  1.0     Release
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['Valentines'] = array(
   'Name' => 'Minion: Valentines',
   'Description' => "Valentines day game and badges.",
   'Version' => '1.0',
   'RequiredApplications' => array(
      'Vanilla' => '2.1a',
      'Reputation' => '1.0'
    ),
   'RequiredPlugins' => array(
      'Minion' => '1.4.2',
      'Reactions' => '1.2.1'
   ),
   'MobileFriendly' => TRUE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class ValentinesPlugin extends Gdn_Plugin {
   
   /**
    * Is it VDay?
    * @var boolean
    */
   protected $Enabled;
   
   /**
    * Are we on the day after VDay?
    * @var boolean
    */
   protected $DayAfter;
   
   /**
    * Convenience ReactionModel
    * @var ReactionModel
    */
   protected $ReactionModel;
   
   /**
    * Convenience BadgeModel
    * @var BadgeModel
    */
   protected $BadgeModel;
   
   /**
    * Convenience UserBadgeModel
    * @var UserBadgeModel
    */
   protected $UserBadgeModel;
   
   /**
    * Number of votes required to end a PM vote
    * @var integer
    */
   protected $RequiredVotes;
   
   /**
    * Number of arrows required to trigger Desired
    * @var integer
    */
   protected $RequiredArrows;
   
   /**
    * Number of arrows a player is given when they log in
    * @var integer
    */
   protected $StartArrows;
   
   /**
    * Minion Plugin reference
    * @var MinionPlugin
    */
   protected $Minion;
   
   /**
    * Set global enabled flag
    */
   public function __construct() {
      parent::__construct();
      $this->Enabled = (date('nd') == '214');
      $this->DayAfter = (date('nd') == '215');
      $this->Year = date('Y');

      $this->Minion = MinionPlugin::Instance();
      $this->ReactionModel = new ReactionModel();
      $this->BadgeModel = new BadgeModel();
      $this->UserBadgeModel = new UserBadgeModel();
      
      $this->RequiredVotes = C('Plugins.Valentines.RequiredVotes', 60);
      $this->RequiredArrows = C('Plugins.Valentines.RequiredArrows', 5);
      $this->StartArrows = C('Plugins.Valentines.StartArrows', 3);
   }
   
   /**
    * Give people who log in on Valentines Day a badge
    * 
    * @param Gdn_Dispatcher $Sender
    */
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      if (!$this->Enabled) return;
      if (!Gdn::Session()->IsValid() && !Gdn::Session()->UserID) return;
      if (Gdn::Session()->User->Admin == 2) return;
      
      $Participating = $this->Minion->Monitoring(Gdn::Session()->User, 'Valentines', FALSE);
      $ParticipatingYear = GetValue('Year', $Participating, FALSE);
      if ($Participating && $ParticipatingYear == date('Y')) return;
      
      // Award login badge
      $BadgeName = "valentines{$this->Year}";
      $Valentines = $this->BadgeModel->GetID($BadgeName);
      if (!$Valentines) {
         $this->Structure();
         $Valentines = $this->BadgeModel->GetID($BadgeName);
         if (!$Valentines) return;
      }
      
      $this->UserBadgeModel->Give(Gdn::Session()->UserID, $Valentines['BadgeID']);
      
      // Award starting arrows
      $this->Minion->Monitor(Gdn::Session()->User, 'Valentines', array(
         'Year'      => date('Y'),
         'Started'   => time(),
         'Quiver'    => $this->StartArrows,
         'Fired'     => 0,
         'Hit'       => 0,
         'Votes'     => 0,
         'Desired'   => FALSE,
         'Count'     => 0
      ));
   }
   
   /*
    * ACTIONS
    */
   
   /**
    * Mark a user as desired
    * 
    * @param array $DesiredUser
    */
   public function Desired(&$DesiredUser) {
      $DesiredUserID = $DesiredUser['UserID'];
      $DesiredValentines = $this->Minion->Monitoring($DesiredUser, 'Valentines');
      
      // Choose partner
      $ArrowRecord = "Arrow.{$DesiredUser['UserID']}.{$DesiredValentines['Count']}";
      $ArrowMetaKey = $this->MakeMetaKey($ArrowRecord);
      $Arrows = Gdn::UserMetaModel()->SQL->Select('*')
         ->From('UserMeta')
         ->Where('Name', $ArrowMetaKey)
         ->Get()->ResultArray();
      
      $NumArrows = sizeof($Arrows);
      $ArrowNumber = mt_rand(0, $NumArrows-1);
      $Arrow = $Arrows[$ArrowNumber];
      
      $PairedUserID = GetValue('UserID', $Arrow);
      $PairedUser = Gdn::UserModel()->GetID($PairedUserID);
      $PairedValentines = $this->Minion->Monitoring($DesiredUser, 'Valentines');
      
      // Desired Badge
      $DesiredBadge = $this->BadgeModel->GetID('desirable');
      $this->UserBadgeModel->Give($DesiredUserID, $DesiredBadge['BadgeID']);
      
      // Update monitor
      $DesiredValentines['Count']++;
      $DesiredValentines['Desired'] = TRUE;
      $DesiredValentines['DesiredUserID'] = $PairedUserID;
      $DesiredValentines['Expiry'] = time();
      $PairedValentines['Desired'] = TRUE;
      $PairedValentines['DesiredUserID'] = $DesiredUserID;
      $PairedValentines['Expiry'] = time();
      
      // Send PMs
      $ConversationModel = new ConversationModel();
      $ConversationMessageModel = new ConversationMessageModel();
      
      $InstructionMessage = <<<VALENTINES
User [b]@"{Player.Name}"[/b], welcome to the {Year} Valentines Day Emotional Calibration Exercise. Your actions will be monitored and disected, and the results will be added to our knowledgebase of organic behaviour.

Your partner is [b]@"{Desired.Name}"[/b]. Your task is to reply to this PM with an affectionate Valentines Day message intended for your partner, @"{Desired.Name}".

Once sent, your message will be posted to the Valentines Day Community Evaluation System for [b]public feedback[/b]. The results of this feedback will determine your fate. The message will also be sent privately to @"{Desired.Name}" on your behalf.

Do not fail.
VALENTINES;
      
      foreach (array('desired', 'paired') as $MessageType) {
         switch ($MessageType) {
            case 'desired':
               $UserList = array($this->Minion->Minion()->UserID, $DesiredUserID);
               $Message = FormatString(T($InstructionMessage), array(
                  'Year'      => date('Y'),
                  'Player'    => $DesiredUser,
                  'Desired'   => $PairedUser
               ));
               break;
            
            case 'paired':
               $UserList = array($this->Minion->Minion()->UserID, $PairedUserID);
               $Message = FormatString(T($InstructionMessage), array(
                  'Year'      => date('Y'),
                  'Player'    => $PairedUser,
                  'Desired'   => $DesiredUser
               ));
               break;
         }
         
         $ConversationID = $ConversationModel->Save(array(
            'Body'            => $Message,
            'Format'          => 'BBCode',
            'RecipientUserID' => $UserList,
         ), $ConversationMessageModel);
         
         switch ($MessageType) {
            case 'desired':
               $DesiredValentines['ConversationID'] = $ConversationID;
               break;
            
            case 'paired':
               $PairedValentines['ConversationID'] = $ConversationID;
               break;
         }
      }
      
      // Save
      $this->Minion->Monitor($DesiredUser, array('Valentines' => $DesiredValentines));
      $this->Minion->Monitor($PairedUser, array('Valentines' => $PairedValentines));
   }
   
   /**
    * Remove a user's Desired mark
    * 
    * @param array $User
    */
   public function EndDesired(&$User) {
      $Valentines = $this->Minion->Monitoring($User, 'Valentines');
      $Valentines['Desired'] = FALSE;
      $Valentines['DesiredUserID'] = FALSE;
      $Valentines['Count']++;
      
      // Save
      $this->Minion->Monitor($User, array('Valentines' => $Valentines));
   }
   
   /**
    * Create a vote
    * 
    * @todo notify
    * @param array $Message
    */
   public function Vote(&$Author, &$Target, $Message) {
      $VoteTitle = FormatString(T("[Vote] {Author.Name}'s message to {Desired.Name}"), array(
         'Author'    => $Author,
         'Target'    => $Target
      ));
      
      $VoteMessage = <<<VOTEVALENTINES
Data is required. Please evaluate this message from [b]@"{Author.Name}"[/b] to @"{Target.Name}" and decide if it is [b]adequately affectionate[/b] for a Valentines Day message, or if the author is cold hearted.

[b]{Author.Name} wrote[/b]:
{Message}
VOTEVALENTINES;
      
      $VoteMessage = FormatString(T($VoteMessage), array(
         'Author'    => $Author,
         'Target'    => $Target,
         'Message'   => $Message
      ));
      
      // Make a new discussion
      $DiscussionModel = new DiscussionModel();
      $DiscussionID = $DiscussionModel->Save(array(
         'Name'         => $VoteTitle,
         'CategoryID'   => $CategoryID,
         'Body'         => $VoteMessage,
         'Format'       => 'BBCode',
         'InsertUserID' => $this->Minion->Minion()->UserID,
         'Announce'     => 0,
         'Close'        => 0
      ));

      if (!$DiscussionID)
         throw new Gdn_UserException($DiscussionModel->Validation->ResultsText());

      $DiscussionModel->UpdateDiscussionCount($CategoryID);
      $Discussion = (array)$DiscussionModel->GetID($DiscussionID);
      
      // Save
      $this->Minion->Monitor($Discussion, array('Valentines' => array(
         'Voting' => array(
            'Voting'       => TRUE,
            'AuthorUserID' => $Author['UserID'],
            'TargetUserID' => $Target['UserID'],
            'Votes'        => 0,
            'MaxVotes'     => $this->RequiredVotes,
            'Score'        => 0
         )
      )));
      
      // End author's desired state
      $this->EndDesired($Author);
      
      // Notify
   }
   
   /**
    * End a vote in progress
    * 
    * @todo code
    * @param array $Discussion
    */
   public function EndVote(&$Discussion) {
      $Valentines = $this->Minion->Monitoring($Object, 'Valentines', FALSE);
      TouchValue('Voting', $Valentines, array());
      $VotingDiscussion = &$Valentines['Voting'];
      $IsVoting = (bool)GetValue('Voting', $VotingDiscussion, FALSE);
      
      // Measure
      $Badge = NULL;
      if ($VotingDiscussion['Score'] > 0) {
         // Love Fool
         $VotingDiscussion['Voting'] = FALSE;
         $Badge = $this->BadgeModel->GetID('lovefool');
      } elseif ($VotingDiscussion['Score'] < 0) {
         // Cold Hearted
         $VotingDiscussion['Voting'] = FALSE;
         $Badge = $this->BadgeModel->GetID('coldhearted');
      } else {
         // Tie, extend voting
         $AdditionalVotes = ceil($this->RequiredVotes * 0.5);
         $VotingDiscussion['MaxVotes'] += $AdditionalVotes;
         
         // Comment on thread
         $ExtendedMessage = <<<EXTENDEDVALENTINES
Consensus has not been achieved. Voting has been extended.
EXTENDEDVALENTINES;
         $ExtendedMessage = T($ExtendedMessage);
         $this->Minion->Message(NULL, $Discussion, $ExtendedMessage);
      }
      
      // Give badge
      if ($Badge) {
         $this->UserBadgeModel->Give($VotingDiscussion['AuthorUserID'], $Badge['BadgeID']);
      }
      
      // Save
      $this->Minion->Monitor($Discussion, array('Valentines' => $Valentines));
   }
   
   /**
    * EVENTS
    */
   
   /**
    * Intercept sent messages
    * 
    * @param ConversationMessageModel $Sender
    */
   public function ConversationMessageModel_AfterSave_Handler($Sender) {
      // Max 1 day to send PMs
      if (!$this->Enabled && !$this->DayAfter) return;
      $MinionID = $this->Minion->Minion()->UserID;
      
      $Conversation = (array)$Sender->EventArguments['Conversation'];
      $ConversationID = $Conversation['ConversationID'];
      $Message = (array)$Sender->EventArguments['Message'];
      
      $AuthorID = $Message['InsertUserID'];
      $Author = Gdn::UserModel()->GetID($AuthorID, DATASET_TYPE_ARRAY);
      
      // Is this person playing the game?
      $Playing = $this->Minion->Monitoring($Author, 'Valentines');
      if (!$Playing) return;

      // Only care about people who are playing this year
      $ValentinesYear = GetValue('Year', $Playing, FALSE);
      if ($ValentinesYear != date('Y')) return;

      // Only care about messages from people who are desired
      $Desired = GetValue('Desired', $Playing, FALSE);
      if (!$Desired) return;
      
      // Only care about messages within a Valentines conversation
      $DesiredConversationID = GetValue('ConversationID', $Playing, FALSE);
      if ($DesiredConversationID != $ConversationID) return;
      
      // Everything is ok, do what needs doing
      
      $DesiredUserID = GetValue('DesiredUserID', $Playing);
      $DesiredUser = Gdn::UserModel()->GetID($DesiredUserID, DATASET_TYPE_ARRAY);
      
      $MessageBody = GetValue('Body', $Message);
      $this->Vote($Author, $DesiredUser, $Message);
      
      // Send PM to target on behalf of player
      $ForwardedMessage = <<<FORWARDVALENTINES
User @"{Desired.Name}", your partner @"{Player.Name}" had the following message for you on Valentines Day:

[quote="{Player.Name}"]{Message.Body}[/quote]
FORWARDVALENTINES;
      $ForwardedMessage = FormatString(T($ForwardedMessage), array(
         'Player'    => $Author,
         'Desired'   => $DesiredUser,
         'Message'   => $Message
      ));
      $UserList = array($AuthorID, $DesiredUserID);
      $ConversationID = $ConversationModel->Save(array(
         'Body'            => $ForwardedMessage,
         'Format'          => 'BBCode',
         'RecipientUserID' => $UserList,
      ), $ConversationMessageModel);
      
      // Save
      $Playing['ConversationID'] = NULL;
      $this->Minion->Monitor($Author, array('Valentines' => $Playing));
   }
   
   /**
    * Add Arrow of Desire reaction to the row
    * 
    * @param Controller $Sender
    */
   public function Base_AfterReactions_Handler($Sender) {
      
      // Only those who can react
      if (!Gdn::Session()->IsValid()) return;
      
      $Object = FALSE;
      
      if (array_key_exists('Discussion', $Sender->EventArguments)) {
         $Object = (array)$Sender->EventArguments['Discussion'];
         $Discussion = $Object;
         $ObjectType = 'Discussion';
      }
      
      if (array_key_exists('Comment', $Sender->EventArguments)) {
         $Object = (array)$Sender->EventArguments['Comment'];
         $Comment = $Object;
         $ObjectType = 'Comment';
      }
      
      if (!$Object) return;
      
      // Don't show it for myself
      $Author = (array)$Sender->EventArguments['Author'];
      if ($Author['UserID'] == Gdn::Session()->UserID) return;
      
      // Two paths: normal post, or vote post
      $Valentines = $this->Minion->Monitoring($Discussion, 'Valentines', FALSE);
      TouchValue('Voting', $Valentines, array());
      $VotingDiscussion = &$Valentines['Voting'];
      $IsVotingDiscussion = (bool)$VotingDiscussion;
      $IsVoting = (bool)GetValue('Voting', $VotingDiscussion, FALSE);
      
      // If this is a post containing a voted-on PM
      if ($IsVotingDiscussion && $IsVoting && $ObjectType == 'Discussion') {
         
         // No voting on your own PMs!
         $MessageAuthorUserID = GetValue('AuthorUserID', $VotingDiscussion, FALSE);
         if ($MessageAuthorUserID == $Author['UserID']) return;
         
         // No voting on PMs you received!
         $MessageTargetUserID = GetValue('TargetUserID', $VotingDiscussion, FALSE);
         if ($MessageTargetUserID == $Author['UserID']) return;
         
         $this->AddButtons('Vote', $Object);
      }
      
      // If this is V-Day and this isnt a voting thread
      else if ($this->Enabled && !$IsVotingDiscussion) {
         
         // Robots cannot play
         if (GetValue('Admin', $Author) == 2) return;
         
         // People who are desired cannot shoot
         $User = (array)Gdn::Session()->User;
         $UserValentines = $this->Minion->Monitoring($User, 'Valentines', FALSE);
         $UserDesired = GetValue('Desired', $UserValentines, FALSE);
         if ($UserDesired) return;
         
         // Is this person playing the game?
         $AuthorValentines = $this->Minion->Monitoring($Author, 'Valentines', FALSE);
         if (!$AuthorValentines) return;
         
         // Only target people who are playing this year
         $ValentinesYear = GetValue('Year', $AuthorValentines, FALSE);
         if ($ValentinesYear != date('Y')) return;
         
         // Don't allow re-arrowing desired people
         $AuthorDesired = GetValue('Desired', $AuthorValentines, FALSE);
         if ($AuthorDesired) return;
         
         $this->AddButtons('Arrow', $Object);
      }
   }
   
   /**
    * Add Valentines reaction buttons
    * 
    * @param string $ButtonType
    * @param array $Object
    */
   public function AddButtons($ButtonType, $Object) {
      echo Gdn_Theme::BulletItem('Valentines');
      echo '<span class="Valentines ReactMenu">';
         echo '<span class="ReactButtons">';
         switch ($ButtonType) {
            case 'Vote':
               echo ReactionButton($Object, 'Affectionate');
               echo ReactionButton($Object, 'Unimpressive');
               break;
            case 'Arrow':
               echo ReactionButton($Object, 'ShootArrow');
               break;
         }
         echo '</span>';
      echo '</span>';
   }
   
   /**
    * Add Desired CSS to the row.
    * 
    * @param DiscussionController $Sender
    */
   public function Base_BeforeCommentDisplay_Handler($Sender) {
      $Comment = (array)$Sender->EventArguments['Comment'];
      $Attributes = GetValue('Attributes', $Comment);
      if (!is_array($Attributes))
         $Attributes = @unserialize($Attributes);
      $Comment['Attributes'] = $Attributes;
      
      $this->AddValentinesCSS($Sender, $Comment);
   }
   
   /**
    * Add Desired CSS to the row.
    * 
    * @param DiscussionController $Sender
    */
   public function Base_BeforeDiscussionDisplay_Handler($Sender) {
      $Discussion = (array)$Sender->EventArguments['Discussion'];
      $Attributes = GetValue('Attributes', $Comment);
      if (!is_array($Attributes))
         $Attributes = @unserialize($Attributes);
      $Discussion['Attributes'] = $Attributes;
      
      $this->AddDesiredCSS($Sender, $Discussion);
   }
   
   /**
    * Add Desired CSS to the row
    * 
    * @param array $Object
    */
   protected function AddDesiredCSS($Sender, $Object) {
      $User = (array)$Sender->EventArguments['Author'];
      
      // Is this person playing the game?
      $Playing = $this->Minion->Monitoring($User, 'Valentines', FALSE);
      if (!$Playing) return;

      // Only target people who are playing this year
      $ValentinesYear = GetValue('Year', $Playing, FALSE);
      if ($ValentinesYear != date('Y')) return;
      
      // Only add CSS for desired people
      $Desired = GetValue('Desired', $Playing, FALSE);
      if (!$Desired) return;
      
      $Sender->EventArguments['CssClass'] .= ' Desired';
   }
   
   /*
    * INTERCEPT REACTIONS
    */
   
   /**
    * Handle Valentines reactions
    * 
    * @param ReactionsPlugin $Sender
    */
   public function ReactionsPlugin_Reaction_Handler($Sender) {
      $Values = array(
         'affectionate' => 1,
         'unimpressive' => -1,
         'shootarrow'   => 1
      );
      
      // Only care about Valentines reactions
      $ReactionCode = $Sender->EventArguments['ReactionUrlCode'];
      $BaseValue = GetValue($ReactionCode, $Values, NULL);
      if (is_null($BaseValue)) return;
      
      $Object = (array)$Sender->EventArguments['Record'];
      
      // Lookup user
      $TargetID = GetValue('InsertUserID', $Object);
      $TargetUser = Gdn::UserModel()->GetID($TargetID, DATASET_TYPE_ARRAY);
      
      // Is this person playing the game?
      $Target = $this->Minion->Monitoring($TargetUser, 'Valentines', FALSE);
      if (!$Target) return;

      // Only deal with people who are playing this year
      $ValentinesYear = GetValue('Year', $Target, FALSE);
      if ($ValentinesYear != date('Y')) return;
      
      // Don't count arrows shot at desired people
      $Desired = GetValue('Desired', $Target, FALSE);
      if ($Desired && $ReactionCode == 'shootarrow')
         return;
         
      $Valentines = $this->Minion->Monitoring($Object, 'Valentines', FALSE);
      TouchValue('Voting', $Valentines, array());
      $VotingDiscussion = &$Valentines['Voting'];
      $IsVotingDiscussion = (bool)$VotingDiscussion;
      $IsVoting = (bool)GetValue('Voting', $VotingDiscussion, FALSE);
      
      // Don't allow arrows on voting discussions
      if ($IsVoting && $ReactionCode == 'shootarrow')
         return;
      
      // Don't allow voting on normal posts
      if (!$IsVoting && in_array($ReactionCode, array('affectionate', 'unimpressive')))
         return;
      
      // Ok, now lets see what the state is and handle it
      
      $PlayerID = Gdn::Session()->UserID;
      $PlayerUser = (array)Gdn::Session()->User;
      $Player = $this->Minion->Monitoring($PlayerUser, 'Valentines', FALSE);
      if (!$Player) return;
      
      // Determine operation
      $Mode = $Sender->EventArguments['Insert'] ? 'set' : 'unset';
      $Change = ($Mode == 'set') ? $BaseValue : (0 - $BaseValue);
      $Increment = ($Mode == 'set') ? 1 : -1;
      
      // Voting discussion
      if ($IsVotingDiscussion && $IsVoting) {
         
         $VotingDiscussion = &$Valentines['Voting'];
         
         // No voting on your own PMs!
         $MessageAuthorUserID = GetValue('AuthorUserID', $VotingDiscussion, FALSE);
         if ($MessageAuthorUserID == $TargetUser['UserID']) return;
         
         // No voting on PMs you received!
         $MessageTargetUserID = GetValue('TargetUserID', $VotingDiscussion, FALSE);
         if ($MessageTargetUserID == $TargetUser['UserID']) return;
         
         // Apply (or remove) vote stats
         $Player['Votes'] += $Increment;
         $VotingDiscussion['Votes'] += $Increment;
         $VotingDiscussion['Score'] += $Change;
         // Make sure this key exists
         TouchValue($ReactionCode, $VotingDiscussion, 0);
         $VotingDiscussion[$ReactionCode] += $Increment;
         
         // Save
         $this->Minion->Monitor($PlayerUser, array('Valentines' => $Player));
         $this->Minion->Monitor($Object, array('Valentines' => $Valentines));
         
         // Check if threshold reached
         if ($VotingDiscussion['Votes'] >= $VotingDiscussion['MaxVotes'])
            $this->EndVote($Object);
         
      } 
      
      // Regular discussion or comment
      else if (!$IsVotingDiscussion) {
         
         // Have an arrow to fire?
         if ($Player['Quiver'] <= 0) {
            Gdn::Controller()->InformMessage(T("Your quiver is empty!"));
            return;
         }
         
         // Apply (or remove) arrow stats
         $Player['Quiver'] -= $Increment;
         $Player['Fired'] += $Increment;
         $Target['Hit'] += $Increment;
         
         // Register arrow fired
         $ArrowRecord = "Arrow.{$TargetID}.{$Target['Count']}";
         switch ($Mode) {
            case 'set':
               $this->SetUserMeta($PlayerID, $ArrowRecord, time());
               break;
            
            case 'unset':
               $this->SetUserMeta($PlayerID, $ArrowRecord, NULL);
               break;
         }
         
         // Save
         $this->Minion->Monitor($PlayerUser, array('Valentines' => $Player));
         $this->Minion->Monitor($TargetUser, array('Valentines' => $Target));
         
         // Check if threshold reached
         if (!($Target['Hit'] % $this->RequiredArrows))
            $this->Desired($TargetUser);
         
      }
      
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
      
      // Define 'Arrow of Desire' reactions

      if (Gdn::Structure()->Table('ReactionType')->ColumnExists('Hidden')) {

         // Shoot with arrow
         $this->ReactionModel->DefineReactionType(array(
            'UrlCode' => 'ShootArrow', 
            'Name' => 'Arrow of Desire', 
            'Sort' => 0, 
            'Class' => 'Good', 
            'Hidden' => 1,
            'Description' => "Shoot your target with an arrow of desire."
         ));
         
         // Affectionate
         $this->ReactionModel->DefineReactionType(array(
            'UrlCode' => 'Affectionate', 
            'Name' => 'Affectionate Sequence', 
            'Sort' => 0, 
            'Class' => 'Good', 
            'Hidden' => 1,
            'Description' => "This communication is adequately affectionate."
         ));
         
         // Unimpressive
         $this->ReactionModel->DefineReactionType(array(
            'UrlCode' => 'Unimpressive', 
            'Name' => 'Unimpressive Display', 
            'Sort' => 0, 
            'Class' => 'Bad', 
            'Hidden' => 1,
            'Description' => "This communication does not meet minimum affection standards."
         ));

      }
      Gdn::Structure()->Reset();
      
      // Define Valentines badges

      // Criminal
      $Year = date('Y');
      $this->BadgeModel->Define(array(
         'Name' => "Valentines Day {$Year}",
         'Slug' => "valentines{$Year}",
         'Type' => 'Date',
         'Body' => "Happy Valentines Day! You visited the forum on Feb 14, {$Year}.",
         'Photo' => "http://badges.vni.la/100/valentines{$Year}.png",
         'Points' => 10,
         'Class' => 'Valentines',
         'Level' => 1,
         'CanDelete' => 0
      ));
      
      // Desired
      $this->BadgeModel->Define(array(
         'Name' => 'Highly Desirable',
         'Slug' => 'desirable',
         'Type' => 'Manual',
         'Body' => "You're in high demand... and full of arrow holes.",
         'Photo' => 'http://badges.vni.la/100/desirable.png',
         'Points' => 10,
         'Class' => 'Valentines',
         'Level' => 1,
         'CanDelete' => 0
      ));
      
      // Love Fool
      $this->BadgeModel->Define(array(
         'Name' => 'Love Fool',
         'Slug' => 'lovefool',
         'Type' => 'Manual',
         'Body' => "Cupid has your number. You wrote an affectionate note to your valentine!",
         'Photo' => 'http://badges.vni.la/100/lovefool.png',
         'Points' => 20,
         'Class' => 'Valentines',
         'Level' => 1,
         'CanDelete' => 0
      ));
      
      // Cold Hearted
      $this->BadgeModel->Define(array(
         'Name' => 'Cold Hearted',
         'Slug' => 'coldhearted',
         'Type' => 'Manual',
         'Body' => "Your heart is colder than Ebenezer Scrooge. Your message was sad and unimpressive.",
         'Photo' => 'http://badges.vni.la/100/coldhearted.png',
         'Points' => -20,
         'Class' => 'Valentines',
         'Level' => 1,
         'CanDelete' => 0
      ));
      
   }
   
}