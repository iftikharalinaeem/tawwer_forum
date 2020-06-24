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

        $this->idParamSchema()->setDescription('Delete a poll.');
        $this->schema([], 'out');

        $row = $this->pollByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $this->pollModel->deleteID($id);
    }

    /**
     * Delete a poll option.
     *
     * @param int $id The unique ID of the poll.
     * @param int $pollOptionID The unique ID of the poll option.
     */
    public function delete_options($id, $pollOptionID) {
        $this->permission('Plugins.Polls.Manage');

        $this->idParamSchema();
        $this->idParamOptionSchema()->setDescription('Delete a poll.');
        $this->schema([], 'out');

        $this->pollByID($id);
        $row = $this->optionByID($pollOptionID);

        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->pollModel->deleteOptionID($pollOptionID);
    }

    /**
     * Delete a poll vote.
     *
     * @param int $id The unique ID of the poll.
     * @param array $query
     */
    public function delete_votes($id, array $query) {
        $this->permission('Plugins.Polls.Manage');

        $this->idParamSchema();
        $in = $this->schema([
            'userID:i?' => 'The user that voted. Defaults to the current user.',
        ])->setDescription('Delete a poll vote.');
        $this->schema([], 'out');

        $this->pollByID($id);

        $query = $in->validate($query);

        if (isset($query['userID']) && $query['userID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
            $userID = $query['userID'];
        } else {
            $userID = $this->getSession()->UserID;
        }

        $this->pollModel->deleteVote($id, $userID);
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
     * Get a schema instance comprised of all available poll option fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullOptionSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'pollOptionID:i' => 'The unique ID of the option.',
                'pollID:i' => 'The unique ID of the poll.',
                'body:s' => 'The name of the option.',
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
     * Get a schema instance comprised of all available poll vote fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullVoteSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'userID' => 'The user that made the vote',
                'pollOptionID:i' => 'The unique ID of the option.',
                'dateInserted:dt' => 'When the poll was created.',
            ]);
        }

        return $schema;
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
     * List polls' options.
     *
     * @param int $id The unique ID of the poll.
     * @param array $query The query string.
     * @throws NotFoundException if the poll or poll option could not be found.
     * @return Data
     */
    public function get_options($id, array $query) {
        $this->permission();

        // No page or limit since minimum is 2 and max is 12.
        $in = $this->schema([
            'pollOptionID:i?' => 'Filter by pollOptionID.',
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'updateUser']),
        ], ['PollOptionIndex', 'in'])->setDescription('List polls\' options.');
        $out = $this->schema([':a' => $this->fullOptionSchema()], 'out');

        $this->pollByID($id);

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        $rows = $this->pollModel->getOptions($id);

        if (isset($query['pollOptionID'])) {
            $pollOption = $rows[$query['pollOptionID']] ?? null;
            if ($pollOption) {
                $rows = [$pollOption];
            } else {
                throw new NotFoundException('PollOption');
            }
        } else {
            $rows = array_values($rows);
        }

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'updateUser' => 'UpdateUserID'])
        );

        $rows = array_map([$this, 'normalizeOptionOutput'], $rows);

        $result = $out->validate($rows);

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
     * List polls' votes.
     *
     * @param int $id The unique ID of the poll.
     * @param array $query The query string.
     * @throws NotFoundException if the poll or poll option could not be found.
     * @return Data
     */
    public function get_votes($id, array $query) {
        $this->permission();

        $in = $this->schema([
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
            'pollOptionID:i?' => 'Filter by pollOptionID.',
            'expand?' => ApiUtils::getExpandDefinition(['user']),
        ], ['PollVoteIndex', 'in'])->setDescription('List polls\' votes.');
        $out = $this->schema([':a' => $this->fullVoteSchema()], 'out');

        $this->pollByID($id);

        $pollOptions = $this->pollModel->getOptions($id);
        if (!$pollOptions) {
            return [];
        }

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $where = [];
        if (isset($query['pollOptionID'])) {
            if (!$pollOptions[$query['pollOptionID']]) {
                throw new NotFoundException('PollOption');
            }

            $where['PollOptionID'] = $query['pollOptionID'];
        } else {
            $where['PollOptionID'] = array_keys($pollOptions);
        }

        $rows = $this->pollModel->getVotesWhere($where, '', '', $limit, $offset)->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['user' => 'UserID'])
        );

        $rows = array_map([$this, 'normalizeVoteOutput'], $rows);

        $result = $out->validate($rows);

        return $result;
    }

    /**
     * Get an ID-only poll record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamOptionSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'pollOptionID' => 'The poll option ID',
            ]);
        }

        return $this->schema($schema, 'in');
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
     * List polls.
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

        $result = $out->validate($rows);

        $whereCount = count($where);
        $isWhereOptimized = isset($where['insertUserID']);
        if ($whereCount === 0 || $isWhereOptimized) {
            $paging = ApiUtils::numberedPagerInfo($this->pollModel->getCount($where), '/api/v2/polls', $query, $in);
        } else {
            $paging = ApiUtils::morePagerInfo($rows, '/api/v2/polls', $query, $in);
        }

        return new Data($result, ['paging' => $paging]);;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOptionOutput(array $dbRecord) {
        return ApiUtils::convertOutputKeys($dbRecord);
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
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    private function normalizeOptionInput(array $schemaRecord) {
        return ApiUtils::convertInputKeys($schemaRecord);
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    private function normalizeVoteInput(array $schemaRecord) {
        return ApiUtils::convertInputKeys($schemaRecord);
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeVoteOutput(array $dbRecord) {
        return ApiUtils::convertOutputKeys($dbRecord);
    }

    /**
     * Get a poll option by its unique ID.
     *
     * @param int $id Identifier of the record.
     * @throws NotFoundException if the record does not exists.
     * @return array
     */
    public function optionByID($id) {
        $row = $this->pollModel->getOptionID($id);
        if (!$row) {
            throw new NotFoundException('PollOption');
        }
        return $row;
    }

    /**
     * Get a poll schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function optionPostSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = $this->schema(
                Schema::parse([
                    'body',
                ])->add($this->fullOptionSchema()),
                'PollOptionPost'
            );
        }

        return $this->schema($schema, 'in');
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
        $data = $this->normalizeInput($body);
        $data['PollID'] = $id;

        $this->pollModel->save($data);
        $this->validateModel($this->pollModel);

        $updatedRow = $this->pollByID($id);
        $updatedRow = $this->normalizeOutput($updatedRow);

        $result = $out->validate($updatedRow);
        return $result;
    }

    /**
     * Update a poll option.
     *
     * @param int $id The unique ID of the poll.
     * @param int $pollOptionID The unique ID of the poll option.
     * @param array $body The request body.
     * @return array
     */
    public function patch_options($id, $pollOptionID, array $body) {
        $this->permission('Plugins.Polls.Manage');

        $this->idParamSchema();
        $this->idParamOptionSchema();
        $in = $this->optionPostSchema()->setDescription('Update a poll option.');
        $out = $this->schema($this->fullOptionSchema(), 'out');

        $row = $this->optionByID($id);
        if ($row['InsertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $body = $in->validate($body, true);
        $body['pollOptionID'] = $pollOptionID;
        $data = $this->normalizeInput($body);

        $this->pollModel->saveOption($id, $data);
        $this->validateModel($this->pollModel);

        $updatedRow = $this->optionByID($pollOptionID);
        $updatedRow = $this->normalizeOutput($updatedRow);

        $result = $out->validate($updatedRow);
        return $result;
    }

    /**
     * Get a poll by its unique ID.
     *
     * @param int $id Identifier of the record.
     * @throws NotFoundException if the record does not exists.
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
        static $schema;

        if (!isset($schema)) {
            $schema = $this->schema(
                Schema::parse([
                    'name',
                    'discussionID',
                ])->add($this->fullSchema()),
                'PollPost'
            );
        }

        return $this->schema($schema, 'in');
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

    /**
     * Create a poll option.
     *
     * @param array $body The request body.
     * @return array
     */
    public function post_options($id, array $body) {
        $this->permission('Plugins.Polls.Add');

        $in = $this->optionPostSchema()->setDescription('Create a poll option.');
        $out = $this->schema($this->fullOptionSchema(), 'out');

        $this->pollByID($id);

        $body = $in->validate($body);

        $optionData = $this->normalizeOptionInput($body);
        $optionID = $this->pollModel->saveOption($id, $optionData);
        $this->validateModel($this->pollModel);

        $row = $this->optionByID($optionID);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Vote for a poll option.
     *
     * @param array $body The request body.
     * @return array
     */
    public function post_votes($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamSchema();
        $this->idParamOptionSchema();
        $in = $this->votePostSchema()->setDescription('Vote for a poll option.');
        $out = $this->schema($this->fullVoteSchema(), 'out');

        $this->pollByID($id);

        $body = $in->validate($body);

        $this->optionByID($body['pollOptionID']);

        if (isset($body['userID']) && $body['userID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
            $userID = $body['userID'];
        } else {
            $userID = $this->getSession()->UserID;
        }

        $this->pollModel->vote($body['pollOptionID'], $userID);
        $this->validateModel($this->pollModel);

        $row = $this->pollModel->getVotesWhere([
            'pollOptionID' => $body['pollOptionID'],
            'userID' => $userID,
        ])->firstRow(DATASET_TYPE_ARRAY);
        $row = $this->normalizeVoteOutput($row);

        $result = $out->validate($row);
        return $result;
    }



    /**
     * Get a poll vote schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function votePostSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = $this->schema(
                Schema::parse([
                    'userID?',
                    'pollOptionID',
                ])->add($this->fullVoteSchema()),
                'PollVotePost'
            );
        }

        return $this->schema($schema, 'in');
    }
}
