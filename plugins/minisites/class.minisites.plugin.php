<?php if (!defined('APPLICATION')) exit;

$PluginInfo['minisites'] = array(
    'Name'        => "Minisites",
    'Description' => "Allows you to use categories as virtual mini forums for multilingual or multi-product communities.",
    'Version'     => '1.0.0-alhpa',
    'Author'      => "Todd Burry",
    'AuthorEmail' => 'todd@vanillaforums.com',
    'AuthorUrl'   => 'https://vanillaforums.com',
    'License'     => 'Proprietary'
);


class MinisitesPlugin extends Gdn_Plugin {
    /// Properties ///

    /// Methods ///

    /**
     */
    public function setup() {
        $this->structure();
    }

    public function structure() {
        Gdn::Structure()
            ->Table('Minisite')
            ->PrimaryKey('MinisiteID')
            ->Column('Name', 'varchar(255)')
            ->Column('Folder', 'varchar(255)', false, 'unique.Folder')
            ->Column('CategoryID', 'int', true)
            ->Column('Locale', 'varchar(20)')
            ->Column('DateInserted', 'datetime')
            ->Column('InsertUserID', 'int')
            ->Column('DateUpdated', 'datetime', true)
            ->Column('UpdateUserID', 'int', true)
            ->Column('Attributes', 'text', true)
            ->Column('IsDefault', 'tinyint(1)', true, 'unique.IsDefault')
            ->Set();
    }

    /// Event Handlers ///

    public function base_getAppSettingsMenuItems_handler($sender) {
        /* @var SideMenuModule */
        $menu = $sender->EventArguments['SideMenu'];
        $menu->AddLink('Forum', T('Minisites'), '/minisites', 'Garden.Settings.Manage', ['After' => 'vanilla/settings/managecategories']);
    }

    public function Gdn_Dispatcher_AppStartup_Handler() {
        $newRoot = 'en';

        $parts = explode('/', trim(Gdn::Request()->Path(), '/'), 2);
        if (empty($parts[1])) {
            $parts[1] = '';
        }

        if ($parts[0] === $newRoot) {
            Gdn::Request()->Path($parts[1]);
        }
        Gdn::Request()->WebRoot($newRoot);
        Gdn::Request()->AssetRoot('/');
    }
}
