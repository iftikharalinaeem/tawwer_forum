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
 * will also be given 3 "arrows". These arrows can be shot at other users. Once 
 * a given user is hit by 5 arrows, they are 'Desired'.
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
   
   protected $Enabled;
   
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
      $this->Enabled = date('nd') == '214';
      $this->Year = date('Y');
      
      if ($this->Enabled) {
         $this->Minion = MinionPlugin::Instance();
      }
   }
   
   /**
    * Give people who log in on Valentines Day a badge
    * 
    * @param Gdn_Dispatcher $Sender
    */
   public function Gdn_Dispatcher_AppStartup_Handler($Sender) {
      if (!$this->Enabled) return;
      if (!Gdn::Session()->IsValid() && !Gdn::Session()->UserID) return;
      
      $Participating = $this->Minion->Monitoring(Gdn::Session()->User, 'Valentines', FALSE);
      if ($Participating) return;
      
      // Award login badge
      $BadgeName = "valentines{$this->Year}";
      $BadgeModel = new BadgeModel();
      $Valentines = $BadgeModel->GetID($BadgeName);
      if (!$Valentines) {
         $this->Structure();
         $Valentines = $BadgeModel->GetID($BadgeName);
         if (!$Valentines) return;
      }
      
      $UserBadgeModel = new UserBadgeModel();
      $BadgeModel->Give(Gdn::Session()->UserID, $Valentines['BadgeID']);
      
      // Award arrows
      $this->Minion->Monitor(Gdn::Session()->User, 'Valentines', array(
         'Started'   => time(),
         'Quiver'    => C('Plugins.Valentines.StartArrows', 3),
         'Shot'      => 0
      ));
   }
   
   /**
    * Add Arrow of Desire reaction to the row
    * 
    */
   public function Base_AfterReactions_Handler($Sender) {
      // Only those who can react
      if (!Gdn::Session()->IsValid()) return;
      
      if (array_key_exists('Comment', $Sender->EventArguments))
         $Object = (array)$Sender->EventArguments['Comment'];
      else if (array_key_exists('Discussion', $Sender->EventArguments))
         $Object = (array)$Sender->EventArguments['Discussion'];
      else
         return;
      
      // Is the object hunted?
      $IsHunted = MinionPlugin::Instance()->Monitoring($Object, 'Hunted', FALSE);
      if (!$IsHunted) return;
      
      $User = (array)$Sender->EventArguments['Author'];
      // Don't show it for myself
      if ($User['UserID'] == Gdn::Session()->UserID) return;
      
      $Hunted = MinionPlugin::Instance()->Monitoring($User, 'Hunted', FALSE);
      if (!$Hunted) return;
      
      echo Gdn_Theme::BulletItem('Hunted');
      echo '<span class="Hunter ReactMenu">';
         echo '<span class="ReactButtons">';
            echo ReactionButton($Object, 'AlertAuthorities');
            echo ReactionButton($Object, 'HideCriminal');
         echo '</span>';
      echo '</span>';
   }
   
   /*
    * SETUP
    */
   
   public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {
      
      // Define 'Arrow of Desire' reactions
      $Rm = new ReactionModel();

      if (Gdn::Structure()->Table('ReactionType')->ColumnExists('Hidden')) {

         // Shoot with arrow
         $Rm->DefineReactionType(array(
            'UrlCode' => 'ShootArrow', 
            'Name' => 'Arrow of Desire', 
            'Sort' => 0, 
            'Class' => 'Good', 
            'Hidden' => 1,
            'Description' => "Shoot your target with an arrow of desire."
         ));

      }
      Gdn::Structure()->Reset();
      
      // Define Valentines badges
      $BadgeModel = new BadgeModel();

      // Criminal
      $Year = date('Y');
      $BadgeModel->Define(array(
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
      
      // Arrowed
      $BadgeModel->Define(array(
          'Name' => 'Highly Desirable',
          'Slug' => 'desirable',
          'Type' => 'Manual',
          'Body' => "You're in high demand... and full of arrow holes.",
          'Photo' => 'http://badges.vni.la/100/desirable.png',
          'Points' => 20,
          'Class' => 'Valentines',
          'Level' => 1,
          'CanDelete' => 0
      ));
      
      // Snitch
      $BadgeModel->Define(array(
          'Name' => 'Smooth Talker',
          'Slug' => 'smoothtalker',
          'Type' => 'Manual',
          'Body' => "The way you write, you could charm the pants off Samus.",
          'Photo' => 'http://badges.vni.la/100/smoothtalker.png',
          'Points' => 20,
          'Class' => 'Hunter',
          'Level' => 1,
          'CanDelete' => 0
      ));
      
   }
   
}