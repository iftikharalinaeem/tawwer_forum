<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
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
    'Icon' => 'sphinx.png',
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
     * Search discussions.
     *
     * @param DiscussionsApiController $sender
     * @param array $query
     * @return array
     */
    public function discussionsApiController_get_search(DiscussionsApiController $sender, array $query) {
        $sender->permission('Garden.SignIn.Allow');

        $in = $sender->schema([
            'query:s' => 'Discussion search query.',
            'categoryID:i?' => 'The numeric ID of a category.',
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => DiscussionModel::instance()->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => DiscussionModel::instance()->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'expand:b?' => 'Expand associated records.'
        ], 'in')->setDescription('Search discussions.');
        $out = $sender->schema([':a' => $sender->discussionSchema()], 'out');

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit(
            "p{$query['page']}",
            $query['limit']
        );
        $params = [
            'group' => false,
            'search' => $query['query'],
            'discussion_d' => 1
        ];
        if (array_key_exists('categoryID', $query)) {
            $search['cat'] = $query['categoryID'];
        }

        $searchModel = new SearchModel();
        $search = $searchModel->advancedSearch($params, $offset, $limit);
        $rows = $search['SearchResults'];
        $discussionIDs = array_column($rows, 'PrimaryID');
        $result = [];

        if (!empty($discussionIDs)) {
            $result = DiscussionModel::instance()
                ->getWhere(['DiscussionID' => $discussionIDs])
                ->resultArray();
            if ($query['expand']) {
                $userModel = new UserModel();
                $userModel->expandUsers($result, ['InsertUserID']);
            }
            foreach ($result as &$row) {
                $sender->massageRow($row);
            }
        }

        $result = $out->validate($result);
        return $result;
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
