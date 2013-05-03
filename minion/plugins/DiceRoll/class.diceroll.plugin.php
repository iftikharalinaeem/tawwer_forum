<?php if (!defined('APPLICATION')) exit();

/**
 * Dice Roll Plugin
 * 
 * Changes: 
 *  1.0     Release
 *  1.1     Multi dice rolls
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package misc
 */

$PluginInfo['DiceRoll'] = array(
   'Name' => 'Minion: Dice Roll',
   'Description' => "Roll some die.",
   'Version' => '1.1',
   'RequiredApplications' => array(
      'Vanilla' => '2.1a'
    ),
   'RequiredPlugins' => array(
      'Minion' => '1.14'
   ),
   'MobileFriendly' => TRUE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class DiceRollPlugin extends Gdn_Plugin {
   
   const LIMIT_KEY = 'minion.dice.limit.%d';
   const MAX = 2147483647;
   
   /**
    * Parse a token from the current state
    * 
    * @param MinionPlugin $sender
    */
   public function MinionPlugin_Token_Handler($sender) {
      $State = &$sender->EventArguments['State'];

      if (!$State['Method'] && in_array($State['CompareToken'], array('roll'))) {
         $sender->Consume($State, 'Method', 'roll');
         
         $sender->Consume($State, 'Gather', array(
            'Node'   => 'Phrase',
            'Delta'  => ''
         ));
      }
   }
   
   /**
    * Parse custom minion commands
    * 
    * @param MinionPlugin $sender
    */
   public function MinionPlugin_Command_Handler($sender) {
      $Actions = &$sender->EventArguments['Actions'];
      $State = &$sender->EventArguments['State'];
      
      switch ($State['Method']) {
         case 'roll':
            
            // Rolls must occur in discussions
            if (!array_key_exists('Discussion', $State['Sources']))
               return;
            
            // Rolls must specify a dice type
            if (!array_key_exists('Phrase', $State['Targets']))
               return;

            $Actions[] = array('roll', null, $State);
            break;
            
      }
      
   }
   
   /**
    * Perform custom minion actions
    * 
    * @param MinionPlugin $sender
    */
   public function MinionPlugin_Action_Handler($sender) {
      $Action = $sender->EventArguments['Action'];
      $State = &$sender->EventArguments['State'];
      
      switch ($Action) {
         
         // Play a game, or shut one down
         case 'roll':
            
            // Games must be started in the OP
            if (!array_key_exists('Discussion', $State['Sources']))
               return;
            
            $user = GetValueR('Sources.User', $State);
            $limited = $this->limited($user);
            if ($limited) {
               Gdn::Controller()->InformMessage('Calm down buttercup, you might have a problem');
               return false;
            }
            
            $dice = strtolower(GetValueR('Targets.Phrase', $State));
            
            $rolled = $this->roll($dice, GetValueR('Sources.Discussion', $State), $user);
            if ($rolled && !Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
               $this->limited($user, true);
            
            break;
      }
   }
   
   /**
    * Rolls some die
    * 
    * @param string $die
    * @param array $discussion
    * @param array $user
    */
   public function roll($die, $discussion, $user) {
      
      $die = explode(' ', $die);
      if (sizeof($die) > 6)
         $die = array_slice($die, 0, 6);
      
      $rolls = array();
      $j = 0;
      foreach ($die as $dice) {
         $j++; // Number of dice types
         
         $validDice = preg_match('/([\d]+)d([\d]+)((\+|\-)[\d]+)?/', $dice, $matches);
         if (!$validDice) continue;

         $numDie = abs($matches[1]);
         if ($numDie > 10) $numDie = 10;
         if (!$numDie) $numDie = 1;
         $diceSides = abs($matches[2]);
         if (!$diceSides) $diceSides = 1;
         if ($diceSides > self::MAX) $diceSides = self::MAX;

         $modifier = sizeof($matches) == 5 ? $matches[3] : 0;
         $intModifier = intval($modifier);
         if ($intModifier > self::MAX) $intModifier = self::MAX;
         if ($intModifier < -self::MAX) $intModifier = -self::MAX;
         
         $dice = "{$numDie}d{$diceSides}";
         $diceRoll = "{$dice}";
         $diceRoll .= $modifier ? "({$modifier})" : '';

         $diceRolls = array();
         for ($i = 1; $i <= $numDie; $i++) {
            $roll = mt_rand(1,$diceSides) + $intModifier;
            $diceRolls[] = $roll;
         }
         $rolls[] = array(
            'dice'      => $dice,
            'modifier'  => $modifier,
            'diceName'  => $diceRoll,
            'rolls'     => $diceRolls
         );
      }
      
      // Output
      if (sizeof($rolls)) {

         $message = "/me rolls ";
         $strRolls = array();
         foreach ($rolls as $roll) {
            $sum = array_sum($roll['rolls']);
            
            $strRolls[] = sprintf("[b]%s[/b] -> %s (sum:%d)", $roll['diceName'], implode(',',$roll['rolls']), $sum);
         }
         $strRolls = implode(', ', $strRolls);
         $message .= $strRolls;
        
         $minion = Gdn::PluginManager()->GetPluginInstance('MinionPlugin');
         $minion->Message($user, $discussion, $message, array(
            'InputFormat'  => 'BBCode',
            'Inform'       => false
         ));

         $inform = Gdn_Format::BBCode($message);
         $inform = preg_replace('/^\/me/', UserAnchor($minion->Minion()), $inform);

         Gdn::Controller()->InformMessage($inform);
         
         return true;
      }
      
      return false;
   }
   
   /**
    * Check/Set roll limits
    * 
    * @param array $user
    * @param bool $limit
    */
   public function limited($user, $limit = null) {
      $key = sprintf(self::LIMIT_KEY, GetValue('UserID', $user));
      
      // Check
      if (is_null($limit))
         return (bool)Gdn::Cache()->Get($key);
      
      // Set
      Gdn::Cache()->Store($key, true, array(
         Gdn_Cache::FEATURE_EXPIRY  => 60
      ));
   }
}