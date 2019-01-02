<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Container;
use Garden\Schema\Schema;
use Interop\Container\ContainerInterface;

/**
 * Sphinx Plugin
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @package internal
 */
class SphinxPlugin extends Gdn_Plugin {

    /** The highest value for "limit" in the default, generic schema. */
    const MAX_SCHEMA_LIMIT = 100;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var ContainerInterface */
    private $container;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var SphinxSearchModel */
    private $searchModel;

    /** @var Schema */
    private $searchSchema;

    /** @var Gdn_Session */
    private $session;

    /**
     * SphinxPlugin constructor.
     *
     * @param ContainerInterface $container
     * @param CategoryModel $categoryModel
     * @param DiscussionModel $discussionModel
     * @param Gdn_Session $session
     */
    public function __construct(ContainerInterface $container, CategoryModel $categoryModel, DiscussionModel $discussionModel, Gdn_Session $session) {
        $this->categoryModel = $categoryModel;
        $this->container = $container;
        $this->discussionModel = $discussionModel;
        $this->session = $session;

        self::checkSphinxClient(c('Plugins.Sphinx.SphinxAPIDir', null));
    }

    /**
     * Override the search model with the sphinx search model.
     *
     * @param Container $dic The container to initialize.
     */
    public function container_init(Container $dic) {
        $dic->rule(\SearchModel::class)
            ->setShared(true)
            ->setClass(\SphinxSearchModel::class)
        ;
    }

    /**
     * Fired when plugin is enabled
     *
     * @throws Exception
     */
    public function setup() {
        if (!self::checkSphinxClient(c('Plugins.Sphinx.SphinxAPIDir', null))) {
            throw new Exception(
                'Sphinx requires the sphinx client to be installed (See http://www.php.net/manual/en/book.sphinx.php). '
                .'Alternatively you can set "Plugins.Sphinx.SphinxAPIDir" to the location of sphinxapi.php before enabling the plugin (See https://github.com/sphinxsearch/sphinx/blob/master/api/sphinxapi.php).'
            );
        }

        $this->structure();
    }

    /**
     * Check if a SphinxClient is available.  If not try to make it available using $apiDir.
     *
     * $apiPAth is a kludge that allows to use Sphinx on PHP7 the correct version without compiling the php extension yourself
     * Source of the class https://github.com/sphinxsearch/sphinx/blob/master/api/sphinxapi.php
     * Make sure that it matches the sphinx version you are using.
     *
     * @param string $apiDir Directory that contains sphinxapi.php
     *
     * @return bool
     */
    public static function checkSphinxClient($apiDir = null) {
        if (class_exists('SphinxClient')) {
            return true;
        }

        $sphinxClientPath = rtrim($apiDir, '/').'/sphinxapi.php';
        if (is_readable($sphinxClientPath)) {
            require_once($sphinxClientPath);
        }

        return class_exists('SphinxClient');
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
        $result = $this->getSearchModel()->modelSearch(
            CommentModel::instance(),
            $query['query'],
            $params,
            $limit,
            $offset,
            $query['expand']
        );

        foreach ($result as &$row) {
            $row = $sender->normalizeOutput($row);
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
            ->schema([
                'categoryID:i?' => 'The numeric ID of a category to limit search results to.',
                'followed:b?' => 'Limit results to those in followed categories. Cannot be used with the categoryID parameter.'
            ], 'in')
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
        } elseif ($this->session->isValid() && !empty($query['followed'])) {
            $followed = $this->categoryModel->getFollowed($this->session->UserID);
            $followedIDs = array_column($followed, 'CategoryID');
            $params['cat'] = $followedIDs;
        }

        $result = $this->getSearchModel()->modelSearch(
            $this->discussionModel,
            $query['query'],
            $params,
            $limit,
            $offset,
            $query['expand']
        );

        foreach ($result as &$row) {
            $row = $sender->normalizeOutput($row);
        }
        $result = $out->validate($result);
        return $result;
    }

    /**
     * Get the plugins copy of SphinxSearchModel.
     *
     * @return SphinxSearchModel
     */
    private function getSearchModel() {
        if (!isset($this->searchModel)) {
            $this->searchModel = $this->container->get(SearchModel::class);
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
