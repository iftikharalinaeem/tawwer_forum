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
use Vanilla\Formatting\FormatCompatTrait;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/events` resource.
 */
class EventsApiController extends AbstractApiController {

    use FormatCompatTrait;

    /** @var CapitalCaseScheme */
    private $capitalCaseScheme;

    /** @var CamelCaseScheme */
    private $camelCaseScheme;

    /** @var EventModel */
    private $eventModel;

    /** @var GroupModel */
    private $groupModel;

    /** @var UserModel */
    private $userModel;

    /**
     * EventsApiController constructor.
     *
     * @param EventModel $eventModel
     * @param GroupModel $groupModel
     * @param UserModel $userModel
     */
    public function __construct(
        EventModel $eventModel,
        GroupModel $groupModel,
        UserModel $userModel
    ) {
        $this->eventModel = $eventModel;
        $this->groupModel = $groupModel;
        $this->userModel = $userModel;

        $this->camelCaseScheme = new CamelCaseScheme();
        $this->capitalCaseScheme = new CapitalCaseScheme();
    }

    /**
     * Delete an event.
     *
     * @param int $id The ID of the event.
     * @throws ClientException
     */
    public function delete($id) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamEventSchema()->setDescription('Delete an event.');
        $this->schema([], 'out');

        $event = $this->eventByID($id);

        if (!$this->eventModel->checkPermission('Organizer', $event)) {
            throw new ClientException('You do not have the rights to delete this event.');
        }

        // EventModel->deleteID() won't do here because it does not delete all the event's data.
        $this->eventModel->delete(['EventID' => $id]);
    }

    /**
     * Get an event by its ID.
     *
     * @param int $id The event ID.
     * @throws NotFoundException If the event could not be found.
     * @return array
     */
    public function eventByID($id) {
        $row = $this->eventModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Event');
        }

        return $row;
    }

    /**
     * Get a schema instance comprised of all available event participant fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullEventParticipantSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'eventID:i' => 'The ID of the event.',
                'userID:i' => 'The user ID of the participant.',
                'user?' => $this->getUserFragmentSchema(),
                'dateInserted:dt' => 'When the event was created.',
                'attending:s|n' => [
                    'enum' => ['yes', 'no', 'maybe'],
                    'description' => 'Is the participant attending the event.',
                ]
            ], 'Event');
        }

        return $schema;
    }

    /**
     * Get a schema instance comprised of all available event fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullEventSchema() {
        /** @var Schema $schema */
        static $schema;

        if ($schema === null) {
            // Name this schema so that it can be read by swagger.
            $schema = $this->schema([
                'eventID:i' => 'The ID of the event.',
                'groupID:i?' => 'ID of the group. This parameter is deprecrated',
                'parentRecordType:s' => 'The record type of the parent record.',
                'parentRecordID:i' => 'The record ID of the parent record',
                'name:s' => 'The name of the event.',
                'body:s' => 'The description of the event.',
                'location:s|n' => [
                    'maxLength' => 255,
                    'description' => 'The location of the event.'
                ],
                'dateStarts:dt' => 'When the event starts.',
                'dateEnds:dt|n' => 'When the event ends.',
                'dateInserted:dt' => 'When the event was created.',
                'insertUserID:i' => 'The user that created the event.',
                'insertUser?' => $this->getUserFragmentSchema(),
                'dateUpdated:dt|n' => 'When the event was updated.',
                'updateUserID:i|n' => 'The user that updated the event.',
                'updateUser?' => $this->getUserFragmentSchema(),
                'url:s' => 'The full URL to the event.',
            ], 'Event');
        }

        return $schema;
    }

    /**
     * Get an event.
     *
     * @param int $id The ID of the event.
     * @throws Exception
     * @return array
     */
    public function get($id) {
        $this->permission();

        $this->idParamEventSchema()->setDescription('Get an event.');
        $out = $this->schema($this->fullEventSchema(), 'out');

        $event = $this->eventByID($id);

        if (!$this->eventModel->checkPermission('View', $event)) {
            throw new ClientException('You do not have the rights to view this event.');
        }

        $this->userModel->expandUsers($event, ['InsertUserID', 'UpdateUserID']);

        $result = $this->normalizeEventOutput($event);
        return $out->validate($result);
    }

    /**
     * List event's participants.
     *
     * @param int $id The ID of the event.
     * @param array $query
     * @throws Exception
     * @return array
     */
    public function get_participants($id, array $query) {
        $this->permission();

        $this->idParamEventSchema();
        $in = $this->schema([
            'attending:s?' => [
                'default' => 'all',
                'enum' => ['yes', 'no', 'maybe', 'answered', 'unanswered', 'all'],
                'description' => 'Filter participant by attending status.',
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->eventModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('Get event\'s participants.');
        $out = $this->schema([':a' => $this->fullEventParticipantSchema()], 'out');

        $query = $in->validate($query);

        $participantData = $this->normalizeEventParticipantInput($query);

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        // Filters
        $where = [];
        if (array_key_exists('Attending', $participantData)) {
            if ($participantData['Attending'] === 'Answered') {
                $where['Attending<>'] = 'Invited';
            } else if ($participantData['Attending'] === 'Unanswered') {
                $where['Attending'] = 'Invited';
            } else if ($participantData['Attending'] !== 'All') {
                $where['Attending'] = $participantData['Attending'];
            }
        }

        // Data
        $rows = $this->eventModel->getInvitedUsers($id, $where, '', 'asc', $limit, $offset);

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($rows, ['UserID']);
        }
        foreach ($rows as &$row) {
            $row = $this->normalizeEventParticipantOutput($row);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/events/$id/participants", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get an event for editing.
     *
     * @throws NotFoundException if the event could not be found.
     * @throws ClientException
     * @param int $id The ID of the event.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->idParamEventSchema()->setDescription('Get an event for editing.');
        $out = $this->schema(
            Schema::parse([
                'eventID',
                'groupID',
                'parentRecordType',
                'parentRecordID',
                'name',
                'body',
                'format' => new \Vanilla\Models\FormatSchema(true),
                'location',
                'dateStarts',
                'dateEnds'
            ])->add($this->fullEventSchema()),
            'out'
        );

        $event = $this->eventByID($id);

        if (!$this->eventModel->checkPermission('View', $event)) {
            throw new ClientException('You do not have the rights to view this event.');
        }

        $result = $out->validate($event);
        $this->applyFormatCompatibility($result, 'body', 'format');
        return $result;
    }

    /**
     * Ensure a parent record exists, and the user has access to it.
     *
     * @param array $inputParams Check if we can insert in some parent record/id combo if supplied.
     * @throws NotFoundException If the group could not be found.
     */
    public function validateParentRecordInsert(array $inputParams) {
        $parentRecordID = $inputParams['parentRecordID'] ?? null;
        $parentRecordType = $inputParams['parentRecordType'] ?? null;
        if ($parentRecordType === GroupModel::RECORD_TYPE && $parentRecordID !== null) {
            $row = $this->groupModel->getID($parentRecordID, DATASET_TYPE_ARRAY);
            if (!$row || !$this->groupModel->checkPermission('Access', $row)) {
                throw new NotFoundException('Group');
            }
        } else {
            // Other record types aren't implemented.
        }

        if ($parentRecordType !== null && $parentRecordID !== null) {
            if (!$this->eventModel->canCreateEvents($parentRecordType, $parentRecordID)) {
                throw new ClientException('You do not have permission to create an event in this group.');
            }
        }
    }

    /**
     * Add an event end date.
     *
     * if we don't have an endDate, set it to midnight of that day
     * make it to an all day event.
     *
     * @param $eventData
     * @return array
     */
    private function setEventEndDate($eventData) {
        $startDate = $eventData['dateStarts'] ?? $eventData['DateStarts'] ?? false;
        $eventEndDateInfo = [];
        if ($startDate instanceof DateTimeInterface) {
            $formattedStartDate = $startDate->format('Y-m-d');
            $newEndDate = new DateTime($formattedStartDate);
            $newEndDate->add(new DateInterval('PT23H59M59S'))->format('Y-m-d H:i:s');
            $eventEndDateInfo['dateEnds'] = $newEndDate;
        } else {
            $convertedStartDate = new DateTime($startDate);
            $formattedStartDate = $convertedStartDate->format('Y-m-d');
            $newEndDate = new DateTime($formattedStartDate);
            $newEndDate->add(new DateInterval('PT23H59M59S'));
            $eventEndDateInfo['dateEnds'] = $newEndDate->format('Y-m-d H:i:s');
        }
        $eventEndDateInfo['allDayEvent'] = 1;

        return $eventEndDateInfo;
    }

    /**
     * Get an ID-only event record schema.
     *
     * @return Schema Returns a schema object.
     */
    public function idParamEventSchema() {
        return $this->schema(['id:i' => 'The event ID.'], 'in');
    }

    /**
     * List events.
     *
     * @param array $query
     * @return array
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'groupID:i' => 'Filter by group ID.',
            'sort:s?' => [
                'enum' => [
                    'dateInserted', '-dateInserted',
                    'dateStarts', '-dateStarts',
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
                'default' => $this->eventModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
            'expand:b?' => 'Expand associated records.',
        ], 'in')->setDescription('List events.');
        $out = $this->schema([':a' => $this->fullEventSchema()], 'out');

        $query = $in->validate($query);

        // Sorting
        $sortField = '';
        $sortOrder = 'asc';
        if (array_key_exists('sort', $query)) {
            $sortField = $this->capitalCaseScheme->convert(ltrim($query['sort'], '-'));
            if (strlen($sortField) !== strlen($query['sort'])) {
                $sortOrder = 'desc';
            }
        }

        // Paging
        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        // Filters
        $where = [];
        if (array_key_exists('groupID', $query)) {
            $group = $this->groupModel->getID($query['groupID']);
            $groupPrivacy = $group['Privacy'];
            $access = ($groupPrivacy === 'Private' || $groupPrivacy === 'Secret' ) ?  'Member' :  'Access';
            $isAdmin = Gdn::Session()->CheckPermission('Garden.Settings.Manage');
            if (!$this->groupModel->checkPermission($access, $query['groupID']) && !$isAdmin) {
                // Use an impossible GroupID, so the same result is met as if a non-existent group ID is provided.
                $where['GroupID'] = -1;
            } else {
                $where['GroupID'] = $query['groupID'];
            }
        }

        // Data
        $rows = [];
        if ($where) {
            $rows = $this->eventModel->getWhere($where, $sortField, $sortOrder, $limit, $offset)->resultArray();
        }

        if (!empty($query['expand'])) {
            $this->userModel->expandUsers($rows, ['InsertUserID', 'UpdateUserID']);
        }
        foreach ($rows as &$row) {
            $row = $this->normalizeEventOutput($row);
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/events", $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Normalize an event Schema record to match the database definition.
     *
     * @param array $schemaRecord Event Schema record.
     * @return array Return a database record.
     */
    public function normalizeEventInput(array $schemaRecord) {
        $parentRecordType = $schemaRecord['parentRecordType'] ?? null;
        $parentRecordID = $schemaRecord['parentRecordID'] ?? null;
        if ($parentRecordType === GroupModel::RECORD_TYPE && $parentRecordID !== null) {
            // Backwards compatibility.
            $schemaRecord['GroupID'] = $schemaRecord['parentRecordID'];
        }
        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize an event participant Schema record to match the database definition.
     *
     * @param array $schemaRecord Event participant Schema record.
     * @return array Return a database record.
     */
    public function normalizeEventParticipantInput(array $schemaRecord) {
        if (array_key_exists('attending', $schemaRecord)) {
            if ($schemaRecord['attending'] === null) {
                $schemaRecord['attending'] = 'Invited';
            }
            $schemaRecord['attending'] = $this->capitalCaseScheme->convert($schemaRecord['attending']);
        }

        $dbRecord = ApiUtils::convertInputKeys($schemaRecord);
        return $dbRecord;
    }

    /**
     * Normalize a event database record to match the schema definition.
     *
     * @param array $dbRecord Event database record.
     * @return array Return a schema record.
     */
    public function normalizeEventOutput(array $dbRecord) {
        $dbRecord['Body'] = Gdn_Format::to($dbRecord['Body'], $dbRecord['Format']);

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        $schemaRecord['url'] = eventUrl($dbRecord);

        return $schemaRecord;
    }

    /**
     * Normalize a event participant database record to match the schema definition.
     *
     * @param array $dbRecord Event participant database record.
     * @return array Return a schema record.
     */
    public function normalizeEventParticipantOutput(array $dbRecord) {
        $dbRecord['Attending'] = $this->camelCaseScheme->convert($dbRecord['Attending']);
        $dbRecord['Attending'] = $dbRecord['Attending'] === 'invited' ? null : $dbRecord['Attending'];

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Update an event.
     *
     * @param int $id The ID of the event.
     * @param array $body The request body.
     * @throws NotFoundException If unable to find the event.
     * @throws ClientException
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamEventSchema();
        $in = $this->patchEventSchema();
        $out = $this->schema($this->fullEventSchema(), 'out');

        $body = $this->normalizeInputIDs($body);
        $body = $in->validate($body, true);

        $event = $this->eventByID($id);

        $eventData = $this->normalizeEventInput($body);

        // backwards compatibility, if a null endDate is passed we should add one.
        if (array_key_exists('dateEnds', $body) && !$body['dateEnds']) {
            $newEndDate = $this->setEventEndDate($event);
            $eventData['DateEnds'] = $newEndDate['dateEnds'] ?? null;
            $eventData['AllDayEvent'] = $newEndDate['allDayEvent'] ?? null;
        }

        $eventData['EventID'] = $id;

        if (!$this->eventModel->checkPermission('Edit', $event)) {
            throw new ClientException('You do not have the rights to edit this event.');
        }

        $this->validateParentRecordInsert($body);

        $this->eventModel->save($eventData);
        $this->validateModel($this->eventModel);

        $result = $this->eventByID($id);
        $this->userModel->expandUsers($result, ['InsertUserID', 'UpdateUserID']);

        $result = $this->normalizeEventOutput($result);
        return $out->validate($result);
    }

    /**
     * Create an event.
     *
     * @throws ServerException
     * @throws NotFoundException If the group or event was not found.
     * @throws ClientException
     * @param array $body
     * @return mixed
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->postEventSchema()->setDescription('Create an event.');
        $out = $this->schema($this->fullEventSchema(), 'out');

        $body = $this->normalizeInputIDs($body);
        $body = $in->validate($body);

        $this->validateParentRecordInsert($body);

        $eventData = $this->normalizeEventInput($body);

        // backwards compatibility, if a null endDate is passed we should add one.
        if (array_key_exists('dateEnds', $body) && !$body['dateEnds']) {
            $newEndDate = $this->setEventEndDate($eventData);
            $eventData['DateEnds'] = $newEndDate['dateEnds'] ?? null;
            $eventData['AllDayEvent'] = $newEndDate['allDayEvent'] ?? null;
        }

        $id = $this->eventModel->save($eventData);
        $this->validateModel($this->eventModel);

        if (!$id) {
            throw new ServerException('Unable to create event.', 500);
        }

        $row = $this->eventByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);

        $result = $this->normalizeEventOutput($row);
        return $out->validate($result);
    }

    /**
     * RSVP to an event.
     *
     * @throws ClientException
     * @throws ServerException
     * @param int $id The event ID.
     * @param array $body
     * @return array
     */
    public function post_participants($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamEventSchema();
        $in = $this->schema([
            'userID:s?' => 'The ID of the user that RSVP.',
            'attending:s|n' => [
                'enum' => ['yes', 'no', 'maybe'],
                'description' => 'Is the participant attending the event.',
            ]
        ])->setDescription('RSVP to an event.');
        $out = $this->schema($this->fullEventParticipantSchema(), 'out');

        $event = $this->eventByID($id);

        if (!$this->eventModel->checkPermission('View', $event)) {
            throw new ClientException('You do not have the rights to view that event.');
        }

        $body = $in->validate($body);

        $userID = !empty($body['userID']) ? $body['userID'] : $this->getSession()->UserID;
        if ($this->getSession()->UserID !== $userID && !$this->eventModel->checkPermission('Organizer', $event, $this->getSession()->UserID)) {
            throw new ClientException('You do not have the rights to add a participant to that event.');
        }

        $participantData = $this->normalizeEventParticipantInput($body);

        $this->eventModel->attend($userID, $id, $participantData['Attending']);

        $error = true;
        $users = $this->eventModel->getInvitedUsers($id, ['UserID' => $userID]);
        $participant = null;
        if (count($users) === 1) {
            $participant = array_pop($users);
        }
        if ($participant !== null) {
            $error = ($participant['Attending'] !== $participantData['Attending']);
        }
        if ($error) {
            throw new ServerException('Unable to insert the participant\'s RSVP.');
        }

        $this->userModel->expandUsers($participant, ['UserID']);

        $result = $this->normalizeEventParticipantOutput($participant);
        return $out->validate($result);
    }

    /**
     * Normalize input groupIDs into recordType and recordID.
     *
     * @param array $input
     * @return array
     */
    private function normalizeInputIDs(array $input): array {
        $groupID = $input['groupID'] ?? null;
        if ($groupID !== null) {
            $input['parentRecordType'] = GroupModel::RECORD_TYPE;
            $input['parentRecordID'] = $groupID;
        }
        return $input;
    }

    /**
     * Get an event schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postEventSchema() {
        static $postEventSchema;

        if ($postEventSchema === null) {
            $postEventSchema = $this->schema(
                Schema::parse([
                    'parentRecordID',
                    'parentRecordType',
                    'name',
                    'body',
                    'format' => new \Vanilla\Models\FormatSchema(),
                    'location',
                    'dateStarts',
                    'dateEnds?',
                ])->add($this->fullEventSchema()),
                'EventPost'
            );
        }

        return $this->schema($postEventSchema, 'in');
    }

    /**
     * Get an event schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function patchEventSchema() {
        static $postEventSchema;

        if ($postEventSchema === null) {
            $postEventSchema = $this->schema(
                Schema::parse([
                    'parentRecordID?',
                    'parentRecordType?',
                    'name?',
                    'body?',
                    'format?' => new \Vanilla\Models\FormatSchema(),
                    'location?',
                    'dateStarts?',
                    'dateEnds?',
                ])->add($this->fullEventSchema()),
                'EventPatch'
            );
        }

        return $this->schema($postEventSchema, 'in');
    }
}
