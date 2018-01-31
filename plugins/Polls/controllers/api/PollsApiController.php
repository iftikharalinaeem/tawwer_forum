<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

use Vanilla\ApiUtils;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Data;
use Garden\Schema\Schema;

/**
 * Class PollsApiController
 */
class PollsApiController extends AbstractApiController {

    /** @var PollModel */
    private $pollModel;

    /** @var UserModel */
    private $userModel;

    /**
     * PollsApiController constructor.
     *
     * @param PollModel $pollModel
     */
    public function __construct(PollModel $pollModel, UserModel $userModel) {
        $this->pollModel = $pollModel;
        $this->userModel = $userModel;
    }

    /**
     * Delete a poll.
     *
     * @param int $id The unique ID of the poll.
     */
    public function delete($id) {
        $this->permission('Plugins.Polls.Manage');

        $this->idParamSchema('in')->setDescription('Delete a poll.');
        $this->schema([], 'out');

        $row = $this->pollByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $this->pollModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available poll fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'pollID:i' => 'The unique ID of the poll.',
                'name:s' => 'The name of the poll.',
                'discussionID:i' => 'The discussion the poll is displayed in.',
                'countOptions:i' => 'The number of options to choose from.',
                'countVotes:i' => 'The number of votes.',
                'insertUserID:i' => 'The unique ID of the user who created this poll.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the poll was created.',
                'updateUserID:i|n' => 'The unique ID of the user who updated this poll.',
                'updateUser?' => $this->getUserFragmentSchema(),
                'dateUpdated:dt|n' => 'When the poll was updated.',
            ]);
        }

        return $schema;
    }

    /**
     * Get a poll by its unique ID.
     *
     * @param int $id
     * @throws
     * @return array
     */
    public function pollByID($id) {
        $row = $this->pollModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Poll');
        }
        return $row;
    }

    /**
     * Get a poll schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function pollPostSchema() {
        static $pollPostSchema;

        if (!isset($pollPostSchema)) {
            $pollPostSchema = $this->schema(
                Schema::parse([
                    'name',
                    'discussionID',
                ])->add($this->fullSchema()),
                'PollPost'
            );
        }

        return $this->schema($pollPostSchema, 'in');
    }

    /**
     * Get a poll.
     *
     * @param int $id The unique ID of the poll.
     * @return array
     */
    public function get($id) {
        $this->permission();

        $this->idParamSchema()->setDescription('Get a poll.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->pollByID($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a poll for editing.
     *
     * @param int $id The ID of the poll.
     * @throws NotFoundException if the poll could not be found.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Plugins.Polls.Manage');

        $this->idParamSchema()->setDescription('Get a poll for editing.');
        $out = $this->schema(
            Schema::parse([
                'pollID',
                'name',
                'discussionID',
            ])->add($this->fullSchema()
        ), ['PollGetEdit', 'out']);

        $row = $this->pollByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only poll record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse(['id:i' => 'The poll ID.']);
        }

        return $this->schema($schema, 'in');
    }

    /**
     * List polls created by the current user.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema([
            'discussionID:i?' => [
                'description' => 'Filter by discussion.',
                'x-filter' => [
                    'field' => 'DiscussionID'
                ],
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => 25,
                'minimum' => 1,
                'maximum' => 100
            ],
            'insertUserID:i?' => [
                'description' => 'Filter by author.',
                'x-filter' => [
                    'field' => 'InsertUserID',
                ],
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'updateUser']),
        ], ['PollIndex', 'in'])->setDescription('List polls.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        $where = ApiUtils::queryToFilters($in, $query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->pollModel->getWhere($where, 'PollID', 'desc', $limit, $offset)->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'updateUser' => 'UpdateUserID'])
        );

        $rows = array_map([$this, 'normalizeOutput'], $rows);

        $result = $out->validate($rows, true);

        $whereCount = count($where);
        $isWhereOptimized = isset($where['insertUserID']);
        if ($whereCount === 0 || $isWhereOptimized) {
            $paging = ApiUtils::numberedPagerInfo($this->pollModel->getCount($where), '/api/v2/polls', $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($rows, '/api/v2/polls', $query, $in);
        }

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        return ApiUtils::convertOutputKeys($dbRecord);
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    private function normalizeInput(array $schemaRecord) {
        return ApiUtils::convertInputKeys($schemaRecord);
    }

    /**
     * Update a poll.
     *
     * @param int $id The unique ID of the poll.
     * @param array $body The request body.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Plugins.Polls.Manage');

        $this->idParamSchema();
        $in = $this->pollPostSchema()->setDescription('Update a poll.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->pollByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $body = $in->validate($body, true);
        $recordType = !empty($row['DiscussionID']) ? 'comment' : 'discussion';
        $pollData = $this->normalizeInput($body);
        $pollData['PollID'] = $id;
        $this->pollModel->save($pollData);
        $this->validateModel($this->pollModel);

        $updatedRow = $this->pollByID($id);
        $updatedRow = $this->normalizeOutput($updatedRow);

        $result = $out->validate($updatedRow);
        return $result;
    }

    /**
     * Create a poll.
     *
     * @param array $body The request body.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Plugins.Polls.Add');

        $in = $this->pollPostSchema()->setDescription('Create a poll.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $pollData = $this->normalizeInput($body);
        $pollID = $this->pollModel->save($pollData);
        $this->validateModel($this->pollModel);

        $row = $this->pollByID($pollID);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }
}
