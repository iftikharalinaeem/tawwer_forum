<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Utility\CapitalCaseScheme;
use Vanilla\Utility\CamelCaseScheme;

/**
 * API Controller for the `/groups` resource.
 */
class GroupsApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $capitalCaseScheme;

    /** @var CamelCaseScheme */
    private $camelCaseScheme;

    /** @var GroupModel */
    private $groupModel;

    /** @var UserModel */
    private $userModel;

    /**
     * GroupsApiController constructor.
     *
     * @param GroupModel $groupModel
     * @param UserModel $userModel
     */
    public function __construct(
        GroupModel $groupModel,
        UserModel $userModel
    ) {
        $this->camelCaseScheme = new CamelCaseScheme();
        $this->capitalCaseScheme = new CapitalCaseScheme();
        $this->groupModel = $groupModel;
        $this->userModel = $userModel;
    }

    /**
     * Delete a group.
     *
     * @param int $id The ID of the group.
     * @throws ClientException
     */
    public function delete($id) {
        $this->permission('Garden.Group.Add');

        $this->idParamSchema()->setDescription('Delete a group.');
        $this->schema([], 'out');

        $row = $this->groupByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID && !$this->groupModel->checkPermission('Delete', $id)) {
            throw new ClientException('You do not have the rights to delete this group.');
        }

        // GroupModel->deleteID() won't do here.
        $this->groupModel->delete(['GroupID' => $id]);
    }

    /**
     * Get a schema instance comprised of all available group fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'groupID:i' => 'The ID of the group.',
                'name:s' => 'The name of the group.',
                'description:s' => 'The description of the group.',
                'format:s' => 'The input format of the group.',
                'iconUrl:s|n?' => 'The URL of the icon of the group.',
                'bannerUrl:s|n?' => 'The URL of the banner of the group.',
                'dateInserted:dt' => 'When the group was created.',
                'insertUserID:i' => 'The user that created the group.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateUpdated:dt|n' => 'When the group was updated.',
                'updateUserID:i|n' => 'The user that updated the group.',
                'updateUser?' => $this->getUserFragmentSchema(),
                'privacy:s' => [
                    'enum' => ['public', 'private'],
                    'description' => 'The privacy level of the group\'s content.',
                ],
                'dateLastComment:dt|n' => 'When the last comment was posted in the group.',
                'countMembers:i' => 'The number of user belonging to the group.',
                'countDiscussions:i' => 'The number of discussions in the group.',
                'url:s?' => 'The full URL to the group.',
            ], 'Group');
        }

        return $schema;
    }

    /**
     * Get a group by its numeric ID.
     *
     * @param int $id The group ID.
     * @throws NotFoundException If the group could not be found.
     * @return array
     */
    public function groupByID($id) {
        $row = $this->groupModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Group');
        }

        return $row;
    }

    /**
     * Get a group.
     *
     * @param int $id The ID of the group.
     * @throws Exception
     * @return array
     */
    public function get($id) {
        $this->permission();

        $this->idParamSchema()->setDescription('Get a group.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->groupByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID', 'UpdateUserID']);

        $row = $this->normalizeOutput($row);
        return $out->validate($row);
    }


    /**
     * Get a group for editing.
     *
     * @param int $id The ID of the group.
     * @throws NotFoundException if unable to find the group.
     * @throws ClientException
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamSchema()->setDescription('Get a group for editing.');
        $out = $this->schema(
            Schema::parse([
                'groupID', 'name', 'description', 'format', 'iconUrl', 'bannerUrl', 'privacy'
            ])->add($this->fullSchema()),
            'out'
        );

        $row = $this->groupByID($id);

        if ($row['InsertUserID'] !== $this->getSession()->UserID && !$this->groupModel->checkPermission('Edit', $id)) {
            throw new ClientException('You do not have the rights to edit this group.');
        }

        $result = $this->normalizeOutput($row);
        return $out->validate($result);
    }

    /**
     * Get an ID-only group record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamSchema() {
        return $this->schema(['id:i' => 'The group ID.'], 'in');
    }

    /**
     * List groups.
     *
     * @param array $query The query string.
     * @return array
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema([
            'memberID:i?' => 'Filter by member userID.',
            'sort:s?' => [
                'enum' => [
                    'dateInserted', '-dateInserted',
                    'dateLastComment', '-dateLastComment',
                    'countMembers', '-countMembers',
                    'countDiscussions', '-countDiscussions',
                ],
                'description' => 'Sort the results by the specified field. The default sort order is ascending.'
                    .'Prefixing the field with "-" will sort using a descending order.',
            ],
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => $this->groupModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('List groups.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $query = $in->validate($query);

        // Sorting
        $sortField = '';
        $sortOrder = 'asc';
        if (array_key_exists('sort', $query)) {
            $sortField = ltrim($query['sort'], '-');
            if (strlen($sortField) !== strlen($query['sort'])) {
                $sortOrder = 'desc';
            }
        }

        // Paging
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        // Filters
        $where = [];
        if (array_key_exists('memberID', $query)) {
            $userGroups = $this->groupModel->SQL->getWhere('UserGroup', $query['memberID'])->resultArray();
            $groupIDs  = array_column($userGroups, 'GroupID');

            if (empty($groupIDs)) {
                $where = null;
            } else {
                $where['GroupID'] = $groupIDs;
            }
        }

        // Data
        $rows = [];
        if ($where !== null) {
            $rows = $this->groupModel->getWhere($where, $sortField, $sortOrder, $limit, $offset)->resultArray();
        }

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($rows, ['InsertUserID', 'UpdateUserID']);
        }
        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows, true);
        return $result;
    }

    /**
     * Normalize a group Schema record to match the database definition.
     *
     * @param array $schemaRecord Group Schema record.
     * @return array Return a database record.
     */
    public function normalizeInput(array $schemaRecord) {
        if (array_key_exists('bannerUrl', $schemaRecord)) {
            $schemaRecord['banner'] = !empty($schemaRecord['bannerUrl']) ? $schemaRecord['bannerUrl'] : null;
        }
        if (array_key_exists('iconUrl', $schemaRecord)) {
            $schemaRecord['icon'] = !empty($schemaRecord['iconUrl']) ? $schemaRecord['iconUrl'] : null;
        }
        if (array_key_exists('privacy', $schemaRecord)) {
            $schemaRecord['privacy'] = ucfirst($schemaRecord['privacy']);
        }

        $dbRecord = $this->capitalCaseScheme->convertArrayKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize a group database record to match the schema definition.
     *
     * @param array $dbRecord Group database record.
     * @return array Return a schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        static $fieldToURL;
        if ($fieldToURL === null) {
            $fieldToURL = function(&$field) {
                if (!empty($field)) {
                    $url = Gdn_Upload::url($field);
                    if ($url) {
                        return $url;
                    }
                }
                return null;
            };
        }

        $dbRecord['Url'] = groupUrl($dbRecord);

        $dbRecord['BannerUrl'] = $fieldToURL($dbRecord['Banner']);
        $dbRecord['IconUrl'] = $fieldToURL($dbRecord['Icon']);

        $dbRecord['Privacy'] = strtolower($dbRecord['Privacy']);

        $schemaRecord = $this->camelCaseScheme->convertArrayKeys($dbRecord);

        return $schemaRecord;
    }

    /**
     * Update a group.
     *
     * @param int $id The ID of the group.
     * @param array $body The request body.
     * @throws NotFoundException If unable to find the group.
     * @throws ClientException
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamSchema();
        $in = $this->postSchema()->setDescription('Update a group.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body, true);

        $row = $this->groupByID($id);

        $groupData = $this->normalizeInput($body);
        $groupData['GroupID'] = $id;

        if ($row['InsertUserID'] !== $this->getSession()->UserID && !$this->groupModel->checkPermission('Edit', $id)) {
            throw new ClientException('You do not have the rights to edit this group.');
        }

        $this->groupModel->save($groupData);
        $this->validateModel($this->groupModel);

        $result = $this->groupByID($id);
        $this->userModel->expandUsers($result, ['InsertUserID', 'UpdateUserID']);

        $result = $this->normalizeOutput($result);
        return $out->validate($result);
    }

    /**
     * Create a group.
     *
     * @param array $body The request body.
     * @throws ServerException if the group could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Groups.Group.Add');

        $in = $this->postSchema()->setDescription('Create a group.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $groupData = $this->normalizeInput($body);

        $id = $this->groupModel->save($groupData);
        $this->validateModel($this->groupModel);

        if (!$id) {
            throw new ServerException('Unable to create group.', 500);
        }

        $row = $this->groupByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $result = $this->normalizeOutput($row);
        return $out->validate($result);
    }

    /**
     * Get a group schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postSchema() {
        static $postSchema;

        if ($postSchema === null) {
            $postSchema = $this->schema(
                Schema::parse(
                    ['name', 'description', 'format', 'iconUrl?', 'bannerUrl?', 'privacy']
                )->add($this->fullSchema()),
                'GroupPost'
            );
        }

        return $this->schema($postSchema, 'in');
    }
}
