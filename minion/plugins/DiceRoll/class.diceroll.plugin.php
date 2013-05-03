<?php if (!defined('APPLICATION')) exit();

/**
 * Dice Roll Plugin
 * 
 * Changes: 
 *  1.0     Release
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package misc
 */

$PluginInfo['DiceRoll'] = array(
   'Name' => 'Minion: Dice Roll',
   'Description' => "Roll some die.",
   'Version' => '1.0',
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

class GamingPlugin extends Gdn_Plugin {
   
   const LIMIT_KEY = 'minion.dice.limit.%d';
   
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
            $canRoll = $this->limit($user);
            if (!$canRoll) return false;
            
            $dice = strtolower(GetValueR('Targets.Phrase', $State));
            
            $rolled = $this->roll($dice, GetValueR('Sources.Discussion', $State), $user);
            if ($rolled && !Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))
               $this->limit($user, true);
            
            break;
      }
   }
   
   /**
    * Rolls some die
    * 
    * @param string $dice
    * @param array $discussion
    * @param array $user
    */
   public function roll($dice, $discussion, $user) {
      
      $validDice = preg_match('/([\d]+)d([\d]+)((\+|\-)[\d]+)?/', $dice, $matches);
      if (!$validDice) return false;
      
      $numDie = abs($matches[1]);
      if ($numDie > 10) $numDie = 10;
      if (!$numDie) $numDie = 1;
      $diceSides = abs($matches[2]);
      $modifier = sizeof($matches) == 5 ? $matches[3] : 0;
      $intModifier = intval($modifier);
      
      $rolls = array();
      for ($i = 1; $i <= $numDie; $i++) {
         $roll = mt_rand(1,$diceSides) + $intModifier;
         $rolls[] = $roll;
      }
      
      if (sizeof($rolls)) {
         
         $rolls = implode(', ', $rolls);
         $diceRoll = "{$dice}";
         $diceRoll .= $modifier ? " ({$modifier})" : '';
         
         $message = <<<ROLL
/me rolls $diceRoll... [b]{$rolls}[/b]
ROLL;
         $inform = <<<ROLLINFORM
{Minion.UserID,user} rolls $diceRoll... <b>{$rolls}</b>
ROLLINFORM;

         $minion = Gdn::PluginManager()->GetPluginInstance('MinionPlugin');
         $minion->Message($user, $discussion, $message, array(
            'InputFormat'  => 'BBCode',
            'Inform'       => false
         ));
         
         $inform = FormatString($inform, array(
            'Minion'    => $minion->Minion()
         ));
         Gdn::Controller()->InformMessage($inform);
      }
   }
   
   /**
    * 
    * 
    * @param array $user
    * @param bool $limit
    */
   public function limit($user, $limit = null) {
      $key = sprintf(self::LIMIT_KEY, $user['UserID']);
      
      // Check
      if (is_null($limit))
         return !(bool)Gdn::Cache()->Get($key);
      
      // Set
      Gdn::Cache()->Store($key, true, array(
         Gdn_Cache::FEATURE_EXPIRY  => 60
      ));
   }
}