<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Groups\Models\GroupPermissions;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\CapitalCaseScheme;
use Vanilla\Utility\InstanceValidatorSchema;

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

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var FormatService */
    private $formatService;

    /**
     * DI.
     * @inheritdoc
     */
    public function __construct(
        GroupModel $groupModel,
        UserModel $userModel,
        BreadcrumbModel $breadcrumbModel,
        FormatService $formatService
    ) {
        $this->groupModel = $groupModel;
        $this->userModel = $userModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->formatService = $formatService;

        $this->camelCaseScheme = new CamelCaseScheme();
        $this->capitalCaseScheme = new CapitalCaseScheme();
    }

    /**
     * Delete a group.
     *
     * @param int $id The ID of the group.
     * @throws ClientException
     */
    public function delete($id) {
        $this->permission('Groups.Group.Add');

        $this->idParamGroupSchema()->setDescription('Delete a group.');
        $this->schema([], 'out');


        $this->groupModel->checkGroupPermission(GroupPermissions::DELETE, $id);

        // GroupModel->deleteID() won't do here since it does not delete all the group's data.
        $this->groupModel->delete(['GroupID' => $id]);
    }

    /**
     * Delete an invite to a user from a group.
     *
     * @throws ClientException
     * @throws NotFoundException If unable to find the group.
     * @param int $id The group ID.
     * @param int $userID The group  member user ID.
     */
    public function delete_invites($id, $userID) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupMemberSchema(false)->setDescription('Delete an invite to a user from a group.');
        $this->schema([], 'out');

        $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id);
        $this->groupModel->deleteInvites($id, $userID);
    }

    /**
     * Remove a user from a group or leave a group.
     *
     * @throws ClientException
     * @throws NotFoundException If unable to find the group.
     * @param int $id The group ID.
     * @param int|null $userID The group  member user ID.
     */
    public function delete_members($id, $userID = null) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupMemberSchema(true);
        $userIn = $this->schema([
            'userID:i?' => 'The group member user ID.'
        ], 'in')->setDescription('Remove a user from a group or leave a group.');
        $this->schema([], 'out');

        $filtered = $userIn->validate(['userID' => $userID]);
        if (!empty($filtered['userID'])) {
            $userID = $filtered['userID'];
        } else {
            $userID = $this->getSession()->UserID;
        }

        $this->leaveGroup($id, $userID);
    }

    /**
     * Get a schema instance comprised of all available group application fields
     *
     * @return Schema
     */
    protected function fullGroupApplicantSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'userID:i' => 'The user ID of the applicant.',
                'user?' => $this->getUserFragmentSchema(),
                'status:s' => [
                    'enum' => ['approved', 'denied', 'pending'],
                    'description' => 'The status of the applicant.',
                ],
                'reason:s' => 'The reason why the applicant wants to join the group.',
                'body:s' => 'Universal record field. Content of "reason".',
                'dateInserted:dt' => 'When the applicant was created.',
            ], 'GroupApplicant');
        }

        return $schema;
    }

    /**
     * Get a schema instance comprised of all available group invites fields
     *
     * @return Schema
     */
    protected function fullGroupInviteSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'userID:i' => 'The ID of the user that has been invited.',
                'user?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the invite was issued.',
                'insertUserID:i' => 'The user that created the invite.',
                'insertUser?' => $this->getUserFragmentSchema(),
            ], 'GroupInvite');
        }

        return $schema;
    }

    /**
     * Get a schema instance comprised of all available group member fields.
     *
     * @return Schema
     */
    protected function fullGroupMemberSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'userID:i' => 'The user ID of the member of the group.',
                'user?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the user was added to the group.',
                'insertUserID:i' => 'The user that added this user to the group.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'role:s' => [
                    'enum' => ['leader', 'member'],
                    'description' => 'The role of the user for that group.',
                ],
            ], 'GroupMember');
        }

        return $schema;
    }

    /**
     * Get a schema instance comprised of all available group fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullGroupSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'groupID:i' => 'The ID of the group.',
                'name:s' => 'The name of the group.',
                'description:s' => 'The description of the group.',
                'body:s' => 'Universal record field. Content of description.',
                'iconUrl:s|n?' => 'The URL of the icon of the group.',
                'bannerUrl:s|n?' => 'The URL of the banner of the group.',
                'dateInserted:dt' => 'When the group was created.',
                'insertUserID:i' => 'The user that created the group.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateUpdated:dt|n' => 'When the group was updated.',
                'updateUserID:i|n' => 'The user that updated the group.',
                'updateUser?' => $this->getUserFragmentSchema(),
                'privacy:s' => [
                    'enum' => ['public', 'private', 'secret'],
                    'description' => 'The privacy level of the group\'s content.',
                ],
                'dateLastComment:dt|n' => 'When the last comment was posted in the group.',
                'countMembers:i' => 'The number of user belonging to the group.',
                'countDiscussions:i' => 'The number of discussions in the group.',
                'url:s' => 'The full URL to the group.',
                'breadcrumbs:a' => new InstanceValidatorSchema(Breadcrumb::class),
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

        $this->idParamGroupSchema()->setDescription('Get a group.');
        $out = $this->schema($this->fullGroupSchema(), 'out');

        $row = $this->groupByID($id);
        $this->verifyAccess($row);
        $this->userModel->expandUsers($row, ['InsertUserID', 'UpdateUserID']);

        $row = $this->normalizeGroupOutput($row);

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

        $this->idParamGroupSchema()->setDescription('Get a group for editing.');
        $out = $this->schema(
            Schema::parse([
                'groupID',
                'name',
                'description',
                'format' => new \Vanilla\Models\FormatSchema(true),
                'iconUrl',
                'bannerUrl',
                'privacy'
            ])->add($this->fullGroupSchema()),
            'out'
        );

        $group = $this->groupByID($id);

        $this->groupModel->checkGroupPermission(GroupPermissions::EDIT, $id);

        $result = $this->normalizeGroupOutput($group, ['skipFormatting' => true]);
        return $out->validate($result);
    }

    /**
     * List the invites for a group.
     *
     * @param int $id The ID of the group.
     * @param array $query
     * @throws ClientException
     * @throws NotFoundException if unable to find the group.
     * @return array
     */
    public function get_invites($id, array $query) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupSchema();
        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->groupModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('List the invites for a group.');
        $out = $this->schema([':a' => $this->fullGroupInviteSchema()], 'out');

        $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id);

        $query = $in->validate($query);

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $invites = $this->groupModel->getApplicants($id, ['Type' => 'Invitation'], $limit, $offset, false);

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($invites, ['UserID', 'InsertUserID']);
        }

        $result = $out->validate($invites);

        $paging = ApiUtils::numberedPagerInfo(
            $this->groupModel->getApplicantsCount($id, ['Type' => 'Invitation']),
            "/api/v2/groups/$id/invites",
            $query,
            $in
        );

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * List the applicants to a group.
     *
     * @param int $id The ID of the group.
     * @param array $query
     * @throws ClientException
     * @throws NotFoundException if unable to find the group.
     * @return array
     */
    public function get_applicants($id, array $query) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupSchema();
        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->groupModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('List applicants to a group.');
        $out = $this->schema([':a' => $this->fullGroupApplicantSchema()], 'out');

        $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id);

        $query = $in->validate($query);

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $applicants = $this->groupModel->getApplicants($id, ['Type' => 'Application'], $limit, $offset, false);

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($applicants, ['UserID', 'InsertUserID']);
        }

        foreach ($applicants as &$applicant) {
            $applicant = $this->normalizeGroupApplicantOutput($applicant);
        }
        unset($applicant);

        $result = $out->validate($applicants);

        $paging = ApiUtils::numberedPagerInfo(
            $this->groupModel->getApplicantsCount($id, ['Type' => 'Application']),
            "/api/v2/groups/$id/applicants",
            $query,
            $in
        );

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * List the members of a group.
     *
     * @param int $id The ID of the group.
     * @param array $query
     * @throws NotFoundException if unable to find the group.
     * @throws ClientException
     * @return array
     */
    public function get_members($id, array $query) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupSchema();
        $in = $this->schema([
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->groupModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('List members of a group.');
        $out = $this->schema([':a' => $this->fullGroupMemberSchema()], 'out');


        $this->groupModel->checkGroupPermission(GroupPermissions::VIEW, $id);

        $query = $in->validate($query);

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $members = $this->groupModel->getMembers($id, [], $limit, $offset, false);

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($members, ['UserID']);
        }

        $members = array_map([$this, 'normalizeGroupMemberOutput'], $members);

        $result = $out->validate($members);

        $paging = ApiUtils::morePagerInfo($members, "/api/v2/groups/$id/members", $query, $in);

        return new Data($result, ['paging' => $paging]);

    }

    /**
     * Get an IDs-only group member record schema.
     *
     * @param bool $isUserIDOptional
     * @return Schema Returns a schema object.
     */
    public function idParamGroupMemberSchema($isUserIDOptional) {
        return $this->schema([
            'id:i' => 'The group ID.',
            'userID:i'.($isUserIDOptional ? '?' : '') => 'The group member user ID.',
        ], 'in');
    }


    /**
     * Get an ID-only group record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamGroupSchema() {
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
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->groupModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('List groups.');
        $out = $this->schema([':a' => $this->fullGroupSchema()], 'out');

        $query = $in->validate($query);

        // Sorting
        [$sortField, $sortOrder] = $this->resultSorting($query);

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        // Default filters
        $where = [];

        // If the current user is not a Groups global moderator, limit view to only public and private groups.
        if ($this->groupModel->isModerator() === false) {
            $where['Privacy'] = ['Public', 'Private'];
        }

        if (array_key_exists('memberID', $query)) {
            $userGroups = $this->groupModel->SQL->getWhere(
                'UserGroup',
                ['UserID' => $query['memberID']]
            )->resultArray();
            $ids  = array_column($userGroups, 'GroupID');

            if (!empty($ids)) {
                $where['GroupID'] = $ids;
                if ($query['memberID'] === $this->getSession()->UserID) {
                    unset($where['Privacy']); // The user should be able to see all the groups they're a member of.
                }
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
            $row = $this->normalizeGroupOutput($row);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::numberedPagerInfo($this->groupModel->getCount(), "/api/v2/groups", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Leave group implementation.
     *
     * @param int $id
     * @param int $userID
     */
    private function leaveGroup(int $id, int $userID) {

        if ($userID !== $this->getSession()->UserID) {
            $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id, $userID);
        } else {
            $this->groupModel->checkGroupPermission(GroupPermissions::LEAVE, $id, $userID);
        }

        $this->groupModel->removeMember($id, $userID);
    }

    /**
     * Normalize a group applicant database record to match the Schema definition.
     *
     * @param array $dbRecord Group Applicant database record.
     * @return array Return a Schema record.
     */
    public function normalizeGroupApplicantOutput(array $dbRecord) {
        if (in_array($dbRecord['Type'], ['Approved', 'Denied'])) {
            $dbRecord['Status'] = $this->camelCaseScheme->convert($dbRecord['Type']);
        } else {
            $dbRecord['Status'] = 'pending';
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        $schemaRecord['body'] = $schemaRecord['reason'];

        return $schemaRecord;

    }

    /**
     * Normalize a group Schema record to match the database definition.
     *
     * @param array $schemaRecord Group Schema record.
     * @return array Return a database record.
     */
    public function normalizeGroupInput(array $schemaRecord) {
        if (array_key_exists('bannerUrl', $schemaRecord)) {
            $schemaRecord['banner'] = !empty($schemaRecord['bannerUrl']) ? $schemaRecord['bannerUrl'] : null;
        }
        if (array_key_exists('iconUrl', $schemaRecord)) {
            $schemaRecord['icon'] = !empty($schemaRecord['iconUrl']) ? $schemaRecord['iconUrl'] : null;
        }
        if (array_key_exists('privacy', $schemaRecord)) {
            $schemaRecord['privacy'] = ucfirst($schemaRecord['privacy']);
        }

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize a group member Schema record to match the database definition.
     *
     * @param array $schemaRecord Group Member Schema record.
     * @return array Return a database record.
     */
    public function normalizeGroupMemberInput(array $schemaRecord) {
        $schemaRecord['role'] = $this->capitalCaseScheme->convert($schemaRecord['role']);

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize a group member database record to match the schema definition.
     *
     * @param array $dbRecord Group database record.
     * @return array Return a schema record.
     */
    public function normalizeGroupMemberOutput(array $dbRecord) {
        $dbRecord['Role'] = $this->camelCaseScheme->convert($dbRecord['Role']);

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);

        return $schemaRecord;
    }

    /**
     * Normalize a group database record to match the schema definition.
     *
     * @param array $dbRecord Group database record.
     * @return array Return a schema record.
     */
    public function normalizeGroupOutput(array $dbRecord, $options = []) {
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

        if (empty($options['skipFormatting'])) {
            $dbRecord['excerpt'] = $this->formatService->renderExcerpt($dbRecord['Description'], $dbRecord['Format']);
            $dbRecord['Description'] = $this->formatService->renderHTML($dbRecord['Description'], $dbRecord['Format']);
            $dbRecord['Body'] = $dbRecord['Description'];
        }

        $result = ApiUtils::convertOutputKeys($dbRecord);
        $result['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new GroupRecordType($result['groupID']));
        return $result;
    }

    /**
     * Get a member from a group.
     * Assume that the group exists.
     *
     * @throws NotFoundException If the member does not exist.
     * @param $id
     * @param $userID
     * @return array Member information.
     */
    public function memberByID($id, $userID) {
        $row = $this->groupModel->getMember($id, $userID);
        if (!$row) {
            throw new NotFoundException('Group Member');
        }

        $this->userModel->expandUsers($row, ['userID']);

        return $row;
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

        $this->idParamGroupSchema();
        $in = $this->postGroupSchema()->setDescription('Update a group.');
        $out = $this->schema($this->fullGroupSchema(), 'out');

        $body = $in->validate($body, true);

        $groupData = $this->normalizeGroupInput($body);
        $groupData['GroupID'] = $id;

        $this->groupModel->checkGroupPermission(GroupPermissions::EDIT, $id);

        $this->groupModel->save($groupData);
        $this->validateModel($this->groupModel);

        $result = $this->groupByID($id);
        $this->userModel->expandUsers($result, ['InsertUserID', 'UpdateUserID']);

        $result = $this->normalizeGroupOutput($result);
        return $out->validate($result);
    }

    /**
     * Approve or deny a group applicant.
     *
     * @throws ClientException
     * @throws NotFoundException If the applicant was not found.
     * @throws ServerException
     * @param int $id
     * @param int $userID
     * @param array $body
     * @return array
     */
    public function patch_applicants($id, $userID, array $body) {
        $this->permission('Garden.SignInAllow');

        $this->idParamGroupMemberSchema(false);
        $in = $this->schema([
            'status:s' => [
                'enum' => ['approved', 'denied'],
                'description' => 'The status of the applicant.',
            ]
        ], 'in')->setDescription('Approve or deny a group applicant.');
        $out = $this->schema($this->fullGroupApplicantSchema(), 'out');


        $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id);

        $applicants = $this->groupModel->getApplicants($id, ['Type' => 'Application', 'UserID' => $userID], false, false, false);
        if (count($applicants) === 0) {
            throw new NotFoundException('GroupApplicant');
        }
        $applicant = array_pop($applicants);

        $body = $in->validate($body);

        $isApproved = $body['status'] === 'approved';

        if (!$this->groupModel->processApplicant($id, $userID, $isApproved)) {
            throw new ServerException('Unable to update the applicant.', 500);
        }

        // If an applicant is approved the record is deleted so lets use the fetched record and update the status.
        $applicant = $this->normalizeGroupApplicantOutput($applicant);
        $applicant['status'] = $isApproved ? 'approved' : 'denied';
        return $out->validate($applicant);
    }

    /**
     * Update a group member.
     *
     * @throws ClientException
     * @param int $id The ID of the group.
     * @param int $userID The user ID of the member of the group.
     * @param array $body
     * @return array The updated group member.
     */
    public function patch_members($id, $userID, array $body) {
        $this->permission('Garden.SignInAllow');

        $this->idParamGroupMemberSchema(false);
        $in = $this->schema([
            'role:s' => [
                'enum' => ['leader', 'member'],
                'description' => 'The role of the user for that group.',
            ],
        ])->setDescription('Change a user\'s role within a group.');
        $out = $this->schema($this->fullGroupMemberSchema(), 'out');

        $this->memberByID($id, $userID);

        $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id);

        $body = $in->validate($body);

        $memberData = $this->normalizeGroupMemberInput($body);

        // We currently only allow role to be updated.
        $this->groupModel->setMemberRole($id, $userID, $memberData['Role']);

        $user = $this->memberByID($id, $userID);
        $this->userModel->expandUsers($user, ['UserID', 'InsertUserID']);
        $user = $this->normalizeGroupMemberOutput($user);

        return $out->validate($user);
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

        $in = $this->postGroupSchema()->setDescription('Create a group.');
        $out = $this->schema($this->fullGroupSchema(), 'out');

        $body = $in->validate($body);
        $groupData = $this->normalizeGroupInput($body);

        $id = $this->groupModel->save($groupData);
        $this->validateModel($this->groupModel);

        if (!$id) {
            throw new ServerException('Unable to create group.', 500);
        }

        $row = $this->groupByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $result = $this->normalizeGroupOutput($row);
        return $out->validate($result);
    }

    /**
     * Apply to a group.
     *
     * @throws ServerException
     * @param int $id
     * @param array $body
     * @return array
     */
    public function post_applicants($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->postGroupSchema();
        $in = $this->schema([
            'reason:s' => [
                'maxLength' => 200,
                'description' => 'The reason why the user wants to apply to this group.',
            ]
        ])->setDescription('Apply to a private group.');
        $out = $this->schema($this->fullGroupApplicantSchema(), 'out');

        $group = $this->groupByID($id);
        $this->verifyAccess($group);

        $body = $in->validate($body);

        $userID = $this->getSession()->UserID;
        if (!$this->groupModel->apply($id, $userID, $body['reason'])) {
            throw new ServerException('Unable to apply.', 500);
        }

        $applicants = $this->groupModel->getApplicants($id, ['UserID' => $userID], false, false, false);

        $applicant = $this->normalizeGroupApplicantOutput(array_pop($applicants));
        return $out->validate($applicant);
    }

    /**
     * Apply to a group. Convenience method that points to post_applicants.
     *
     * @throws ServerException
     * @param int $id
     * @param array $body
     * @return array
     */
    public function post_apply($id, array $body) {
        return $this->post_applicants($id, $body);
    }

    /**
     * Invite a user to a group.
     *
     * @throws ClientException
     * @throws ServerException
     * @param int $id
     * @param array $body
     * @return array
     */
    public function post_invites($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupSchema();
        $in = $this->schema(['userID:i'], 'in')->setDescription('Invite a user to a group.');
        $out = $this->schema($this->fullGroupInviteSchema(), 'out');


        $this->groupModel->checkGroupPermission(GroupPermissions::MODERATE, $id);

        $body = $in->validate($body);

        $this->userByID($body['userID']);

        $result = $this->groupModel->inviteUsers($id, [$body['userID']]);
        $this->validateModel($this->groupModel);

        $invites = $this->groupModel->getApplicants($id, ['Type' => 'Invitation', 'UserID' => $body['userID']], false, false, false);
        if (!$result || count($invites) !== 1) {
            throw new ServerException('An error occurred while inviting the user.', 500);
        }

        $invites = array_pop($invites);

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($invites, ['UserID', 'InsertUserID']);
        }

        return $out->validate($invites);
    }

    /**
     * Join a public group or a group that you have been invited to.
     *
     * @param int $id The group ID.
     * @throws NotFoundException If unable to find the group.
     * @throws ServerException
     * @return array
     */
    public function post_join($id) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupSchema()->setDescription('Join a public group or a group that you have been invited to.');
        $out = $this->schema($this->fullGroupMemberSchema(), 'out');

        $group = $this->groupByID($id);
        $this->verifyAccess($group);

        if (!$this->groupModel->join($id, $this->getSession()->UserID)) {
            throw new ServerException('Unable to join the group.', 500);
        }

        $members = $this->groupModel->getMembers($id, ['UserID' => $this->getSession()->UserID], false, false, false);
        $member = array_pop($members);

        $this->userModel->expandUsers($member, ['UserID']);

        $result = $this->normalizeGroupMemberOutput($member);
        return $out->validate($result);
    }

    /**
     * Leave a group. Shortcut of DELETE /group/:id/members/:userID
     *
     * @param int $id The group ID.
     * @throws NotFoundException If unable to find the group.
     * @return array
     */
    public function post_leave($id) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamGroupSchema()->setDescription('Leave a group.');
        $this->schema([], 'out');

        $this->leaveGroup($id, $this->getSession()->UserID);
    }

    /**
     * Add a user to a group.
     *
     * @param int $id The group ID.
     * @param array $body The request body.
     * @throws ClientException
     * @throws NotFoundException If unable to find the group.
     * @throws ServerException
     * @return array
     */
    public function post_members($id, array $body) {
        $this->permission("Garden.Moderation.Manage");

        $this->idParamGroupSchema();
        $in = $this->schema([
            'userID:i' => 'The ID of the user.',
            'role:s?' => [
                'default' => 'member',
                'enum' => ['leader', 'member'],
                'description' => 'The role of the user for that group.',
            ],
        ])->setDescription('Add a user to a group.');
        $out = $this->schema($this->fullGroupMemberSchema(), 'out');

        $body = $in->validate($body);

        if (!$this->groupModel->addUser($id, $body['userID'], $this->capitalCaseScheme->convert($body['role']))) {
            throw new ServerException('Unable to add user to group.', 500);
        }

        $members = $this->groupModel->getMembers($id, ['UserID' => $body['userID']], false, false, false);
        $member = array_pop($members);

        $this->userModel->expandUsers($member, ['UserID']);

        $result = $this->normalizeGroupMemberOutput($member);
        return $out->validate($result);
    }

    /**
     * Search for a group.
     *
     * @param array $query
     * @return array
     */
    public function get_search(array $query) {
        $this->permission();

        $in = $this->schema([
            'query:s' => 'Search parameter',
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
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => GroupModel::LIMIT,
                'minimum' => 1,
                'maximum' => GroupModel::LIMIT,
            ],
        ], 'in')->setDescription('Search for a group');

        $out = $this->schema([':a' => $this->fullGroupSchema()], 'out');
        $query = $in->validate($query);

        // Sorting
        [$sortField, $sortOrder] = $this->resultSorting($query);

        $groupName = $query['query'];
        $page = $query['page'];
        $limit = $query['limit'];

        [$offset, $limit] = offsetLimit("p{$page}", $limit);

        $rows = $this->groupModel->searchByName($groupName, $sortField, $sortOrder, $limit, $offset);
        foreach ($rows as &$row) {
            $row = $this->normalizeGroupOutput($row);
        }
        $result = $out->validate($rows);

        $paging = ApiUtils::numberedPagerInfo($this->groupModel->searchTotal($groupName), "/api/v2/groups/search", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get a group schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postGroupSchema() {
        static $postGroupSchema;

        if ($postGroupSchema === null) {
            $postGroupSchema = $this->schema(
                Schema::parse([
                    'name',
                    'description',
                    'format' => new \Vanilla\Models\FormatSchema(),
                    'iconUrl?',
                    'bannerUrl?',
                    'privacy',
                ])->add($this->fullGroupSchema()),
                'GroupPost'
            );
        }

        return $this->schema($postGroupSchema, 'in');
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

    /**
     * Get the sorting parameters for queries.
     *
     * @param array $query
     * @return array
     */
    protected function resultSorting(array $query) {
        $sortField = '';
        $sortOrder = 'asc';
        if (array_key_exists('sort', $query)) {
            $sortField = ltrim($query['sort'], '-');
            if (strlen($sortField) !== strlen($query['sort'])) {
                $sortOrder = 'desc';
            }
        }

        return [$sortField, $sortOrder];
    }

    /**
     * Verify the current user has "Access" permission for a group.
     *
     * @param array $group
     * @throws NotFoundException If the current user does not have access to the group.
     */
    private function verifyAccess(array $group) {
         // GroupModel's checkPermission method caches permissions, which make it a pain for contexts where permissions
         // are prone to changing, like in tests or API endpoints that attempt to verify group access before a user joins.
        $this->groupModel->resetCachedPermissions();
        if (!$this->groupModel->hasGroupPermission(GroupPermissions::ACCESS, $group['GroupID'])) {
            throw new NotFoundException('Group');
        }
    }
}
