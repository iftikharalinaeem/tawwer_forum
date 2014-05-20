<?php if (!defined('APPLICATION')) exit();

/**
 * Gaming Plugin
 *
 * Acts a game loader / invoker, and supplies game tools to Minion games.
 *
 * Tools:
 *  Inventory
 *
 * Changes:
 *  1.0     Release
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package misc
 */

$PluginInfo['Gaming'] = array(
   'Name' => 'Minion: Gaming',
   'Description' => "Add minion gaming tools and commands.",
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

   protected $games = array();
   protected $aliases = array();

   /**
    * Parse a token from the current state
    *
    * @param MinionPlugin $sender
    */
   public function MinionPlugin_Token_Handler($sender) {
      $State = &$sender->EventArguments['State'];

      if (!$State['Method'] && in_array($State['CompareToken'], array('play', 'playing'))) {
         $sender->consume($State, 'Method', 'play');

         $sender->consume($State, 'Gather', array(
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
         case 'play':

            // Games must be started in the OP
            if (array_key_exists('Comment', $State['Sources']))
               return;

            // Games must have a name
            if (!array_key_exists('Phrase', $State['Targets']))
               return;

            $Actions[] = array('play', null, $State);
            break;

      }

   }

   /**
    * Perform custom minion actions
    *
    * @param MinionPlugin $sender
    */
   public function MinionPlugin_Action_Handler($sender) {
      $action = $sender->EventArguments['Action'];
      $state = &$sender->EventArguments['State'];

      switch ($action) {

         // Play a game, or shut one down
         case 'play':

            // Games must be started in the OP
            if (array_key_exists('Comment', $state['Sources']))
               return;

            $gameName = strtolower(valr('Targets.Phrase', $state));
            if (!array_key_exists($gameName, $this->aliases)) {
               break;
            }
            $game = $this->aliases[$gameName];
            $pluginName = $game['plugin'];
            $plugin = Gdn::pluginManager()->getPluginInstance($pluginName, Gdn_PluginManager::ACCESS_PLUGINNAME);

            switch ($state['Toggle']) {

               case 'off':
                  $plugin->stopGame($state['Sources']['Discussion'], $state['Sources']['User']);
                  break;

               case 'on':
               default:
                  $plugin->startGame($state['Sources']['Discussion'], $state['Sources']['User']);
                  break;

            }

            break;
      }
   }

   /**
    * Collect games
    *
    * @param MinionPlugin $sender
    */
   public function MinionPlugin_Start_Handler($sender) {
      $this->fireEvent('Register');
   }

   /**
    * Register a game with Minion
    *
    * @param string $game
    * @param array $aliases
    * @param string $pluginName
    */
   public function registerGame($game, $aliases, $pluginName) {
      $this->games[$game] = array(
         'name'      => $game,
         'aliases'   => $aliases,
         'plugin'    => $pluginName
      );
      $this->aliases = array_merge($this->aliases, array_fill_keys($aliases, $game));
   }

}