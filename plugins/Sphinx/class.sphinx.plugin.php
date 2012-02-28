<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['Sphinx'] = array(
   'Name' => 'Sphinx Search',
   'Description' => "Upgrades search to use the powerful Sphinx engine instead of the default search.",
   'Version' => '1.0',
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

      switch (strtolower(GetValue(0, $Args))) {
         case 'sphinx.conf':
            $this->_GenerateConf($Sender, $Args);
            return;
      }

		// Load up config options we'll be setting
		$Validation = new Gdn_Validation();
      $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
      $ConfigurationModel->SetField(array(
         'Plugins.Sphinx.Server' => C('Database.Host'),
         'Plugins.Sphinx.Port' => 9312,
         'Plugins.Sphinx.UseDeltas',
         'Plugins.Sphinx.ForceInnoDB'
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

   /**
    * @param Gdn_MySQLStructure $Sender
    * @param array $Args
    */
   public function Gdn_MySQLStructure_BeforeSet_Handler($Sender, $Args) {
      $SearchModel = new SearchModel();

      if (C('Plugins.Sphinx.ForceInnoDB') && in_array($Sender->TableName(), $SearchModel->Types))
         $Sender->Engine('InnoDB');
   }

   protected function _GenerateConf($Sender, $Args) {
      $SearchModel = new SearchModel();

      @ob_end_clean();

      $fp = fopen('php://output', 'ab');
      header("Content-Disposition: attachment; filename=\"sphinx.conf\"");
      header('Content-Type: text/plain');
      header("Content-Transfer-Encoding: binary");
      header('Accept-Ranges: bytes');
      header("Cache-control: private");
      header('Pragma: private');
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

      $SearchModel->GenerateConfig($fp);
   }
}