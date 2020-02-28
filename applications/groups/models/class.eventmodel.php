<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Event Model
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package groups
 * @since 1.0
 */
class EventModel extends Gdn_Model {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     */
    public function __construct() {
        parent::__construct('Event');
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
     * @return type
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
     * Check permission on a event.
     *
     * @param string $permission The permission to check. Valid values are:
     *  - Organizer: User is a leader of the event.
     *  - Member: User is a member of the event.
     *  - Create: User can create events.
     *  - Edit: User can edit the event.
     *  - View: The user may view the event's contents.
     * @param int|array $eventID The event ID of the event record
     * @param int|null $userID
     * @return boolean
     */
    public function checkPermission($permission, $eventID, $userID = null) {
        static $permissions = [];

        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        if (is_array($eventID)) {
            $event = $eventID;
            $eventID = $event['EventID'];
        }

        $key = "{$userID}-{$eventID}";
        if (!isset($permissions[$key])) {
            // Get the data for the group.
            if (!isset($event)) {
                $event = $this->getID($eventID);
            }

            $userEvent = false;
            if ($userID) {
                $userEvent = Gdn::sql()
                    ->getWhere('UserEvent', ['EventID' => $eventID, 'UserID' => $userID])
                    ->firstRow(DATASET_TYPE_ARRAY);
            }

            // Set the default permissions.
            $perms = [
                'Organizer' => false,
                'Create' => true,
                'Edit' => false,
                'Member' => false,
                'View' => true
            ];

            // The group creator is always a member and leader.
            if ($userID == $event['InsertUserID']) {
                $perms['Organizer'] = true;
                $perms['Edit'] = true;
                $perms['Member'] = true;
                $perms['View'] = true;
            }

            if ($userEvent) {
                $perms['Member'] = true;
                $perms['View'] = true;
            } else {
                // Check if we're in a group
                $eventGroupID = val('GroupID', $event, null);
                if ($eventGroupID) {
                    $groupModel = new GroupModel();
                    $eventGroup = $groupModel->getID($eventGroupID);

                    if (groupPermission('Member', $eventGroupID)) {
                        $perms['Member'] = true;
                        $perms['View'] = true;
                    } else {
                        $perms['Create'] = false;
                    }
                }

            }

            // Allow Admins to restrict event creation to leaders only.
            if (!c('Groups.Members.CanAddEvents', true) && !groupPermission('Leader', $eventGroupID)) {
                $perms['Create'] = false;
            }

            // Moderators can view and edit all events.
            if ($userID == Gdn::session()->UserID && checkPermission('Garden.Moderation.Manage')) {
                $perms['Edit'] = true;
                $perms['View'] = true;
            }
            $permissions[$key] = $perms;
        }

        $perms = $permissions[$key];

        if (!$permission) {
            return $perms;
        }

        if (!isset($perms[$permission])) {
            if (strpos($permission, '.Reason') === false) {
                trigger_error("Invalid group permission $permission.");
                return false;
            } else {
                $permission = stringEndsWith($permission, '.Reason', true, true);
                if ($perms[$permission]) {
                    return '';
                }

                if (in_array($permission, ['Member', 'Leader'])) {
                    $message = t(sprintf("You aren't a %s of this event.", strtolower($permission)));
                } else {
                    $message = sprintf(t("You aren't allowed to %s this event."), t(strtolower($permission)));
                }

                return $message;
            }
        } else {
            return $perms[$permission];
        }
    }

    /**
     * Checks to see whether or not a user can create events for a group.
     *
     * @param int $groupID
     * @return bool
     */
    public function canCreateEvents(int $groupID): bool {
        if (groupPermission('Leader', $groupID)) {
            return true;
        } elseif (c('Groups.Members.CanAddEvents', true) && groupPermission('Member', $groupID)) {
            return true;
        } else {
            return false;
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
     * Invite an entire group to this event.
     *
     * @param integer $eventID
     * @param integer $groupID
     */
    public function inviteGroup($eventID, $groupID) {
        return;
        $event = $this->getID($eventID, DATASET_TYPE_ARRAY);
        $groupModel = new GroupModel();
        $groupMembers = $groupModel->getMembers($groupID);

        // Notify the users of the invitation
        $activityModel = new ActivityModel();
        $activity = [
            'ActivityType' => 'Events',
            'ActivityUserID' => $event['InsertUserID'],
            'HeadlineFormat' => t('Activity.NewEvent', '{ActivityUserID,User} added a new event: <a href="{Url,html}">{Data.Name,text}</a>.'),
            'RecordType' => 'Event',
            'RecordID' => 'EventID',
            'Route' => eventUrl($event),
            'Data' => ['Name' => $event['Name']]
        ];

        foreach ($groupMembers as $groupMember) {
            $activity['NotifyUserID'] = $groupMember['UserID'];
            $activityID = $activityModel->queue($activity);
        }
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
        Gdn::userModel()->joinUsers($collapsedInvited, ['UserID']);
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
     * Override event save.
     *
     * Set 'Fix' = false to bypass date munging
     *
     * @param array $event
     * @param array $settings Base class compatibility.
     * @return array
     */
    public function save($event, $settings = []) {
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
