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
      $String = str_replace('<#EMO_DIR#>', 'default', $String);
      $Result = $this->NBBC()->Parse($String);
      
      // Make sure to clean filter the html in the end.
      $Config = array(
       'anti_link_spam' => array('`.`', ''),
       'comment' => 1,
       'cdata' => 3,
       'css_expression' => 1,
       'deny_attribute' => 'on*',
       'elements' => '*-applet-form-input-textarea-iframe-script-style', // object, embed allowed
       'keep_bad' => 0,
       'schemes' => 'classid:clsid; href: aim, feed, file, ftp, gopher, http, https, irc, mailto, news, nntp, sftp, ssh, telnet; style: nil; *:file, http, https', // clsid allowed in class
       'valid_xml' => 2
      );

      $Spec = 'object=-classid-type, -codebase; embed=type(oneof=application/x-shockwave-flash)';
      $Result = htmLawed($Result, $Config, $Spec);
      
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
         require_once PATH_PLUGINS.'/HtmLawed/htmLawed/htmLawed.php';
         
         $Plugin = new NBBCPlugin('BBCodeRelaxed');
         $this->_NBBC = $Plugin->NBBC();
         $this->_NBBC->ignore_newlines = TRUE;
         $this->_NBBC->enable_smileys = FALSE;
      }
      return $this->_NBBC;
   }
}