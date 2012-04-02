<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['IPBFormatter'] = array(
    'Name' => 'IPB Formatter',
    'Description' => 'Formats posts imported from Invision Power Board.',
    'Version' => '1.0a',
    'RequiredApplications' => array('Vanilla' => '2.0.2a'),
    'RequiredPlugins' => FALSE,
    'HasLocale' => FALSE,
    'Author' => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com/profile/todd'
);

Gdn::FactoryInstall('IPBFormatter', 'IPBFormatterPlugin', __FILE__, Gdn::FactorySingleton);

class IPBFormatterPlugin extends Gdn_Plugin {
   /// Methods ///
   
   public function Format($String) {
      $String = html_entity_decode($String, ENT_QUOTES, 'UTF-8');
//      $String = str_replace('<br />', "\n", $String);
//      $String = str_replace(array('<', '>'), array('[', ']'), $String);
      $Result = $this->NBBC()->Parse($String);
      return $Result;
   }
   
   /**
    *
    * @var BBCode
    */
   protected $_NBBC;
   
   /**
    * @return BBCode;
    */
   public function NBBC() {
      if ($this->_NBBC === NULL) {
         $Plugin = new NBBCPlugin('BBCodeRelaxed');
         $this->_NBBC = $Plugin->NBBC();
         $this->_NBBC->ignore_newlines = TRUE;
      }
      return $this->_NBBC;
   }
}