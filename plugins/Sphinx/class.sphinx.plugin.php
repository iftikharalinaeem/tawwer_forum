<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Schema\Schema;

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

    /** The highest value for "limit" in the default, generic schema. */
    const MAX_SCHEMA_LIMIT = 100;

    /** @var SearchModel */
    private $searchModel;

    /** @var Schema */
    private $searchSchema;

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
            throw new Exception(
                'Sphinx requires the sphinx client to be installed (See http://www.php.net/manual/en/book.sphinx.php). '
                .'Alternatively you can set "Plugins.Sphinx.SphinxAPIDir" to the location of sphinxapi.php before enabling the plugin (See https://github.com/sphinxsearch/sphinx/blob/master/api/sphinxapi.php).'
            );
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
     * Search comments.
     *
     * @param CommentsApiController $sender
     * @param array $query
     * @return array
     */
    public function commentsApiController_get_search(CommentsApiController $sender, array $query) {
        $sender->permission('Garden.SignIn.Allow');

        $in = $sender
            ->schema(['categoryID:i?' => 'The numeric ID of a category.'], 'in')
            ->merge($this->searchSchema())
            ->setDescription('Search comments.');
        $out = $sender->schema([':a' => $sender->commentSchema()], 'out');

        $params = [
            'group' => false,
            'comment_c' => 1
        ];
        if (array_key_exists('categoryID', $query)) {
            $params['cat'] = $query['categoryID'];
        }
        $query = $sender->filterValues($query);
        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit(
            "p{$query['page']}",
            $query['limit']
        );
        $result = $this->searchModel()->modelSearch(
            CommentModel::instance(),
            $query['query'],
            $params,
            $limit,
            $offset,
            $query['expand']
        );

        foreach ($result as &$row) {
            $sender->normalizeOutput($row);
        }
        $result = $out->validate($result);
        return $result;
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

        $in = $sender
            ->schema(['categoryID:i?' => 'The numeric ID of a category.'], 'in')
            ->merge($this->searchSchema())
            ->setDescription('Search discussions.');
        $out = $sender->schema([':a' => $sender->discussionSchema()], 'out');

        $query = $sender->filterValues($query);
        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit(
            "p{$query['page']}",
            $query['limit']
        );
        $params = [
            'group' => false,
            'discussion_d' => 1
        ];
        if (array_key_exists('categoryID', $query)) {
            $params['cat'] = $query['categoryID'];
        }

        $result = $this->searchModel()->modelSearch(
            DiscussionModel::instance(),
            $query['query'],
            $params,
            $limit,
            $offset,
            $query['expand']
        );

        foreach ($result as &$row) {
            $sender->normalizeOutput($row);
        }
        $result = $out->validate($result);
        return $result;
    }

    /**
     * Get the plugins copy of SearchModel.
     *
     * @return SearchModel
     */
    private function searchModel() {
        if (!isset($this->searchModel)) {
            $this->searchModel = new SearchModel();
        }

        return $this->searchModel;
    }

    /**
     * Get a generic search schema.
     *
     * @return Schema
     */
    private function searchSchema() {
        if (!isset($this->searchSchema)) {
            $this->searchSchema = Schema::parse([
                'query:s' => 'Search terms.',
                'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                    'default' => 1,
                    'minimum' => 1,
                    'maximum' => DiscussionModel::instance()->getMaxPages()
                ],
                'limit:i?' => [
                    'description' => 'Desired number of items per page.',
                    'default' => DiscussionModel::instance()->getDefaultLimit(),
                    'minimum' => 1,
                    'maximum' => self::MAX_SCHEMA_LIMIT
                ],
                'expand:b?' => [
                    'default' => false,
                    'description' => 'Expand associated records.'
                ]
            ]);
        }

        return $this->searchSchema;
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
            $saved = $sender->Form->save();
            if ($saved) {
                $sender->informMessage(t('Saved'));
            }
        }

        $sender->setData('Title', 'Sphinx Settings');

        $sender->addSideMenu('/settings/plugins');
        $sender->render('settings', '', 'plugins/Sphinx');
    }

}
