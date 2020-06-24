<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/badges` resource.
 */
class BadgesApiController extends AbstractApiController {

    /** @var BadgeModel */
    private $badgeModel;

    /** @var UserBadgeModel */
    private $userBadgeModel;

    /** @var UserModel */
    private $userModel;

    /**
     * BadgesApiController constructor.
     *
     * @param BadgeModel $badgeModel
     * @param UserBadgeModel $userBadgeModel
     * @param UserModel $userModel
     */
    public function __construct(BadgeModel $badgeModel, UserBadgeModel $userBadgeModel, UserModel $userModel) {
        $this->badgeModel = $badgeModel;
        $this->userBadgeModel = $userBadgeModel;
        $this->userModel = $userModel;
    }

    /**
     * Get a badge by its numeric ID.
     *
     * @param int $id The badge ID.
     * @throws NotFoundException If the badge could not be found.
     * @return array
     */
    public function badgeByID($id) {
        $row = $this->badgeModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Badge');
        }

        return $row;
    }

    /**
     * Delete a badge.
     *
     * @param int $id The ID of the badge.
     * @throws ClientException
     */
    public function delete($id) {
        $this->permission('Reputation.Badges.Manage');

        $this->idParamBadgeSchema()->setDescription('Delete a custom badge.');
        $this->schema([], 'out');

        $badge = $this->badgeByID($id);
        if (!$badge['CanDelete']) {
            throw new ForbiddenException('This badge cannot be deleted.');
        }

        $this->badgeModel->deleteID($id);
    }

    /**
     * Delete the badge request of a user.
     *
     * @param int $id The ID of the badge.
     * @param int $userID The ID of the user.
     * @throws ClientException
     */
    public function delete_requests($id, $userID = null) {
        $this->permission('Reputation.Badges.Request');

        $in = $this->idParamBadgeRequestSchema()->setDescription('Delete the badge request of a user.');
        $this->schema([], 'out');

        $filtered = $in->validate(['id' => $id, 'userID' => $userID]);
        if (!empty($filtered['userID'])) {
            $userID = $filtered['userID'];
        } else {
            $userID = $this->getSession()->UserID;
        }

        if ($userID !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }

        $this->badgeByID($id);
        $this->userByID($userID);
        $this->getBadgeRequest($userID, $id);

        $this->userBadgeModel->deleteRequest($userID, $id);
    }

    /**
     * Remove a badge from a user.
     *
     * @param int $id The ID of the badge.
     * @param int $userID The ID of the user.
     * @throws ClientException
     */
    public function delete_users($id, $userID) {
        $this->permission('Reputation.Badges.Manage');

        $this->idParamUserBadgeSchema()->setDescription('Remove a badge from a user.');
        $this->schema([], 'out');

        $this->badgeByID($id);
        $this->userBadgeByID($userID, $id);

        $this->userBadgeModel->revoke($userID, $id);
    }

    /**
     * Get a schema instance comprised of all available Badge fields
     *
     * @return Schema
     */
    protected function fullBadgeSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'badgeID:i' => 'The ID of the badge.',
                'type:s' => 'The type of the badge. (Differentiate custom badges from the others)',
                'key:s' => 'They key of the badge.',
                'name:s' => 'The name of the badge.',
                'body:s?' => 'The description of the badge.',
                'photoUrl:s|n' => 'The photo of the badge.',
                'points:i' => 'The amount of point a badge is worth.',
                'enabled:b' => 'Tells whether the badge is enabled or not.',
                'canDelete:b' => 'Tells whether the badge can be deleted or not. (Only custom badges can be deleted right now)',
                'countUsers:i' => 'The number of users that have earned the badge.',
                'class:s|n?' => 'The class of the badge.',
                'classLevel:i|n' => 'The class level.',
                'insertUserID:i' => 'The user that created the badge.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the badge was created.',
                'updateUserID:i|n' => 'The last user that updated the badge.',
                'updateUser?' => $this->getUserFragmentSchema(),
                'dateUpdated:dt|n' => 'When the badge was last updated.',
                'attributes:o|n' => 'A free-form object containing all custom data for this badge.',
                'url:s' => 'The URL of the badge.',
            ], 'Badge');
        }

        return $schema;
    }

    /**
     * Get a schema instance comprised of all available UserBadge fields
     *
     * @return Schema
     */
    protected function fullBadgeRequestSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'userID:i' => 'The ID of the user.',
                'user?' => $this->getUserFragmentSchema(),
                'badgeID:i' => 'The ID of the badge.',
                'badge?' => $this->fullBadgeSchema(),
                'reasonBody:s|n' => 'Reason why the user wants the badge.',
                'insertUserID:i' => 'The user that created the user badge relation.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the user requested the badge.',
            ], 'BadgeRequest');
        }

        return $schema;
    }

    /**
     * Get a schema instance comprised of all available UserBadge fields
     *
     * @return Schema
     */
    protected function fullUserBadgeSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'userID:i' => 'The ID of the user.',
                'user?' => $this->getUserFragmentSchema(),
                'badgeID:i' => 'The ID of the badge.',
                'badge?' => $this->fullBadgeSchema(),
                'reasonBody:s|n' => 'Reason why the badge was given to the user.',
                'dateEarned:dt|n' => 'When the badge was earned.',
                'insertUserID:i' => 'The user that created the user badge relation.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the user badge relation was created.',
            ], 'UserBadge');
        }

        return $schema;
    }

    /**
     * Get a badge.
     *
     * @param int $id The ID of the badge.
     * @throws Exception
     * @return array
     */
    public function get($id) {
        // Individual badges can always be viewed.
        $this->permission();

        $this->idParamBadgeSchema()->setDescription('Get a badge.');
        $out = $this->schema($this->fullBadgeSchema(), 'out');

        $row = $this->badgeByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $row = $this->normalizeBadgeOutput($row);
        return $out->validate($row);
    }

    /**
     * Get a badge for editing.
     *
     * @param int $id The ID of the badge.
     * @throws NotFoundException if unable to find the badge.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Reputation.Badges.Manage');

        $this->idParamBadgeSchema()->setDescription('Get a badge for editing.');
        $out = $this->schema(
            Schema::parse([
                'badgeID',
                'name',
                'key',
                'body',
                'photoUrl?',
                'points',
                'class',
                'classLevel',
                'enabled',
            ])->add($this->fullBadgeSchema()),
            'out'
        );

        $row = $this->badgeByID($id);

        $result = $this->normalizeBadgeOutput($row);
        return $out->validate($result);
    }

    /**
     * List badge requests.
     *
     * @throws NotFoundException if unable to find the badge or user.
     * @param $query
     * @return array
     */
    public function get_requests(array $query) {
        $this->permission('Reputation.Badges.Give');

        $in = $this->schema(
            [
                'badgeID:i?' => 'Filter by badge ID',
                'userID:i?' => 'Filter by user ID',
                'page:i?' => [
                    'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                    'default' => 1,
                    'minimum' => 1,
                    'maximum' => 100
                ],
                'limit:i?' => [
                    'description' => 'Desired number of items per page.',
                    'default' => $this->badgeModel->getDefaultLimit(),
                    'minimum' => 1,
                    'maximum' => 100
                ],
                'expand?' => ApiUtils::getExpandDefinition(['user', 'badge', 'insertUser'])
            ], 'in')
            ->requireOneOf(['badgeID', 'userID'])
            ->setDescription('List badge requests.');
        $out = $this->schema([':a' => $this->fullBadgeRequestSchema()], 'out');

        $query = $in->validate($query);

        // Paging
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $where = [
            'Declined' => 0,
            'DateCompleted is null' => null,
            'DateRequested is not null' => null,
        ];
        if (array_key_exists('badgeID', $query)) {
            $currentBadge = $this->badgeByID($query['badgeID']);

            $where['BadgeID'] = $query['badgeID'];
        }
        if (array_key_exists('userID', $query)) {
            $this->userByID($query['userID']);

            $where['UserID'] = $query['userID'];
        }

        $rows = $this->userBadgeModel->getWhere($where, '', 'asc', $limit, $offset)->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'user' => 'UserID'])
        );

        $expandBadge = $this->isExpandField('badge', $query['expand']);
        $badges = [];
        if (isset($currentBadge)) {
            $badges[$currentBadge['BadgeID']] = $currentBadge;
        }

        foreach ($rows as &$row) {
            $badgeID = $row['BadgeID'];
            if ($expandBadge) {
                if (!isset($badges[$badgeID])) {
                    $badge = $this->badgeByID($badgeID);
                    $badges[$badgeID] = $badge;
                } else {
                    $badge = $badges[$badgeID];
                }
                $row['Badge'] = $badge;
            }
            $row = $this->normalizeUserBadgeOutput($row);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/badges/requests", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * List users/badges relation.
     *
     * @throws NotFoundException if unable to find the badge or user.
     * @param array $query
     * @return array
     */
    public function get_users(array $query) {
        $this->permission();

        $in = $this->schema(
            [
                'badgeID:i?' => 'Filter by badge ID',
                'userID:i?' => 'Filter by user ID',
                'page:i?' => [
                    'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                    'default' => 1,
                    'minimum' => 1,
                    'maximum' => 100
                ],
                'limit:i?' => [
                    'description' => 'Desired number of items per page.',
                    'default' => $this->badgeModel->getDefaultLimit(),
                    'minimum' => 1,
                    'maximum' => 100
                ],
                'expand?' => ApiUtils::getExpandDefinition(['user', 'badge', 'insertUser'])
            ], 'in')
            ->requireOneOf(['badgeID', 'userID'])
            ->setDescription('List all the users that have the badge.');
        $out = $this->schema([':a' => $this->fullUserBadgeSchema()], 'out');

        $query = $in->validate($query);

        // Paging
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $where = [
            'DateCompleted is not null' => null,
        ];
        if (array_key_exists('badgeID', $query)) {
            $currentBadge = $this->badgeByID($query['badgeID']);

            $where['BadgeID'] = $query['badgeID'];
        }
        if (array_key_exists('userID', $query)) {
            $this->userByID($query['userID']);

            $where['UserID'] = $query['userID'];
        }

        $rows = $this->userBadgeModel->getWhere($where, '', 'asc', $limit, $offset)->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'user' => 'UserID'])
        );

        $expandBadge = $this->isExpandField('badge', $query['expand']);
        $badges = [];
        if (isset($currentBadge)) {
            $badges[$currentBadge['BadgeID']] = $currentBadge;
        }

        foreach ($rows as &$row) {
            $badgeID = $row['BadgeID'];
            if ($expandBadge) {
                if (!isset($badges[$badgeID])) {
                    $badge = $this->badgeByID($badgeID);
                    $badges[$badgeID] = $badge;
                } else {
                    $badge = $badges[$badgeID];
                }
                $row['Badge'] = $badge;
            }
            $row = $this->normalizeUserBadgeOutput($row);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/badges/users", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get a user's badge request.
     *
     * @param int $userID The user ID.
     * @param int $badgeID The badge ID.
     * @throws NotFoundException If the badge could not be found.
     * @return array
     */
    public function getBadgeRequest($userID, $badgeID) {
        $row = $this->userBadgeModel->getWhere([
            'UserID' => $userID,
            'BadgeID' => $badgeID,
            'DateCompleted is null' => null,
            'DateRequested is not null' => null,
        ])->firstRow(DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('BadgeRequest');
        }

        return $row;
    }

    /**
     * List badges.
     *
     * @param array $query The query string.
     * @return array
     */
    public function index(array $query) {
        // You need this permission to list badges.
        $this->permission('Reputation.Badges.View');

        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => 100
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->badgeModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100
            ],
            'expand?' => ApiUtils::getExpandDefinition(['insertUser', 'updateUser'])
        ], 'in')->setDescription('List badges.');
        $out = $this->schema([':a' => $this->fullBadgeSchema()], 'out');

        $query = $this->filterValues($query);
        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $rows = $this->badgeModel->getWhere(false, '', 'asc', $limit, $offset)->resultArray();

        // Expand associated rows.
        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'updateUser' => 'UpdateUser'])
        );

        foreach ($rows as &$currentRow) {
            $currentRow = $this->normalizeBadgeOutput($currentRow);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::numberedPagerInfo(
            $this->badgeModel->getCount(),
            "/api/v2/badges",
            $query,
            $in
        );

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get an ID-only Badge record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamBadgeSchema() {
        return $this->schema(['id:i' => 'The badge ID.'], 'in');
    }

    /**
     * Get an ID-only BadgeRequest record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamBadgeRequestSchema() {
        return $this->schema(
            [
                'id:i' => 'The badge ID.',
                'userID:i?' => 'The user ID.',
            ],
            'in'
        );
    }

    /**
     * Get an ID-only UserBadge record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamUserBadgeSchema() {
        return $this->schema(
            [
                'id:i' => 'The badge ID.',
                'userID:i' => 'The user ID.',
            ],
            'in'
        );
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    public function normalizeBadgeInput(array $schemaRecord) {
        if (array_key_exists('photoUrl', $schemaRecord)) {
            $schemaRecord['photo'] = !empty($schemaRecord['photoUrl']) ? $schemaRecord['photoUrl'] : null;
        }
        if (array_key_exists('key', $schemaRecord)) {
            $schemaRecord['slug'] = $schemaRecord['key'];
        }
        if (array_key_exists('enabled', $schemaRecord)) {
            $schemaRecord['active'] = $schemaRecord['enabled'];
        }
        if (array_key_exists('classLevel', $schemaRecord)) {
            $schemaRecord['level'] = $schemaRecord['classLevel'];
        }
        if (!array_key_exists('type', $schemaRecord)) {
            $schemaRecord['type'] = 'Custom';
        }

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize a database record to match the schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a schema record.
     */
    public function normalizeBadgeOutput(array $dbRecord) {
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

        $dbRecord['Url'] = url('/badge/'.$dbRecord['BadgeID'].'/'.$dbRecord['Slug']);
        $dbRecord['PhotoUrl'] = $fieldToURL($dbRecord['Photo']);
        $dbRecord['Key'] = $dbRecord['Slug'];
        $dbRecord['Format'] = 'text';
        $dbRecord['Enabled'] = (bool)$dbRecord['Active'];
        $dbRecord['UpdateUserID'] = !empty($dbRecord['UpdateUser']) ? $dbRecord['UpdateUser'] : null;
        $dbRecord['CountUsers'] = $dbRecord['CountRecipients'];
        $dbRecord['ClassLevel'] = $dbRecord['Level'];

        if (!empty($dbRecord['Attributes'])) {
            if (!is_array($dbRecord['Attributes'])) {
                $dbRecord['Attributes'] = dbdecode($dbRecord['Attributes']);
            }
        }

        if (!empty($dbRecord['Threshold'])) {
            if (!is_array($dbRecord['Attributes'])) {
                $dbRecord['Attributes'] = [];
            }
            $dbRecord['Attributes']['Threshold'] = $dbRecord['Threshold'];
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Normalize a database record to match the schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a schema record.
     */
    public function normalizeBadgeRequestOutput(array $dbRecord) {
        if (array_key_exists('Badge', $dbRecord)) {
            $dbRecord['Badge'] = $this->normalizeBadgeOutput($dbRecord['Badge']);
        }
        $dbRecord['DateRequested'] = $dbRecord['DateRequested'];
        $dbRecord['ReasonBody'] = !empty($dbRecord['RequestReason']) ? $dbRecord['RequestReason'] : null;

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Normalize a database record to match the schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a schema record.
     */
    public function normalizeUserBadgeOutput(array $dbRecord) {
        if (array_key_exists('Badge', $dbRecord)) {
            $dbRecord['Badge'] = $this->normalizeBadgeOutput($dbRecord['Badge']);
        }
        $dbRecord['DateEarned'] = $dbRecord['DateCompleted'];
        $dbRecord['ReasonBody'] = !empty($dbRecord['Reason']) ? $dbRecord['Reason'] : null;

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Update a badge.
     *
     * @param int $id The ID of the badge.
     * @param array $body The request body.
     * @throws NotFoundException If unable to find the badge.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Reputation.Badges.Manage');

        $this->idParamBadgeSchema();
        $in = $this->postBadgeSchema()->setDescription('Update a badge.');
        $out = $this->schema($this->fullBadgeSchema(), 'out');

        $body = $in->validate($body, true);

        $this->badgeByID($id);

        $badgeData = $this->normalizeBadgeInput($body);
        $badgeData['BadgeID'] = $id;

        $this->badgeModel->save($badgeData);
        $this->validateModel($this->badgeModel);

        $result = $this->badgeByID($id);
        $this->userModel->expandUsers($result, ['InsertUserID']);

        $result = $this->normalizeBadgeOutput($result);
        return $out->validate($result);
    }

    /**
     * Create a badge.
     *
     * @throws ServerException If the badge could not be created.
     * @param array $body The request body.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Reputation.Badges.Manage');

        $in = $this->postBadgeSchema()->setDescription('Create a custom badge.');
        $out = $this->schema($this->fullBadgeSchema(), 'out');

        $body = $in->validate($body);
        $badgeData = $this->normalizeBadgeInput($body);

        $id = $this->badgeModel->save($badgeData);
        $this->validateModel($this->badgeModel);

        if (!$id) {
            throw new ServerException('Unable to create badge.', 500);
        }

        $row = $this->badgeByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $result = $this->normalizeBadgeOutput($row);
        return $out->validate($result);
    }

    /**
     * Give a badge.
     *
     * @throws ServerException If the badge could not be given.
     * @param int $id The ID of the badge.
     * @param array $body The request body.
     * @return array
     */
    public function post_users($id, array $body) {
        $this->permission('Reputation.Badges.Give');

        $this->idParamBadgeSchema();
        $in = $this->postUserBadgeSchema()->setDescription('Give a badge to a user.');
        $out = $this->schema($this->fullUserBadgeSchema(), 'out');

        $this->badgeByID($id);

        $body = $in->validate($body);
        $userID = $body['userID'];

        $success = $this->userBadgeModel->give($userID, $id, !empty($body['reasonBody']) ? $body['reasonBody'] : null);
        $this->validateModel($this->userBadgeModel);

        if (!$success) {
            throw new ServerException('Unable to give badge.', 500);
        }

        $row = $this->userBadgeByID($userID, $id);
        $this->userModel->expandUsers($row, ['UserID', 'InsertUserID']);

        // Get the fresh badge with updated count.
        $row['Badge'] = $this->badgeByID($id);
        $this->userModel->expandUsers($row['Badge'], ['InsertUserID']);

        $result = $this->normalizeUserBadgeOutput($row);
        return $out->validate($result);
    }

    /**
     * Request a badge.
     *
     * @throws ServerException If the badge could not be given.
     * @param int $id The ID of the badge.
     * @param array $body The request body.
     * @return array
     */
    public function post_requests($id, array $body) {
        $this->permission('Reputation.Badges.Request');

        $this->idParamBadgeSchema();
        $in = $this->postBadgeRequestSchema()->setDescription('Request a badge.');
        $out = $this->schema($this->fullBadgeRequestSchema(), 'out');

        $badge = $this->badgeByID($id);
        $userID = $this->getSession()->UserID;

        $body = $in->validate($body);
        $success = $this->userBadgeModel->request($userID, $id, !empty($body['reasonBody']) ? $body['reasonBody'] : null);
        $this->validateModel($this->userBadgeModel);

        if (!$success) {
            throw new ServerException('Unable to request badge.', 500);
        }

        $row = $this->getBadgeRequest($userID, $id);
        $this->userModel->expandUsers($row, ['UserID', 'InsertUserID']);

        $row['Badge'] = $badge;
        $this->userModel->expandUsers($row['Badge'], ['InsertUserID']);

        $result = $this->normalizeBadgeRequestOutput($row);
        return $out->validate($result);
    }

    /**
     * Get a badge schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postBadgeSchema() {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema(
                Schema::parse([
                    'name',
                    'key',
                    'body',
                    'photoUrl?',
                    'points',
                    'class',
                    'classLevel',
                    'enabled?' => [
                        'default' => true,
                    ]
                ])->add($this->fullBadgeSchema()),
                'BadgePost'
            );
        }

        return $this->schema($schema, 'in');
    }

    /**
     * Get a badge request schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postBadgeRequestSchema() {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema(
                Schema::parse([
                    'reasonBody?',
                ])->add($this->fullBadgeRequestSchema()),
                'BadgeRequestPost'
            );
        }

        return $this->schema($schema, 'in');
    }

    /**
     * Get a badge schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postUserBadgeSchema() {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema(
                Schema::parse([
                    'userID',
                    'reasonBody?',
                ])->add($this->fullUserBadgeSchema()),
                'UserBadgePost'
            );
        }

        return $this->schema($schema, 'in');
    }

    /**
     * Get a user's badge by its numeric ID.
     *
     * @param int $userID The user ID.
     * @param int $badgeID The badge ID.
     * @throws NotFoundException If the badge could not be found.
     * @return array
     */
    public function userBadgeByID($userID, $badgeID) {
        $row = $this->userBadgeModel->getByUser($userID, $badgeID);
        if (!$row || !empty($row['_New']) || empty($row['DateCompleted'])) {
            throw new NotFoundException('UserBadge');
        }

        return $row;
    }

    /**
     * Get a user by its numeric ID.
     *
     * @param int $id The user ID.
     * @throws NotFoundException if the user could not be found.
     * @return array
     */
    public function userByID($id) {
        $row = $this->userModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row || $row['Deleted'] > 0) {
            throw new NotFoundException('User');
        }
        return $row;
    }
}
