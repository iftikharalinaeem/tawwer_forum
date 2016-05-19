<?php

/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['Sphinx'] = [
    'Name' => 'Sphinx Search',
    'Description' => "Upgrades search to use the powerful Sphinx engine instead of the default search.",
    'Version' => '1.1.3',
    'RequiredApplications' => [
        'Vanilla' => '2.0.17'
    ],
    'Author' => 'Todd Burry',
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
    'SettingsUrl' => '/settings/sphinx',
];

// Force require our sphinx so that an incomplete autoloader doesn't miss it.
if (!class_exists('SearchModel', false)) {
    require_once __DIR__ . '/class.searchmodel.php';
}

/**
 * Sphinx Plugin
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @package internal
 */
class SphinxPlugin extends Gdn_Plugin {

    /**
     * Fired when plugin is disabled
     *
     * This code forces vanilla to re-index its files.
     */
    public function onDisable() {
        // Remove the current library map so re-indexing will occur
        @unlink(PATH_CACHE . '/library_map.ini');
    }

    /**
     * Fired when plugin is enabled
     *
     * @throws Exception
     */
    public function setup() {
        if (!class_exists('SphinxClient')) {
            throw new Exception('Sphinx requires the sphinx client to be installed. See http://www.php.net/manual/en/book.sphinx.php');
        }

        // Remove the current library map so that the core file won't be grabbed.
        @unlink(PATH_CACHE . '/library_map.ini');
        $this->structure();
    }

    /**
     * Fired when the structure is executed
     *
     */
    public function structure() {
        Gdn::structure()
            ->table('SphinxCounter')
            ->column('CounterID', 'uint', false, 'primary')
            ->column('MaxID', 'uint', '0')
            ->engine('InnoDB')
            ->set();
    }

    /**
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_sphinx_create($sender) {
        $sender->permission('Garden.Settings.Manage');

        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Plugins.Sphinx.Server'     => 'auto',
            'Plugins.Sphinx.Port'       => 9312,
            'Plugins.Sphinx.UseDeltas'  => true
        ]);

        // Set the model on the form.
        $sender->Form = new Gdn_Form();
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            // Save new settings
            $Saved = $sender->Form->save();
            if ($Saved) {
                $sender->informMessage(t('Saved'));
            }
        }

        $sender->setData('Title', 'Sphinx Settings');

        $sender->addSideMenu('/settings/plugins');
        $sender->render('settings', '', 'plugins/Sphinx');
    }

}
