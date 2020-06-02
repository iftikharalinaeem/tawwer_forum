<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Forum\Navigation\GroupRecordType;
use Vanilla\Groups\Models\EventPermissions;
use Vanilla\Groups\Models\GroupPermissions;
use Vanilla\Site\SiteSectionModel;

/**
 * Groups Application - Event Model
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventModel extends Gdn_Model {

    const PARENT_TYPE_GROUP = 'group';
    const PARENT_TYPE_CATEGORY = 'category';

    /** @var GroupModel */
    private $groupModel;

    /** @var Gdn_Session */
    private $session;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var ConfigurationInterface */
    private $config;

    /** @var array The permissions associated with a group. */
    private static $permissions = [];

    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('Event');
        $this->groupModel = \Gdn::getContainer()->get(GroupModel::class);
        $this->session = \Gdn::getContainer()->get(Gdn_Session::class);
        $this->config = \Gdn::getContainer()->get(ConfigurationInterface::class);
        $this->categoryModel = \Gdn::getContainer()->get(CategoryModel::class);
    }

    /**
     * Get the canonical event URL for an event.
     *
     * @param array $event
     *
     * @return string The event URL.
     */
    public function eventUrl(array $event): string {
        $eventID = $event['EventID'];
        $name = $event['Name'];
        $slug = Gdn_Format::url($name);
        $parentRecordID = $event['ParentRecordID'];
        $parentRecordType = $event['ParentRecordType'];

        // Lazily get our theme features.
        $themeFeatures = Gdn::themeFeatures();

        $eventPath = $themeFeatures->allFeatures()['NewEventsPage'] ? "/events/$eventID-$slug" : "/event/$eventID-$slug";
        $siteSectionPath = $this->getSiteSectionPathForParentRecord($parentRecordType, $parentRecordID);
        $result = \Gdn::request()->getSimpleUrl($siteSectionPath . $eventPath);
        return $result;
    }

    /**
     * Get a site section path.
     *
     * @param string $parentRecordType
     * @param int $parentRecordID
     * @return string
     */
    private function getSiteSectionPathForParentRecord(string $parentRecordType, int $parentRecordID): string {
        $siteSectionPath = null;
        // Try to find the correct subcommunity.
        /** @var SiteSectionModel $sectionModel */
        $sectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);

        if ($parentRecordType === ForumCategoryRecordType::class) {
            // Go through the sections to find the correct one.
            $sections = $sectionModel->getAll();
            foreach ($sections as $section) {
                if ($section->getAttributes()['CategoryID'] === $parentRecordID) {
                    $siteSectionPath = $section->getBasePath();
                    break;
                }
            }
        }
        if ($siteSectionPath === null) {
            $siteSectionPath = $sectionModel->getCurrentSiteSection()->getBasePath();
        }
        // Make sure we don't have double-slashes in the path.
        $siteSectionPath = rtrim($siteSectionPath, '/');
        return $siteSectionPath;
    }

    /**
     * Get the canonical event URL for an event.
     *
     * @param string $parentRecordType
     * @param int $parentRecordID
     *
     * @return string The event URL.
     */
    public function eventParentUrl(string $parentRecordType, int $parentRecordID): string {
        $parentRecordName = '';
        if ($parentRecordType === ForumCategoryRecordType::TYPE) {
            $parentRecordName = $this->categoryModel::categories($parentRecordID)['Name'];
        } elseif ($parentRecordType === GroupRecordType::TYPE) {
            $parentRecordName = $this->groupModel->getID($parentRecordID)['Name'];
        }
        $slug = Gdn_Format::url($parentRecordName);
        $siteSectionPath = $this->getSiteSectionPathForParentRecord($parentRecordType, $parentRecordID);
        $result = "${siteSectionPath}/events/${parentRecordType}/${parentRecordID}-${slug}";
        return \Gdn::request()->getSimpleUrl($result);
    }

    /**
     * Get events that this user is invited to.
     *
     * @param integer $userID
     * @return type
     */
    public function getByUser($userID) {
        $userGroups = $this->SQL->getWhere('UserGroup', ['UserID' => $userID])->resultArray();
        $iDs = array_column($userGroups, 'GroupID');

        $result = $this->getWhere(['GroupID' => $iDs], 'Name')->resultArray();
        return $result;
    }

    /**
     * Get an event by ID.
     *
     * @param integer $eventID
     * @param integer $datasetType
     * @param array $options Base class compatibility.
     * @return array
     */
    public function getID($eventID, $datasetType = DATASET_TYPE_ARRAY, $options = []) {
        $eventID = self::parseID($eventID);

        $row = parent::getID($eventID, $datasetType);
        return $row;
    }

    /**
     * Get events by date.
     *
     * @param strtotime $future Relative time offset. Like "+30 days"
     * @param array $where
     * @param boolean $ended Optional. Only events that have due to their end date?
     *      (End date is optional so setting this will exclude events with no end date.)
     * @return type
     */
    public function getUpcoming($future, $where = null, $ended = null, $limit = 30) {
        $uTC = new DateTimeZone('UTC');
        $startDate = new DateTime('now', $uTC);
        if ($future) {
            $limitDate = new DateTime('now', $uTC);
            $limitDate->modify($future);
        }

        // Handle 'invited' state manually
        if ($invitedUserID = val('Invited', $where)) {
            unset($where['Invited']);
        }

        // Limit to a future date, but after right now
        if ($limitDate > $startDate) {
            if ($ended === false) {
                $where['DateEnds >='] = $startDate->format('Y-m-d H:i:s');
            }

            if ($future) {
                $where['DateStarts <='] = $limitDate->format('Y-m-d H:i:s');
            }
        } else {
            $where['DateStarts <'] = $startDate->format('Y-m-d H:i:s');
            if ($future) {
                $where['DateStarts >='] = $limitDate->format('Y-m-d H:i:s');
            }
        }
        $eventsQuery = $this->SQL
            ->select('e.*')
            ->where($where);

        if ($ended) {
            // recent events
            $eventsQuery
                ->beginWhereGroup()
                ->where('DateEnds <=', $startDate->format('Y-m-d H:i:s'))
                ->orWhere('DateEnds is  null')
                ->endWhereGroup();
        } else {
            // upcoming events
            $eventsQuery
                ->beginWhereGroup()
                ->where('DateStarts >=', $startDate->format('Y-m-d H:i:s'))
                ->orWhere('DateEnds >=', $startDate->format('Y-m-d H:i:s'))
                ->endWhereGroup();
        }
        $eventsQuery
            ->limit($limit)
            ->orderBy('DateStarts', 'asc');

        if ($invitedUserID) {
            $eventsQuery
                ->from('UserEvent ue')
                ->join('Event e', 'ue.EventID = e.EventID');
        } else {
            $eventsQuery->from('Event e');
        }

        return $eventsQuery->get()->resultArray();
    }

    /**
     * Calculate permissions for an event.
     *
     * @param int $eventID The event ID.
     * @param int|null $userID The userID to calculate the permissions for. Defaults to the current user.
     * @return EventPermissions
     */
    public function calculatePermissionsForEvent(int $eventID, ?int $userID = null): EventPermissions {
        if ($userID === null) {
            $userID = $this->session->UserID;
        }

        $localCacheKey = "{$userID}-{$eventID}";


        if (isset(self::$permissions[$localCacheKey])) {
            return self::$permissions[$localCacheKey];
        }

        $event = $this->getID($eventID);
        if (!$event) {
            throw new NotFoundException('Event');
        }

        $userEvent = false;
        if ($this->session->isValid()) {
            $userEvent = $this->SQL
                ->getWhere('UserEvent', [
                    'EventID' => $eventID,
                    'UserID' => $userID
                ])
                ->firstRow(DATASET_TYPE_ARRAY)
            ;
        }

        $permissions = new EventPermissions();

        // The event owner starts out with access to all events.
        if ($userID === $event['InsertUserID']) {
            $permissions
                ->setPermission(EventPermissions::ORGANIZER, true)
                ->setPermission(EventPermissions::EDIT, true)
                ->setPermission(EventPermissions::MEMBER, true)
                ->setPermission(EventPermissions::VIEW, true)
                ->setPermission(EventPermissions::ATTEND, true)
            ;
        } elseif ($userEvent) {
            $permissions
                ->setPermission(EventPermissions::MEMBER, true)
                ->setPermission(EventPermissions::VIEW, true)
                ->setPermission(EventPermissions::ATTEND, true)
            ;
        }

        $parentRecordID = $event['ParentRecordID'];
        $parentRecordType = $event['ParentRecordType'];
        switch ($parentRecordType) {
            case ForumCategoryRecordType::TYPE:
                if ($this->categoryModel::checkPermission($parentRecordID, 'Vanilla.Events.Manage')) {
                    $permissions
                        ->setPermission(EventPermissions::CREATE, true)
                        ->setPermission(EventPermissions::EDIT, true)
                        ->setPermission(EventPermissions::VIEW, true)
                        ->setPermission(EventPermissions::ATTEND, true)
                        ->setPermission(EventPermissions::ORGANIZER, true)
                    ;
                } elseif ($this->categoryModel::checkPermission($parentRecordID, 'Vanilla.Events.View')) {
                    $permissions
                        ->setPermission(EventPermissions::VIEW, true)
                        ->setPermission(EventPermissions::ATTEND, true)
                    ;
                }
                break;
            case GroupRecordType::TYPE:
                $membersCanAddEvents = $this->config->get('Groups.Members.CanAddEvents', true);
                $eventGroupPermissions = $this->groupModel->calculatePermissionsForGroup($parentRecordID, $userID);
                if ($eventGroupPermissions->hasPermission(GroupPermissions::MEMBER)) {
                    $permissions
                        ->setPermission(EventPermissions::MEMBER, true)
                        ->setPermission(EventPermissions::VIEW, true)
                        ->setPermission(EventPermissions::CREATE, $membersCanAddEvents)
                        ->setPermission(EventPermissions::ATTEND, true)
                    ;
                } elseif ($eventGroupPermissions->hasPermission(GroupPermissions::LEADER)) {
                    $permissions
                        ->setPermission(EventPermissions::CREATE, true)
                        ->setPermission(EventPermissions::EDIT, true)
                        ->setPermission(EventPermissions::VIEW, true)
                        ->setPermission(EventPermissions::ATTEND, true)
                    ;
                } elseif ($eventGroupPermissions->hasPermission(GroupPermissions::VIEW)) {
                    $permissions->setPermission(EventPermissions::VIEW, true);
                }
                break;
        }

        $isModerator = $this->groupModel->isModerator();
        if ($isModerator) {
            $permissions
                ->setPermission(EventPermissions::EDIT, true)
                ->setPermission(EventPermissions::VIEW, true)
                ->setPermission(EventPermissions::CREATE, true)
            ;
        }

        $isAdmin = Gdn::Session()->CheckPermission('Garden.Settings.Manage');
        if ($isAdmin) {
            $permissions
                ->setPermission(EventPermissions::CREATE, true)
                ->setPermission(EventPermissions::EDIT, true)
                ->setPermission(EventPermissions::VIEW, true)
                ->setPermission(EventPermissions::ATTEND, true)
                ->setPermission(EventPermissions::ORGANIZER, true)
            ;
        }

        return $permissions;
    }

    /**
     * Check if a permission exists on a group. Throws an exception if that permission is not set.
     *
     * @param string $permission One of the constants from GroupPermission::
     * @param int $eventID The ID of the group to check.
     * @param int|null $userID
     *
     */
    public function checkEventPermission(string $permission, int $eventID, int $userID = null) {
        $permissions = $this->calculatePermissionsForEvent($eventID, $userID);
        $permissions->checkPermission($permission);
    }

    /**
     * Check if a permission exists on a group.
     *
     * @param string $permission One of the constants from GroupPermission::
     * @param int $eventID The ID of the group to check.
     * @param int|null $userID
     *
     * @return bool Whether or not the user has the permission.
     */
    public function hasEventPermission(string $permission, int $eventID, int $userID = null): bool {
        $permissions = $this->calculatePermissionsForEvent($eventID, $userID);
        return $permissions->hasPermission($permission);
    }

    /**
     * Expand out permissions on rows of events.
     *
     * @param array $eventRows
     */
    public function expandPermissions(array &$eventRows) {
        foreach ($eventRows as &$event) {
            $event['permissions'] = $this->calculatePermissionsForEvent($event['eventID'] ?? $event['EventID']);
        }
    }

    /**
     * Check permission on a event.
     *
     * @param string $permission The permission to check. Valid values are:
     * @param int|array $eventID The event ID of the event record
     * @param int|null $userID
     * @return boolean
     * @deprecated
     */
    public function checkPermission($permission, $eventID, $userID = null) {
        deprecated(__METHOD__, 'checkEventPermission');
        if (is_array($eventID)) {
            $eventID = $eventID['EventID'];
        }

        $isReason = strpos($permission, '.Reason') !== false;
        if ($isReason) {
            $permission = str_replace('.Reason', '', $permission);
        }

        try {
            $this->checkEventPermission((string) $permission, (int) $eventID, $userID);
            return true;
        } catch (Exception $e) {
            // This is a legacy function an used to return the "reason" why the permission failed as a string.
            if ($isReason) {
                trigger_error("Invalid event permission $permission.");
                return false;
            } else {
                return $e->getMessage();
            }
        }
    }

    /**
     * Reset the cached grouped permissions.
     */
    public function resetCachedPermissions() {
        self::$permissions = [];
    }

    /**
     * Checks to see whether or not a user can create events in a parent record..
     *
     * @param string $parentRecordType
     * @param int $parentRecordID
     * @return bool
     */
    public function canCreateEvents(string $parentRecordType, $parentRecordID): bool {
        switch ($parentRecordType) {
            case GroupRecordType::TYPE:
                if ($this->groupModel->hasGroupPermission(GroupPermissions::LEADER, $parentRecordID)) {
                    return true;
                } elseif (c('Groups.Members.CanAddEvents', true)
                    && $this->groupModel->hasGroupPermission(GroupPermissions::MEMBER, $parentRecordID)
                ) {
                    return true;
                } else {
                    return false;
                }
                break;
            case ForumCategoryRecordType::TYPE:
                // Make sure the category exists.
                $category = $this->categoryModel::categories($parentRecordID);
                if (!$category) {
                    return false;
                }
                return $this->categoryModel::checkPermission($category, 'Vanilla.Events.Manage');
                break;
            default:
                return false;
        }
    }

    /**
     * Checks to see whether or not a user can view events in a parent resource.
     *
     * @param string $parentRecordType
     * @param int $parentRecordID
     * @return bool
     */
    public function canViewEvents(string $parentRecordType, $parentRecordID): bool {
        switch ($parentRecordType) {
            case GroupRecordType::TYPE:
                return $this->groupModel->hasGroupPermission(GroupPermissions::VIEW, $parentRecordID);
                break;
            case ForumCategoryRecordType::TYPE:
                return $this->categoryModel::checkPermission($parentRecordID, 'Vanilla.Events.View');
                break;
            default:
                return false;
        }
    }

    /**
     * Check whether or not an event can be created. Throw an exception if it can't.
     *
     * @param string $permission One of EventPermissions::VIEW or EventPermissions::CREATE
     * @param string $parentRecordType
     * @param int|array $parentRecordID
     */
    public function checkParentEventPermission(string $permission, string $parentRecordType, $parentRecordID) {
        $hasPermission = false;
        if ($permission === EventPermissions::VIEW) {
            $hasPermission = $this->canViewEvents($parentRecordType, $parentRecordID);
        } elseif ($permission === EventPermissions::CREATE) {
            $hasPermission = $this->canCreateEvents($parentRecordType, $parentRecordID);
        }

        if ($hasPermission) {
            return;
        }

        $groupIsSecret = false;
        if ($parentRecordType === GroupRecordType::TYPE) {
            $groupIsSecret = !$this->groupModel->hasGroupPermission(GroupPermissions::ACCESS, $parentRecordID);
        }

        if ($groupIsSecret) {
            throw new NotFoundException('Group');
        } elseif ($parentRecordType === ForumCategoryRecordType::TYPE) {
            // Check that the category exists
            $category = $this->categoryModel::categories($parentRecordID);
            if (!$category) {
                throw new NotFoundException('Category');
            }
            throw new PermissionException('Vanilla.Events.Manage');
        } else {
            $permissions = new EventPermissions();
            throw new ForbiddenException($permissions->getDefaultReasonForPermission(EventPermissions::CREATE));
        }
    }

    /**
     * Parse the ID out of a slug.
     *
     * @param type $iD
     * @return type
     */
    public static function parseID($iD) {
        $parts = explode('-', $iD, 2);
        return $parts[0];
    }

    /**
     * Invite someone to an event.
     *
     * @param integer $userID
     * @param integer $eventID
     * @return int
     */
    public function invite($userID, $eventID) {
        return $this->SQL->insert('UserEvent', [
            'EventID' => $eventID,
            'UserID' => $userID,
            'Attending' => 'Invited'
        ]);
    }

    /**
     * Checks whether an event has ended.
     *
     * @param array $event The event to check.
     * @return bool Whether the event has ended.
     */
    public static function isEnded($event) {
        $utc = new DateTimeZone('UTC');
        $now = new DateTime('now', $utc);
        $dateEnds = new DateTime(val('DateEnds', $event), $utc);
        if ($dateEnds < $now) {
            return true;
        }
        return false;
    }

    /**
     * Get list of invited
     * @param type $eventID
     * @return type
     */
    public function invited($eventID) {
        $collapsedInvited = $this->SQL->getWhere('UserEvent', ['EventID' => $eventID])->resultArray();
        $canSeePersonalInfo = $this->session->checkPermission('Garden.Users.Edit');
        $joinedFields = ['Name', 'Photo'];
        if ($canSeePersonalInfo) {
            $joinedFields[] = 'Email';
        }
        Gdn::userModel()->joinUsers($collapsedInvited, ['UserID'], ['Join' => $joinedFields]);
        $invited = [];
        foreach ($collapsedInvited as $invitee) {
            $invited[$invitee['Attending']][] = $invitee;
        }
        return $invited;
    }

    /**
     * Get list of invited users.
     *
     * @param int $eventID
     * @param array $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param int $offset
     * @return array
     */
    public function getInvitedUsers($eventID, array $where = [], $orderFields = '', $orderDirection = 'asc', $limit = false, $offset = 0) {
        $where['EventID'] = $eventID;

        return $this->SQL->getWhere('UserEvent', $where, $orderFields, $orderDirection, $limit, $offset)->resultArray();
    }

    /**
     * Check if a User is invited to an Event.
     *
     * @param integer $userID
     * @param integer $eventID
     * @return bool
     */
    public function isInvited($userID, $eventID) {
        $isInvited = $this->SQL
            ->getWhere('UserEvent', ['UserID' => $userID, 'EventID' => $eventID])
            ->firstRow(DATASET_TYPE_ARRAY);
        $isInvited = val('Attending', $isInvited, false);

        return $isInvited;
    }

    /**
     * Change user attending status for event.
     *
     * @param integer $userID
     * @param integer $eventID
     * @param enum $attending [Yes, No, Maybe, Invited]
     */
    public function attend($userID, $eventID, $attending) {
        $px = Gdn::database()->DatabasePrefix;
        $sql = "insert into {$px}UserEvent (EventID, UserID, DateInserted, Attending)
            values (:EventID, :UserID, :DateInserted, :Attending)
            on duplicate key update Attending = :Attending1";

        $this->Database->query($sql, [
            ':EventID' => $eventID,
            ':UserID' => $userID,
            ':DateInserted' => date('Y-m-d H:i:s'),
            ':Attending' => $attending,
            ':Attending1' => $attending
        ]);
    }

    /**
     * Get a list of Events with the attending status.
     *
     * @param array $where
     * @param string $orderFields
     * @param string $orderDirection
     * @param bool $limit
     * @param bool $offset
     *
     * @return array
     */
    public function getEvents(array $where, string $orderFields = '', string $orderDirection = 'asc', $limit = false, $offset = false) {

        $userID = $where['UserID'] ?? null;
        unset($where['UserID']);

        $results = $this->SQL
            ->select("e.*, ue.Attending")
            ->from('Event e')
            ->join('UserEvent ue', "e.EventID = ue.EventID and ue.UserID= \"" . $userID . "\"", 'left')
            ->where($where)
            ->orderBy($orderFields, $orderDirection)
            ->limit($limit, $offset)
            ->get()->resultArray();
        ;

        return $results;
    }

    /**
     * Override event save.
     *
     * Set 'Fix' = false to bypass date munging
     *
     * @param array $event
     * @param array $settings Base class compatibility.
     * @return array
     */
    public function save($event, $settings = []) {
        if (!isset($event['DateEnds']) || !$event['DateEnds']) {
            $newEndDate = $this->calculateEventEndDate($event);
            $event['DateEnds'] = $newEndDate['DateEnds'] ?? null;
            $event['AllDayEvent'] = $newEndDate['AllDayEvent'] ?? null;
        }

        // Fix the dates.
        if (array_key_exists('DateStarts', $event)) {
            $this->Validation->applyRule('DateStarts', 'ValidateDate');

            if ($event['DateStarts'] instanceof DateTimeInterface) {
                $event['DateStarts'] = $event['DateStarts']->format(MYSQL_DATE_FORMAT);
            } else {
                $ts = strtotime($event['DateStarts']);
                if ($ts !== false) {
                    $event['DateStarts'] = Gdn_Format::toDateTime($ts);
                }
            }
        }
        if (array_key_exists('DateEnds', $event)) {
            $this->Validation->applyRule('DateEnds', 'ValidateDate');

            if ($event['DateEnds'] instanceof DateTimeInterface) {
                $event['DateEnds'] = $event['DateEnds']->format(MYSQL_DATE_FORMAT);
            } else {
                $ts = strtotime($event['DateEnds']);
                if ($ts !== false) {
                    $event['DateEnds'] = Gdn_Format::toDateTime($ts);
                }
            }
        }

        // Add a timezone in case the database wasn't updated.
        touchValue('Timezone', $event, Gdn::session()->getTimeZone()->getName());

        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $primaryKeyVal = val($this->PrimaryKey, $event, false);
        $insert = $primaryKeyVal == false ? true : false;
        if ($insert) {
            $this->addInsertFields($event);
        } else {
            $this->addUpdateFields($event);
        }

        // Validate the form posted values
        $isValid = $this->validate($event, $insert) === true;
        $this->EventArguments['IsValid'] = &$isValid;
        $this->EventArguments['Fields'] = &$event;
        $this->fireEvent('AfterValidateEvent');

        if (!$isValid) {
            return false;
        }

        return parent::save($event);
    }

    /**
     * Get precompiled timezone list.
     *
     * @staticvar array $built
     * @staticvar array $timezones
     * @return array
     */
    public static function timezones($lookupTimezone = null) {
        static $built = null;

        static $timezones = [
            'Pacific/Midway'         => "Midway Island",
            'US/Samoa'                 => "Samoa",
            'US/Hawaii'                => "Hawaii",
            'US/Alaska'                => "Alaska",
            'US/Pacific'              => "Pacific Time",
            'America/Tijuana'        => "Tijuana",
            'US/Arizona'              => "Arizona",
            'US/Mountain'             => "Mountain Time",
            'America/Chihuahua'     => "Chihuahua",
            'America/Mazatlan'      => "Mazatlan",
            'America/Mexico_City'  => "Mexico City",
            'America/Monterrey'     => "Monterrey",
            'Canada/Saskatchewan'  => "Saskatchewan",
            'US/Central'              => "Central Time",
            'US/Eastern'              => "Eastern Time",
            'US/East-Indiana'        => "Indiana (East)",
            'America/Bogota'         => "Bogota",
            'America/Lima'            => "Lima",
            'America/Caracas'        => "Caracas",
            'Canada/Atlantic'        => "Atlantic Time",
            'America/La_Paz'         => "La Paz",
            'America/Santiago'      => "Santiago",
            'Canada/Newfoundland'  => "Newfoundland",
            'America/Buenos_Aires' => "Buenos Aires",
            'Greenland'                => "Greenland",
            'Atlantic/Stanley'      => "Stanley",
            'Atlantic/Azores'        => "Azores",
            'Atlantic/Cape_Verde'  => "Cape Verde Is.",
            'Africa/Casablanca'     => "Casablanca",
            'Europe/Dublin'          => "Dublin",
            'Europe/Lisbon'          => "Lisbon",
            'Europe/London'          => "London",
            'Africa/Monrovia'        => "Monrovia",
            'Europe/Amsterdam'      => "Amsterdam",
            'Europe/Belgrade'        => "Belgrade",
            'Europe/Berlin'          => "Berlin",
            'Europe/Bratislava'     => "Bratislava",
            'Europe/Brussels'        => "Brussels",
            'Europe/Budapest'        => "Budapest",
            'Europe/Copenhagen'     => "Copenhagen",
            'Europe/Ljubljana'      => "Ljubljana",
            'Europe/Madrid'          => "Madrid",
            'Europe/Paris'            => "Paris",
            'Europe/Prague'          => "Prague",
            'Europe/Rome'             => "Rome",
            'Europe/Sarajevo'        => "Sarajevo",
            'Europe/Skopje'          => "Skopje",
            'Europe/Stockholm'      => "Stockholm",
            'Europe/Vienna'          => "Vienna",
            'Europe/Warsaw'          => "Warsaw",
            'Europe/Zagreb'          => "Zagreb",
            'Europe/Athens'          => "Athens",
            'Europe/Bucharest'      => "Bucharest",
            'Africa/Cairo'            => "Cairo",
            'Africa/Harare'          => "Harare",
            'Europe/Helsinki'        => "Helsinki",
            'Europe/Istanbul'        => "Istanbul",
            'Asia/Jerusalem'         => "Jerusalem",
            'Europe/Kiev'             => "Kyiv",
            'Europe/Minsk'            => "Minsk",
            'Europe/Riga'             => "Riga",
            'Europe/Sofia'            => "Sofia",
            'Europe/Tallinn'         => "Tallinn",
            'Europe/Vilnius'         => "Vilnius",
            'Asia/Baghdad'            => "Baghdad",
            'Asia/Kuwait'             => "Kuwait",
            'Africa/Nairobi'         => "Nairobi",
            'Asia/Riyadh'             => "Riyadh",
            'Asia/Tehran'             => "Tehran",
            'Europe/Moscow'          => "Moscow",
            'Asia/Baku'                => "Baku",
            'Europe/Volgograd'      => "Volgograd",
            'Asia/Muscat'             => "Muscat",
            'Asia/Tbilisi'            => "Tbilisi",
            'Asia/Yerevan'            => "Yerevan",
            'Asia/Kabul'              => "Kabul",
            'Asia/Karachi'            => "Karachi",
            'Asia/Tashkent'          => "Tashkent",
            'Asia/Kolkata'            => "Kolkata",
            'Asia/Kathmandu'         => "Kathmandu",
            'Asia/Yekaterinburg'    => "Ekaterinburg",
            'Asia/Almaty'             => "Almaty",
            'Asia/Dhaka'              => "Dhaka",
            'Asia/Novosibirsk'      => "Novosibirsk",
            'Asia/Bangkok'            => "Bangkok",
            'Asia/Jakarta'            => "Jakarta",
            'Asia/Krasnoyarsk'      => "Krasnoyarsk",
            'Asia/Chongqing'         => "Chongqing",
            'Asia/Hong_Kong'         => "Hong Kong",
            'Asia/Kuala_Lumpur'     => "Kuala Lumpur",
            'Australia/Perth'        => "Perth",
            'Asia/Singapore'         => "Singapore",
            'Asia/Taipei'             => "Taipei",
            'Asia/Ulaanbaatar'      => "Ulaan Bataar",
            'Asia/Urumqi'             => "Urumqi",
            'Asia/Irkutsk'            => "Irkutsk",
            'Asia/Seoul'              => "Seoul",
            'Asia/Tokyo'              => "Tokyo",
            'Australia/Adelaide'    => "Adelaide",
            'Australia/Darwin'      => "Darwin",
            'Asia/Yakutsk'            => "Yakutsk",
            'Australia/Brisbane'    => "Brisbane",
            'Australia/Canberra'    => "Canberra",
            'Pacific/Guam'            => "Guam",
            'Australia/Hobart'      => "Hobart",
            'Australia/Melbourne'  => "Melbourne",
            'Pacific/Port_Moresby' => "Port Moresby",
            'Australia/Sydney'      => "Sydney",
            'Asia/Vladivostok'      => "Vladivostok",
            'Asia/Magadan'            => "Magadan",
            'Pacific/Auckland'      => "Auckland",
            'Pacific/Fiji'            => "Fiji",
        ];

        // Build TZ list
        if (is_null($built)) {
            $builder = []; $now = new DateTime('now');
            foreach ($timezones as $timezoneID => $locationName) {
                try {
                    $timezone = new DateTimeZone($timezoneID);
                    $offset = $timezone->getOffset($now);
                    $location = $timezone->getLocation();
                    $transition = array_shift($t = $timezone->getTransitions($now->getTimestamp(), $now->getTimestamp()));
                    $offsetHours = ($offset / 3600);

                    $builderLabel = $offsetHours.'-'.$location['longitude'];
                    $builder[$builderLabel] = [
                        'Timezone' => $timezoneID,
                        'Label' => formatString("({Label}) {Location} {Abbreviation}", [
                            'Label' => 'GMT '.(($offsetHours >= 0) ? "+{$offsetHours}" : $offsetHours),
                            'Location' => $locationName,
                            'Abbreviation' => $transition['abbr']
                        ])
                    ];
                } catch (Exception $ex) {}
            }
            ksort($builder, SORT_NUMERIC);
            foreach ($builder as $buildTimezone) {
                $built[$buildTimezone['Timezone']] = trim($buildTimezone['Label']);
            }
        }

        if (is_null($lookupTimezone)) {
            return $built;
        }
        return getValue($lookupTimezone, $built);
    }

    /**
     * Add an event end date.
     *
     * if we don't have an endDate, set it to midnight of that day
     * make it to an all day event.
     *
     * @param array $eventData
     * @return array
     */
    public function calculateEventEndDate($eventData) {
        $startDate = $eventData['dateStarts'] ?? $eventData['DateStarts'] ?? false;
        $eventEndDateInfo = [];
        if ($startDate instanceof DateTimeInterface) {
            $endDate = $startDate->modify('1 Day');
            $eventEndDateInfo['DateEnds'] = $endDate->format('Y-m-d H:i:s');
        } else {
            $convertedStartDate = new DateTime($startDate);
            $convertedStartDate->modify('1 Day');
            $eventEndDateInfo['DateEnds'] = $convertedStartDate->format('Y-m-d H:i:s');
        }
        $eventEndDateInfo['AllDayEvent'] = 1;

        return $eventEndDateInfo;
    }

    /**
     * Format a date using the current timezone.
     *
     * This is sort of a stop-gap until the **Gdn_Format::*** methods.
     *
     * @param string $dateString
     * @param bool $from
     * @return array
     */
    public static function formatEventDate($dateString, $from = true) {
        if (!$dateString) {
            return ['', '', '', ''];
        }
        if (method_exists(Gdn::session(), 'getTimeZone')) {
            $tz = Gdn::session()->getTimeZone();
        } else {
            $tz = new DateTimeZone('UTC');
        }

        $timestamp = Gdn_Format::toTimestamp($dateString);
        if (!$timestamp) {
            return [false, false, false, false];
        }

        $dt = new DateTime('@'.$timestamp);
        $dt->setTimezone($tz);

        $offTimestamp = $timestamp + $dt->getOffset();

        $dateFormat = '%A, %B %e, %G';
        $dateStr = strftime($dateFormat, $offTimestamp);
        $timeFormat = t('Date.DefaultTimeFormat', '%l:%M%p');
        $timeStr = strftime($timeFormat, $offTimestamp);

        return [$dateStr, $timeStr, $dt->format('H:i'), $dt->format('c')];
    }

    /**
     * Delete an event.
     *
     * @param array|string $where
     * @param integer|bool $limit
     * @param boolean $resetData Unused.
     * @return Gdn_DataSet
     */
    public function delete($where = '', $limit = false, $resetData = false) {
        // Get list of matching events
        $matchEvents = $this->getWhere($where,'','',$limit);

        // Delete events
        $deleted = parent::delete($where, $limit ? ['limit' => $limit] : []);

        // Clean up UserEvents
        $eventIDs = [];
        foreach ($matchEvents as $event) {
            $eventIDs[] = val('EventID', $event);
        }
        $this->SQL->delete('UserEvent', ['EventID' => $eventIDs]);

        return $deleted;
    }
}
