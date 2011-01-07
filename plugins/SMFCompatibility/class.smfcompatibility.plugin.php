<?php if (!defined('APPLICATION')) exit();
/**********************************************************************************
* This program is free software; you may redistribute it and/or modify it under   *
* the terms of the provided license as published by Simple Machines LLC.          *
*                                                                                 *
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* See the "license.txt" file for details of the Simple Machines license.          *
**********************************************************************************/

$PluginInfo['SMFCompatibility'] = array(
   'Name' => 'SMF Compatibility',
   'Description' => 'Adds some compatibility functionality for forums imported from SMF.',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.2a'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com/profile/todd',
   'License' => 'Simple Machines license'
);

Gdn::FactoryInstall('BBCodeFormatter', 'SMFCompatibilityPlugin', __FILE__, Gdn::FactorySingleton);

class SMFCompatibilityPlugin extends Gdn_Plugin {
	/// CONSTRUCTOR ///
	public function __construct() {
      require_once(dirname(__FILE__).'/functions.smf.php');
	}

	/// PROPERTIES ///


	/// METHODS ///

	public function Format($String) {
      $Result = parse_bbc($String);
      return $Result;
	}

	public function Setup() {
      $OldFormat = C('Garden.InputFormatter');

      if ($OldFormat != 'BBCode') {
         SaveToConfig(array(
            'Garden.InputFormatter' => 'BBCode',
            'Garden.InputFormatterBak' => $OldFormat));
      }
	}

   public function OnDisable() {
      $OldFormat = C('Garden.InputFormatterBak');

      if ($OldFormat !== FALSE) {
         SaveToConfig('Garden.InputFormatter', $OldFormat);
      }
   }
}