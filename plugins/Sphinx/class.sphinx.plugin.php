<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['Sphinx'] = array(
   'Name' => 'Sphinx Search',
   'Description' => "Upgrades search to use the powerful Sphinx engine instead of the default search.",
   'Version' => '1.1.1',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/settings/sphinx',
);

class SphinxPlugin extends Gdn_Plugin {
   public function  __construct() {
      parent::__construct();
   }

   public function OnDisable() {
      // Remove the current library map so re-indexing will occur
      @unlink(PATH_CACHE.'/library_map.ini');
   }

   public function Setup() {
      if (!class_exists('SphinxClient')) {
         throw new Exception('Sphinx requires the sphinx client to be installed. See http://www.php.net/manual/en/book.sphinx.php');
      }
      
      // Remove the current library map so that the core file won't be grabbed.
      @unlink(PATH_CACHE.'/library_map.ini');
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('SphinxCounter')
         ->Column('CounterID', 'uint', FALSE, 'primary')
         ->Column('MaxID', 'uint', '0')
         ->Engine('InnoDB')
         ->Set();
   }

   /**
    *
    * @param SettingsController $Sender
    * @param array $Args
    */
   public function SettingsController_Sphinx_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');

		// Load up config options we'll be setting
		$Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.Sphinx.Server' => 'int.sphinx1.vanilladev.com',
         'Plugins.Sphinx.Port' => 9312,
         'Plugins.Sphinx.UseDeltas' => TRUE
      ));

      // Set the model on the form.
      $Sender->Form = new Gdn_Form();
      $Sender->Form->SetModel($ConfigurationModel);

      // If seeing the form for the first time...
      if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
         // Apply the config settings to the form.
         $Sender->Form->SetData($ConfigurationModel->Data);
		} else {
			// Save new settings
			$Saved = $Sender->Form->Save();
         if ($Saved)
            $Sender->InformMessage(T('Saved'));
		}

      $Sender->SetData('Title', 'Sphinx Settings');

      $Sender->AddSideMenu('/dashboard/settings/plugins');
      $Sender->Render('settings', '', 'plugins/Sphinx');
   }
}