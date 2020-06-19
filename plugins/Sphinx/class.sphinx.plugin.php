<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Container\Container;
use Garden\Schema\Schema;
use Psr\Container\ContainerInterface;
use Vanilla\Contracts\Search\SearchRecordTypeProviderInterface;
use Vanilla\Sphinx\Models\SearchRecordTypeProvider;
use Vanilla\Sphinx\Models\SearchRecordTypeDiscussion;
use Vanilla\Sphinx\Models\SearchRecordTypeComment;

/**
 * Sphinx Plugin
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @package internal
 */
class SphinxPlugin extends Gdn_Plugin {
   const PROVIDER_GROUP = 'sphinx';

    /** The highest value for "limit" in the default, generic schema. */
    const MAX_SCHEMA_LIMIT = 100;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var ContainerInterface */
    private $container;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var array */
    private $originalDiscussionAttributes = [];

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
    }

    /**
     * Override the search model with the sphinx search model.
     *
     * @param Container $dic The container to initialize.
     */
    public function container_init(Container $dic) {
        $dic->rule(SearchRecordTypeProviderInterface::class)
            ->setClass(SearchRecordTypeProvider::class) //this is kludge
            ->addCall('setType', [new SearchRecordTypeDiscussion()])
            ->addCall('setType', [new SearchRecordTypeComment()])
            ->addCall('addProviderGroup', [SearchRecordTypeDiscussion::PROVIDER_GROUP])
            ->addCall('addProviderGroup', [self::PROVIDER_GROUP])
            ->addAlias('SearchRecordTypeProvider')
            ->rule(\SearchModel::class)
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
        $sender->permission();

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
            'discussion_d' => 1,
            'types' => [new SearchRecordTypeDiscussion()]
        ];

        if (array_key_exists('categoryID', $query)) {
            $params['cat'] = $query['categoryID'];
        } elseif ($this->session->isValid() && !empty($query['followed'])) {
            $params['followedcats'] = $query['followed'];
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

    /**
     * Hook into single-field discussion updates.
     *
     * @param DiscussionModel $sender
     * @param array $args
     * @return void
     */
    public function discussionModel_afterSetField_handler($sender, array $args = []): void {
        if (isset($args['SetField']['CategoryID'])) {
            $this->scheduleDiscussionUpdate($args["DiscussionID"] ?? null);
        }
    }

    /**
     * Hook into full-record discussion updates.
     *
     * @param DiscussionModel $sender
     * @param array $args
     * @return void
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, array $args = []): void {
        $formPostValues = $args["FormPostValues"] ?? null;
        $insert = $args["Insert"] ?? null;
        $formDiscussionID = $formPostValues["DiscussionID"] ?? null;
        $formCategoryID = $formPostValues["CategoryID"] ?? null;

        // Attempt to determine if this is a discussion update. Bail out of it isn't.
        if ($insert === true || $formDiscussionID === null || $formCategoryID === null) {
            return;
        }

        // Make sure relevant fields have changed enough to warrant an update.
        $originalAttributes = $this->originalDiscussionAttributes[$formDiscussionID] ?? [];
        $originalCategoryID = $originalAttributes["CategoryID"] ?? null;
        if ($formCategoryID == $originalCategoryID) {
            return;
        }

        $this->scheduleDiscussionUpdate($args["DiscussionID"] ?? null);
    }

    /**
     * Hook in before a discussion is saved.
     *
     * @param DiscussionModel $sender
     * @param array $args
     * @return void
     */
    public function discussionModel_beforeSaveDiscussion_handler($sender, array $args = []): void {
        $discussionID = $args["DiscussionID"] ?? null;
        if ($discussionID === null) {
            return;
        }

        // If we already have the original attributes for this discussion, do not proceed.
        if (array_key_exists($discussionID, $this->originalDiscussionAttributes)) {
            return;
        }

        /** @var DiscussionModel $discussionModel */
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            // Nothing to do here. This discussion isn't valid.
            return;
        }

        // Record the relevant attributes for comparison at a later time.
        $this->originalDiscussionAttributes[$discussionID] = [
            "CategoryID" => $discussion["CategoryID"],
        ];
    }

    /**
     * Refresh specific attributes of a discussion and its comments.
     *
     * @param integer $discussionID
     * @return void
     */
    private function refreshDiscussionAttributes(int $discussionID): void {
        /** @var DiscussionModel $discussionModel */
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            return;
        }

        // Update the discussion in all relevant indexes.
        $discussionDocumentID = (intval($discussionID) * 10) + 1;
        foreach ($this->getSearchModel()->indexes(["Discussion"]) as $discussionIndex) {
            $this->getSearchModel()->sphinxClient()->updateAttributes(
                $discussionIndex,
                ["CategoryID"],
                [
                    $discussionDocumentID => [
                        $discussion["CategoryID"]
                    ]
                ]
            );
        }

        // Build a list of attributes to update, per-comment.
        /** @var CommentModel $commentModel */
        $commentModel = Gdn::getContainer()->get(CommentModel::class);
        $comments = $commentModel->getWhere(["DiscussionID" => $discussionID]);
        $commentUpdates = [];
        foreach ($comments->resultArray() as $comment) {
            $commentDocumentID = (intval($comment["CommentID"]) * 10) + 2;
            $commentUpdates[$commentDocumentID] = [$discussion["CategoryID"]];
        }

        // Update discussion comments in all relevant indexes.
        foreach ($this->getSearchModel()->indexes(["Comment"]) as $commentIndex) {
            $this->getSearchModel()->sphinxClient()->updateAttributes(
                $commentIndex,
                ["CategoryID"],
                $commentUpdates
            );
        }
    }

    /**
     * Schedule update of a discussion's attributes.
     *
     * @param integer|null $discussionID
     * @return void
     */
    private function scheduleDiscussionUpdate(?int $discussionID): void {
        static $pending = [];

        if (!$discussionID || in_array($discussionID, $pending)) {
            return;
        }

        /** @var Vanilla\Scheduler\SchedulerInterface $scheduler */
        $scheduler = Gdn::getContainer()->get(Vanilla\Scheduler\SchedulerInterface::class);
        $scheduler->addJob(
            Vanilla\Scheduler\Job\CallbackJob::class,
            [
                "callback" => function () use ($discussionID) {
                    $this->refreshDiscussionAttributes($discussionID);
                }
            ]
        );

        $pending[] = $discussionID;
    }
}
