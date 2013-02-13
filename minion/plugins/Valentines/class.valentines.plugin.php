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
      'Minion' => '1.11',
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
    * Check Expiry this round?
    * @var boolean
    */
   protected $ExpiredCheck;
   
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
    * Convenience ActivityModel
    * @var ActivityModel
    */
   protected $ActivityModel;
   
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
    * Length of time Desired users have to send their PMs (seconds)
    * @var integer
    */
   protected $DesiredExpiry;
   
   /**
    * How low does the available pool have to be before caches are deployed (decimal percent)
    * @var float
    */
   protected $RefillTriggerRatio;
   
   /**
    * How big does the pool have to be before caches are deployed (num arrows)
    * @var integer
    */
   protected $RefillThreshold;
   
   /**
    * How much of the total pool should be in each cache (decimal percent)
    * @var float 
    */
   protected $RefillCacheRatio;
   
   /**
    * Lounge CategoryID
    * @var integer
    */
   protected $LoungeID;
   
   /**
    * Minion Plugin reference
    * @var MinionPlugin
    */
   protected $Minion;
   protected $MinionUser;
   
   /**
    * Set global enabled flag
    */
   public function __construct() {
      parent::__construct();
      $this->Enabled = (date('nd') == '214');
      $this->DayAfter = (date('nd') == '215');
      $this->Enabled = TRUE;
      $this->DayAfter = FALSE;
      
      $this->Year = date('Y');
      $this->ExpiredCheck = FALSE;
      
      $this->ReactionModel = new ReactionModel();
      $this->BadgeModel = new BadgeModel();
      $this->UserBadgeModel = new UserBadgeModel();
      $this->ActivityModel = new ActivityModel();
      
      $this->RequiredVotes = C('Plugins.Valentines.RequiredVotes', 60);
      $this->RequiredArrows = C('Plugins.Valentines.RequiredArrows', 5);
      $this->StartArrows = C('Plugins.Valentines.StartArrows', 3);
      $this->DesiredExpiry = C('Plugins.Valentines.DesiredExpiry', 7200);
      $this->RefillTriggerRatio = C('Plugins.Valentines.RefillRatio', 0.4);
      $this->RefillThreshold = C('Plugins.Valentines.RefillThreshold', 51);
      $this->RefillCacheRatio = C('Plugins.Valentines.RefillThreshold', 0.05);
      $this->LoungeID = C('Plugins.Valentines.LoungeID');
   }
   
   /**
    * Hook into minion startup
    * 
    * @param MinionPlugin $Sender
    */
   public function MinionPlugin_Start_Handler($Sender) {
      
      // Register persona
      $this->Minion->Persona('Valentines', array(
         'Name'      => 'Robot Cupid',
         'Photo'     => 'http://cdn.vanillaforums.com/minion/valentines.jpg',
         'Title'     => 'Happiness Droid',
         'Location'  => 'Cloud Nine'
      ));
            
      // Change persona
      if ($this->Enabled || $this->DayAfter)
         $this->Minion->Persona('Valentines');
   }
   
   /**
    * Hook early and perform valentines actions
    * 
    * @param Gdn_Dispatcher $Sender
    * @return type
    */
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      $this->Minion = MinionPlugin::Instance();
      $this->MinionUser = (array)$this->Minion->Minion();
      
      $this->DropCache(51);
      
      // Valentines events
      if (!$this->Enabled) return;
      if (!Gdn::Session()->IsValid() || !Gdn::Session()->UserID) return;
      if (Gdn::Session()->User->Admin == 2) return;
      
      // Already participating this year?
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
      $User = (array)Gdn::Session()->User;
      $this->Minion->Monitor($User, array('Valentines' => array(
         'Year'      => date('Y'),
         'Started'   => time(),
         'Quiver'    => $this->StartArrows,
         'Fired'     => 0,
         'Hit'       => 0,
         'Votes'     => 0,
         'Desired'   => FALSE,
         'Count'     => 0
      )));
      
      // Track arrow counts
      $this->ArrowPool($this->StartArrows);
      
      // Notify
      $MinionUserID = $this->MinionUser['UserID'];
      $Activity = array(
         'ActivityUserID' => $MinionUserID,
         'NotifyUserID' => Gdn::Session()->UserID,
         'HeadlineFormat' => T("{ActivityUserID,user} has placed {Data.StartArrows} arrows in your quiver."),
         'RecordType' => 'Conversation',
         'RecordID' => 3751,
         'Route' => Url('/'),
         'Data' => array(
             'StartArrows'    => $this->StartArrows,
             'Minion'         => $this->MinionUser
          )
      );
      $this->Activity($Activity);
   }
   
   /*
    * METHODS
    */
   
   public function PluginController_Valentines_Create($Sender) {
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /**
    * Handle timer dismissal
    * 
    * @param PluginController $Sender
    */
   public function Controller_Dismiss($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      $User = (array)Gdn::Session()->User;
      $UserValentines = $this->Minion->Monitoring($User, 'Valentines', FALSE);
      $UserDesired = GetValue('Desired', $UserValentines, FALSE);
      if ($UserDesired) {
         $UserDesired = &$UserValentines['Desired'];
         $UserDesired['Dismissed'] = TRUE;
         $this->Minion->Monitor($User, array('Valentines' => $UserValentines));
      }
      
      $Sender->Render();
   }
   
   /**
    * Get arrows from a fallen cupid
    * 
    * @param PluginController $Sender
    */
   public function Controller_Cache($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_BOOL);
      
      // Must be logged in
      if (!Gdn::Session()->IsValid())
         throw PermissionException();
      
      $CacheID = GetValue(1, $Sender->RequestArgs);
      $CacheKey = "Cache.{$CacheID}";
      $Cache = $this->GetUserMeta($this->MinionUser['UserID'], $CacheKey, 0, TRUE);
      try {
         if (!$Cache || $Cache < 1)
            throw new Exception(T('This arrow cache is empty.'));
         else {
            $CacheLootedKey = "Cache.{$CacheID}.Looted";
            $CacheLooted = $this->GetUserMeta(Gdn::Session()->UserID, $CacheLootedKey, FALSE, TRUE);
            if ($CacheLooted)
               throw new Exception(T('You have already looted this arrow cache!'));
            
            // Remove arrows from Cupid's Quiver
            $Arrows = mt_rand($this->StartArrows - 1, $this->StartArrows + 1);
            if ($Cache < $Arrows) $Arrows = $Cache;
            $Cache -= $Arrows;
            if ($Cache < 0) $Cache = 0;
            $this->SetUserMeta($this->MinionUser['UserID'], $CacheKey, $Cache);
            $this->SetUserMeta(Gdn::Session()->UserID, $CacheLootedKey, TRUE);
            
            // Award arrows to player
            $Player = (array)Gdn::Session()->User;
            $Valentines = $this->Minion->Monitoring($Player, 'Valentines', array());
            $Valentines['Quiver'] += $Arrows;
            $this->ArrowPool($Arrows);
            $this->Minion->Monitor($Player, array('Valentines' => $Valentines));
            $Sender->InformMessage(FormatString(T("You found <b>{Arrows} {ArrowsStr}</b> in the fallen cherub's quiver."), array(
               'User'      => $Player,
               'Arrows'    => $Arrows,
               'ArrowsStr' => Plural($Arrows, 'arrow', 'arrows')
            )));
         }
      } catch (Exception $Ex) {
         $Sender->InformMessage($Ex->getMessage());
      }
      
      $Sender->Render();
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
      $PairedUser = NULL;
      if ($NumArrows) {
         $ArrowNumber = mt_rand(0, $NumArrows-1);
         $Arrow = $Arrows[$ArrowNumber];

         $PairedUserID = GetValue('UserID', $Arrow);
         $PairedUser = Gdn::UserModel()->GetID($PairedUserID);
         $PairedValentines = $this->Minion->Monitoring($DesiredUser, 'Valentines');
      }
      
      // Desired Badge
      $DesiredBadge = $this->BadgeModel->GetID('desirable');
      $this->UserBadgeModel->Give($DesiredUserID, $DesiredBadge['BadgeID']);
      
      // Update monitor
      $Expiry = time() + $this->DesiredExpiry;
      $DesiredValentines['Count']++;
      $DesiredValentines['Desired'] = TRUE;
      $DesiredValentines['DesiredUserID'] = $PairedUserID;
      $DesiredValentines['Expiry'] = $Expiry;
      
      $PairedValentines['Desired'] = TRUE;
      $PairedValentines['DesiredUserID'] = $DesiredUserID;
      $PairedValentines['Expiry'] = $Expiry;
      
      // Expiry reminders
      $this->SetUserMeta($DesiredUserID, 'Desired.Expiry', $Expiry);
      $this->SetUserMeta($PairedUserID, 'Desired.Expiry', $Expiry);
      
      // Send PMs
      $ConversationModel = new ConversationModel();
      $ConversationMessageModel = new ConversationMessageModel();
      
      $Timespan = $this->DesiredExpiry;
      $Timespan -= 3600 * ($Hours = (int) floor($Timespan / 3600));
      $Timespan -= 60 * ($Minutes = (int) floor($Timespan / 60));
      $Seconds = $Timespan;
      
      $TimeFormat = array();
      if ($Hours) $TimeFormat[] = "{$Hours} ".Plural($Hours, 'hour', 'hours');
      if ($Minutes) $TimeFormat[] = "{$Minutes} ".Plural($Minutes, 'minute', 'minutes');
      if ($Seconds) $TimeFormat[] = "{$Seconds} ".Plural($Seconds, 'second', 'seconds');
      $TimeFormat = implode(', ', $TimeFormat);
      
      $InstructionMessage = <<<VALENTINES
User [b]@"{Player.Name}"[/b], welcome to the {Year} Valentines Day Empathy Chip Calibration Exercise. Your actions will be monitored and disected, and the results will be added to our knowledgebase of organic behaviour.

Your partner is [b]@"{Desired.Name}"[/b]. Your task is to reply to this PM with an affectionate Valentines Day message intended for your partner, @"{Desired.Name}".

Once sent, your message will be posted to the Valentines Day Community Evaluation System for [b]public feedback[/b]. The results of this feedback will determine your fate. The message will also be sent privately to @"{Desired.Name}" on your behalf.

You have {Expiry} to complete your task. Do not fail.
VALENTINES;
      
      foreach (array('desired', 'paired') as $MessageType) {
         switch ($MessageType) {
            case 'desired':
               $UserList = array($this->Minion->Minion()->UserID, $DesiredUserID);
               $Message = FormatString(T($InstructionMessage), array(
                  'Year'      => date('Y'),
                  'Player'    => $DesiredUser,
                  'Desired'   => $PairedUser,
                  'Expiry'    => $TimeFormat
               ));
               break;
            
            case 'paired':
               $UserList = array($this->Minion->Minion()->UserID, $PairedUserID);
               $Message = FormatString(T($InstructionMessage), array(
                  'Year'      => date('Y'),
                  'Player'    => $PairedUser,
                  'Desired'   => $DesiredUser,
                  'Expiry'    => $TimeFormat
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
      
      // Notify
      $Activity = array(
         'ActivityUserID' => $PairedUserID,
         'NotifyUserID' => $DesiredUserID,
         'HeadlineFormat' => T("You've been shot by {ActivityUserID,user}! <a href=\"{Url,html}\">What now</a>?"),
         'RecordType' => 'Conversation',
         'RecordID' => $DesiredValentines['ConversationID'],
         'Route' => CombinePaths(array('messages',$DesiredValentines['ConversationID'])),
         'Data' => array(
            'Shooter'   => $PairedUser,
            'Minion'    => $this->Minion->Minion()
         )
      );
      $this->Activity($Activity);
      
      $Activity = array(
         'ActivityUserID' => $DesiredUserID,
         'NotifyUserID' => $PairedUserID,
         'HeadlineFormat' => T("You shot {ActivityUserID,user} in the neck! <a href=\"{Url,html}\">What now</a>?"),
         'RecordType' => 'Conversation',
         'RecordID' => $PairedValentines['ConversationID'],
         'Route' => CombinePaths(array('messages',$PairedValentines['ConversationID'])),
         'Data' => array(
             'Target'   => $DesiredUser,
             'Minion'   => $this->Minion->Minion()
          )
      );
      $this->Activity($Activity);
      
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
      
      // Remove expiry timer
      $this->SetUserMeta($User['UserID'], 'Desired.Expiry', NULL);
      
      // Save
      $this->Minion->Monitor($User, array('Valentines' => $Valentines));
   }
   
   
   /**
    * Expire this user's Desired and punish
    * 
    * @param array $User
    */
   public function Expire(&$User) {
      $Valentines = $this->Minion->Monitoring($User, 'Valentines');
      $DesiredUserID = $Valentines['DesiredUserID'];
      $DesiredUser = Gdn::UserModel()->GetID($DesiredUserID);
      
      // Punish
      
      // Create a shaming discussion
      $ComplianceTitle = FormatString(T("[Compliance] {User.Name} failed to contact {Desired.Name}"), array(
         'User'      => $User,
         'Desired'   => $DesiredUser
      ));
      
      $Timespan = $this->DesiredExpiry;
      $Timespan -= 3600 * ($Hours = (int) floor($Timespan / 3600));
      $Timespan -= 60 * ($Minutes = (int) floor($Timespan / 60));
      $Seconds = $Timespan;
      
      $TimeFormat = array();
      if ($Hours) $TimeFormat[] = "{$Hours} ".Plural($Hours, 'hour', 'hours');
      if ($Minutes) $TimeFormat[] = "{$Minutes} ".Plural($Minutes, 'minute', 'minutes');
      if ($Seconds) $TimeFormat[] = "{$Seconds} ".Plural($Seconds, 'second', 'seconds');
      $TimeFormat = implode(', ', $TimeFormat);
      
      $ComplianceMessage = <<<COMPLIANCEVALENTINES
Unfortunately @"{User.Name}" was unable to overcome their own organic lethargy over the course of the last
{Expiry}, and as a result has failed to send any messages to their Valentine @"{Desired.Name}".

For this, they will receive punishment consistent with the severity of their crime, and will be mocked
severely by their peers for being a complete and utter failure.

This incident has been logged in the Vault.
COMPLIANCEVALENTINES;
      $ComplianceMessage = FormatString($ComplianceMessage, array(
         'User'      => $User,
         'Desired'   => $DesiredUser,
         'Expiry'    => $TimeFormat
      ));
      $Discussion = $this->LoungeDiscussion($ComplianceTitle, $ComplianceMessage);
      $Comment = $this->Minion->Message($User, $Discussion, 'I am a tremendous goose and I feel the most profound shame.', array(
         'Format' => FALSE, 
         'PostAs' => $User
      ));

      // Now punish this comment
      $this->Minion->Punish($User, $Discussion, $Comment, 'major');
      
      // End Desired mode
      $this->EndDesired($User);
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
      $Discussion = $this->LoungeDiscussion($VoteTitle, $VoteMessage);
      
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
    * Drop an arrow cache
    * 
    * @param integer $CacheSize
    */
   public function DropCache($CacheSize) {
      return;
      $RecentDiscussions = Gdn::SQL()
         ->Select('DiscussionID')
         ->Select('CategoryID')
         ->From('Discussion')
         ->Where('DateLastComment >', date('Y-m-d H:i:s', strtotime('5 minutes ago')))
         ->Get()->ResultArray();
      
      shuffle($RecentDiscussions);
      $PermissionModel = new PermissionModel();
      do { 
         $Discussion = array_pop($RecentDiscussions);
         
         // Check permission
         $CategoryID = $Discussion['CategoryID'];
         $Category = CategoryModel::Categories($CategoryID);
         $PermissionCategoryID = $Category['PermissionCategoryID'];
         
         $DefaultRoles = C('Garden.Registration.DefaultRoles');
         $DefaultMemberRoleID = GetValue(0, $DefaultRoles);
         
         $Result = $PermissionModel->GetRolePermissions($DefaultMemberRoleID, '', 'Category', 'PermissionCategoryID', 'CategoryID', $PermissionCategoryID);
         $Permission = GetValue(0, $Result);
         
         $CanView = (GetValue('Vanilla.Discussions.View', GetValue(0, $Result), FALSE)) ? TRUE : FALSE;
         $CanPost = (GetValue('Vanilla.Discussions.Add', GetValue(0, $Result), FALSE)) ? TRUE : FALSE;
         
         if (!$CanView || !$CanPost)
            continue;
         
         $DiscussionID = $Discussion['DiscussionID'];
         $DiscussionModel = new DiscussionModel();
         $Discussion = (array)$DiscussionModel->GetID($DiscussionID);
         if ($Discussion['Closed']) continue;
         
         // Create
         
         // Cache GUID
         $CacheID = $this->CreateCache($CacheSize);
         
         $CacheMessage = <<<CACHEVALENTINES
<div class="FallenCupid" data-cacheid="{CacheID}">
   <img src="http://cdn.vanillaforums.com/minion/fallencupid.png" />
   <p>Unidentified Aerial Target has been detected and destroyed. Moving to recover debris at crash site.</p>
   <div><a class="FallenCupidLink" href="{CacheUrl}" rel="{CacheID}">Search the debris for arrows</a></div>
</div>
CACHEVALENTINES;
         $CacheMessage = FormatString($CacheMessage, array(
            'CacheUrl'     => Url("/plugin/valentines/cache/{$CacheID}"),
            'CacheID'      => $CacheID
         ));
         $Comment = $this->Minion->Message(NULL, $Discussion, $CacheMessage, array(
            'Format' => FALSE,
            'Inform' => FALSE
         ));
         
         $this->Minion->Monitor($Comment, array('Valentines' => array(
            'Cache'  => $CacheID
         )));
         break;
         
      } while (sizeof($RecentDiscussions));
      
   }
   
   /**
    * Create a cache and return its ID
    * 
    * @param integer $CacheSize
    */
   protected function CreateCache($CacheSize) {
      $CacheID = uniqid('cache');
      $CacheKey = "Cache.{$CacheID}";
      $this->SetUserMeta($this->MinionUser['UserID'], $CacheKey, $CacheSize);
      return $CacheID;
   }
   
   /**
    * Create a new discussion in the lounge
    * 
    * @param string $Title
    * @param string $Message
    * @return array
    * @throws Gdn_UserException
    */
   protected function LoungeDiscussion($Title, $Message) {
      // Make a new discussion
      $DiscussionModel = new DiscussionModel();
      $DiscussionID = $DiscussionModel->Save(array(
         'Name'         => $Title,
         'CategoryID'   => $this->LoungeID,
         'Body'         => $Message,
         'Format'       => 'BBCode',
         'InsertUserID' => $this->Minion->Minion()->UserID,
         'Announce'     => 0,
         'Close'        => 0
      ));

      if (!$DiscussionID)
         throw new Gdn_UserException($DiscussionModel->Validation->ResultsText());

      $DiscussionModel->UpdateDiscussionCount($this->LoungeID);
      $Discussion = (array)$DiscussionModel->GetID($DiscussionID);
      return $Discussion;
   }
   
   /**
    * Create an activity with defaults
    * 
    * @param array $Activity
    */
   protected function Activity($Activity) {
      $Activity = array_merge(array(
         'ActivityType'    => 'Valentines',
         'Force'           => TRUE,
         'Notified'        => ActivityModel::SENT_PENDING
      ), $Activity);
      $this->ActivityModel->Save($Activity);
   }
   
   /**
    * Add arrows to the total pool
    * 
    * @param integer $Arrows
    */
   protected function ArrowPool($Arrows = NULL) {
      $PoolKey = "Arrows.".date('Y').".Pool";
      $ArrowPool = $this->GetUserMeta($this->MinionUser['UserID'], $PoolKey, 0, TRUE);
      if (is_null($Arrows)) return $ArrowPool;
      
      $ArrowPool += $Arrows;
      $this->SetUserMeta($this->MinionUser['UserID'], $PoolKey, $ArrowPool);
      $this->Arrows($Arrows);
      return $ArrowPool;
   }
   
   /**
    * Add or remove arrows from the available pool
    * 
    * @param integer $Arrows
    */
   protected function Arrows($Arrows = NULL) {
      $AvailableKey = "Arrows.".date('Y').".Available";
      $ArrowPool = $this->GetUserMeta($this->MinionUser['UserID'], $AvailableKey, 0, TRUE);
      if (is_null($Arrows)) return $ArrowPool;
      
      $ArrowPool += $Arrows;
      $this->SetUserMeta($this->MinionUser['UserID'], $AvailableKey, $ArrowPool);
      return $ArrowPool;
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
    * Display PM timer output and run expiry checks
    * 
    * @param Gdn_Controller $Sender
    */
   public function Base_Render_Before($Sender) {
      if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL) return;
      if (!Gdn::Session()->IsValid()) return;
      
      $User = (array)Gdn::Session()->User;
      $UserID = $User['UserID'];
      $JavascriptRequired = FALSE;
      
      // Timer deployment
      $User = (array)Gdn::Session()->User;
      $UserValentines = $this->Minion->Monitoring($User, 'Valentines', FALSE);
      $UserDesired = GetValue('Desired', $UserValentines, FALSE);
      if ($UserDesired) {
      
         // Timer has been dismissed
         if (GetValue('Dismissed', $UserDesired, FALSE)) return;

         // User is desired, show timer
         $Expiry = GetValue('Expiry', $UserDesired);
         $Sender->AddDefinition('ValentinesExpiry', $Expiry - time());
         $JavascriptRequired = TRUE;
      }
      
      if ($JavascriptRequired)
         $Sender->AddJsFile('valentines.js', 'plugins/Valentines');
   }
   
   /**
    * Run time based actions
    * 
    *  - Expiry check
    *  - Random arrow deployment
    * 
    * @param Gdn_Statistics $Sender
    */
   public function Gdn_Statistics_AnalyticsTick_Handler($Sender) {
      // Expiry check
      $ExpiryCheckKey = 'plugins.valentines.expirycheck';
      $NextCheckTime = Gdn::Cache()->Get($ExpiryCheckKey);
      if (!$NextCheckTime || $NextCheckTime < microtime(true)) {
         Gdn::Cache()->Store($ExpiryCheckKey, microtime(true)+60);
         
         // Run expiry check
         $MetaKey = $this->MakeMetaKey('Desired.Expiry');
         $ExpiredUsers = Gdn::SQL()->Select('UserID')
            ->From('UserMeta')
            ->Where('Name', $MetaKey)
            ->Where('Value <', time())
            ->Get()->ResultArray();

         foreach ($ExpiredUsers as $ExpiredUser) {
            $ExpiredUserID = GetValue('UserID', $ExpiredUser);
            $ExpiredUser = Gdn::UserModel()->GetID($ExpiredUserID);
            $this->Expire($User);
         }
         
         // Run arrow check
         $ArrowPool = $this->ArrowPool();
         $Arrows = $this->Arrows();
         $Ratio = $Arrows / $ArrowPool;
         
         // When X% or less arrows remain unfired
         if ($Ratio <= $this->RefillTriggerRatio) {
            if ($ArrowPool >= $this->RefillThreshold) {
               
               // Create a cache with enough arrows for a round number of users
               $RefillCacheSize = ceil(($this->RefillCacheRatio * $ArrowPool) / $this->StartArrows) * $this->StartArrows;
               $this->DropCache($RefillCacheSize);
            }
         }
      }
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
      
      $this->AddDesiredCSS($Sender, $Comment);
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
    * @param Gdn_Controller $Sender
    * @param array $Object
    */
   protected function AddDesiredCSS($Sender, $Object) {
      $User = (array)$Sender->EventArguments['Author'];
      
      // Check for Cache
      $Post = $this->Minion->Monitoring($Object, 'Valentines', FALSE);
      if (!$Post) return;
         
      if ($CacheID = GetValue('Cache', $Post, FALSE)) {
         $Sender->EventArguments['CssClass'] .= ' ArrowCache';
         $Sender->AddJsFile('valentines.js', 'plugins/Valentines');
      }
      
      // Is this person playing the game?
      $Playing = $this->Minion->Monitoring($User, 'Valentines', FALSE);
      if (!$Playing) return;

      // Only target people who are playing this year
      $ValentinesYear = GetValue('Year', $Playing, FALSE);
      if ($ValentinesYear != date('Y')) return;
      
      // Add CSS for desired people
      $Desired = GetValue('Desired', $Playing, FALSE);
      if ($Desired) {
         $Sender->EventArguments['CssClass'] .= ' Desired';
      }
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
         $this->Arrows($Increment);
         
         // Register arrow fired
         $ArrowRecord = "Arrow.{$TargetID}.{$Target['Count']}";
         switch ($Mode) {
            case 'set':
               $this->SetUserMeta($PlayerID, $ArrowRecord, time());
               Gdn::Controller()->InformMessage(FormatString(T("Your arrow embeds itself in {TargetUser.UserID,user}'s butt. You have <b>{Player.Quiver}</b> {Arrows} left."), array(
                  'Player'       => $Player,
                  'Target'       => $Target,
                  'PlayerUser'   => $PlayerUser,
                  'TargetUser'   => $TargetUser,
                  'TargetUserUrl'=> UserAnchor($TargetUser),
                  'Arrows'       => Plural($Player['Quiver'], 'arrow', 'arrows'),
                  'Minion'       => $this->MinionUser
               )));
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
         'Type' => 'Manual',
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
      
      $this->ActivityModel->DefineType('Valentines', array(
         'Notify'    => 1,
         'Public'    => 0
      ));
      
   }
   
}