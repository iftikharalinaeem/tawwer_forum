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
     * @param integer $UserID
     * @return type
     */
    public function getByUser($UserID) {
        $UserGroups = $this->SQL->getWhere('UserGroup', array('UserID' => $UserID))->resultArray();
        $IDs = array_column($UserGroups, 'GroupID');

        $Result = $this->getWhere(array('GroupID' => $IDs), 'Name')->resultArray();
        return $Result;
    }

    /**
     * Get an event by ID.
     *
     * @param integer $EventID
     * @param integer $DatasetType
     * @param array $options Base class compatibility.
     * @return type
     */
    public function getID($EventID, $DatasetType = DATASET_TYPE_ARRAY, $options = []) {
        $EventID = self::parseID($EventID);

        $Row = parent::getID($EventID, $DatasetType);
        return $Row;
    }

    /**
     * Get events by date.
     *
     * @param strtotime $Future Relative time offset. Like "+30 days"
     * @param array $Where
     * @param boolean $Ended Optional. Only events that have due to their end date?
     *      (End date is optional so setting this will exclude events with no end date.)
     * @return type
     */
    public function getUpcoming($Future, $Where = null, $Ended = null) {
        $UTC = new DateTimeZone('UTC');
        $StartDate = new DateTime('now', $UTC);
        if ($Future) {
            $LimitDate = new DateTime('now', $UTC);
            $LimitDate->modify($Future);
        }

        // Handle 'invited' state manually
        if ($InvitedUserID = val('Invited', $Where)) {
            unset($Where['Invited']);
        }

        // Limit to a future date, but after right now
        if ($LimitDate > $StartDate) {
            if ($Ended === false) {
                $Where['DateEnds >='] = $StartDate->format('Y-m-d H:i:s');
            } else {
                $Where['DateStarts >='] = $StartDate->format('Y-m-d H:i:s');
            }

            if ($Future) {
                $Where['DateStarts <='] = $LimitDate->format('Y-m-d H:i:s');
            }
        } else {
            $Where['DateStarts <'] = $StartDate->format('Y-m-d H:i:s');
            if ($Future) {
                $Where['DateStarts >='] = $LimitDate->format('Y-m-d H:i:s');
            }
        }

        // Only events that are over
        if ($Ended) {
            $Where['DateEnds <='] = $StartDate->format('Y-m-d H:i:s');
        }

        $EventsQuery = $this->SQL
            ->select('e.*')
            ->where($Where)
            ->orderBy('DateStarts', 'asc');

        if ($InvitedUserID) {
            $EventsQuery
                ->from('UserEvent ue')
                ->join('Event e', 'ue.EventID = e.EventID');
        } else {
            $EventsQuery->from('Event e');
        }

        return $EventsQuery->get()->resultArray();
    }

    /**
     * Check permission on a event.
     *
     * @param string $Permission The permission to check. Valid values are:
     *  - Organizer: User is a leader of the event.
     *  - Member: User is a member of the event.
     *  - Create: User can create events.
     *  - Edit: User can edit the event.
     *  - View: The user may view the event's contents.
     * @param int $EventID
     * @return boolean
     */
    public function checkPermission($Permission, $EventID) {
        static $Permissions = array();

        $UserID = Gdn::session()->UserID;

        if (is_array($EventID)) {
            $Event = $EventID;
            $EventID = $Event['EventID'];
        }

        $Key = "{$UserID}-{$EventID}";
        if (!isset($Permissions[$Key])) {
            // Get the data for the group.
            if (!isset($Event)) {
                $Event = $this->getID($EventID);
            }

            $UserEvent = false;
            if ($UserID) {
                $UserEvent = Gdn::sql()
                    ->getWhere('UserEvent', array('EventID' => $EventID, 'UserID' => $UserID))
                    ->firstRow(DATASET_TYPE_ARRAY);
            }

            // Set the default permissions.
            $Perms = [
                'Organizer' => false,
                'Create' => true,
                'Edit' => false,
                'Member' => false,
                'View' => true
            ];

            // The group creator is always a member and leader.
            if ($UserID == $Event['InsertUserID']) {
                $Perms['Organizer'] = true;
                $Perms['Edit'] = true;
                $Perms['Member'] = true;
                $Perms['View'] = true;
            }

            if ($UserEvent) {
                $Perms['Member'] = true;
                $Perms['View'] = true;
            } else {
                // Check if we're in a group
                $EventGroupID = val('GroupID', $Event, null);
                if ($EventGroupID) {
                    $GroupModel = new GroupModel();
                    $EventGroup = $GroupModel->getID($EventGroupID);

                    if (groupPermission('Member', $EventGroupID)) {
                        $Perms['Member'] = true;
                        $Perms['View'] = true;
                    } else {
                        $Perms['Create'] = false;
                    }
                }

            }

            // Moderators can view and edit all events.
            if ($UserID == Gdn::session()->UserID && checkPermission('Garden.Moderation.Manage')) {
                $Perms['Edit'] = true;
                $Perms['View'] = true;
            }
            $Permissions[$Key] = $Perms;
        }

        $Perms = $Permissions[$Key];

        if (!$Permission) {
            return $Perms;
        }

        if (!isset($Perms[$Permission])) {
            if (strpos($Permission, '.Reason') === false) {
                trigger_error("Invalid group permission $Permission.");
                return false;
            } else {
                $Permission = StringEndsWith($Permission, '.Reason', true, true);
                if ($Perms[$Permission]) {
                    return '';
                }

                if (in_array($Permission, ['Member', 'Leader'])) {
                    $Message = t(sprintf("You aren't a %s of this event.", strtolower($Permission)));
                } else {
                    $Message = sprintf(t("You aren't allowed to %s this event."), t(strtolower($Permission)));
                }

                return $Message;
            }
        } else {
            return $Perms[$Permission];
        }
    }

    /**
     * Parse the ID out of a slug.
     *
     * @param type $ID
     * @return type
     */
    public static function parseID($ID) {
        $Parts = explode('-', $ID, 2);
        return $Parts[0];
    }

    /**
     * Invite someone to an event.
     *
     * @param integer $UserID
     * @param integer $EventID
     * @return int
     */
    public function invite($UserID, $EventID) {
        return $this->SQL->insert('UserEvent', [
            'EventID' => $EventID,
            'UserID' => $UserID,
            'Attending' => 'Invited'
        ]);
    }

    /**
     * Invite an entire group to this event.
     *
     * @param integer $EventID
     * @param integer $GroupID
     */
    public function inviteGroup($EventID, $GroupID) {
        return;
        $Event = $this->getID($EventID, DATASET_TYPE_ARRAY);
        $GroupModel = new GroupModel();
        $GroupMembers = $GroupModel->getMembers($GroupID);

        // Notify the users of the invitation
        $ActivityModel = new ActivityModel();
        $Activity = [
            'ActivityType' => 'Events',
            'ActivityUserID' => $Event['InsertUserID'],
            'HeadlineFormat' => t('Activity.NewEvent', '{ActivityUserID,User} added a new event: <a href="{Url,html}">{Data.Name,text}</a>.'),
            'RecordType' => 'Event',
            'RecordID' => 'EventID',
            'Route' => eventUrl($Event),
            'Data' => ['Name' => $Event['Name']]
        ];

        foreach ($GroupMembers as $GroupMember) {
            $Activity['NotifyUserID'] = $GroupMember['UserID'];
            $ActivityID = $ActivityModel->Queue($Activity);
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
     * @param type $EventID
     * @return type
     */
    public function invited($EventID) {
        $CollapsedInvited = $this->SQL->getWhere('UserEvent', ['EventID' => $EventID])->resultArray();
        Gdn::userModel()->joinUsers($CollapsedInvited, ['UserID']);
        $Invited = [];
        foreach ($CollapsedInvited as $Invitee) {
            $Invited[$Invitee['Attending']][] = $Invitee;
        }
        return $Invited;
    }

    /**
     * Check if a User is invited to an Event.
     *
     * @param integer $UserID
     * @param integer $EventID
     * @return bool
     */
    public function isInvited($UserID, $EventID) {
        $IsInvited = $this->SQL
            ->getWhere('UserEvent', ['UserID' => $UserID, 'EventID' => $EventID])
            ->firstRow(DATASET_TYPE_ARRAY);
        $IsInvited = val('Attending', $IsInvited, false);

        return $IsInvited;
    }

    /**
     * Change user attending status for event.
     *
     * @param integer $UserID
     * @param integer $EventID
     * @param enum $Attending [Yes, No, Maybe, Invited]
     */
    public function attend($UserID, $EventID, $Attending) {
        $Px = Gdn::database()->DatabasePrefix;
        $Sql = "insert into {$Px}UserEvent (EventID, UserID, DateInserted, Attending)
            values (:EventID, :UserID, :DateInserted, :Attending)
            on duplicate key update Attending = :Attending1";

        $this->Database->query($Sql, [
            ':EventID' => $EventID,
            ':UserID' => $UserID,
            ':DateInserted' => date('Y-m-d H:i:s'),
            ':Attending' => $Attending,
            ':Attending1' => $Attending
        ]);
    }

    /**
     * Override event save.
     *
     * Set 'Fix' = false to bypass date munging
     *
     * @param array $Event
     * @param array $settings Base class compatibility.
     * @return array
     */
    public function save($Event, $settings = []) {
        // Fix the dates.
        if (!empty($Event['DateStarts'])) {
            $ts = strtotime($Event['DateStarts']);
            if ($ts !== false) {
                $Event['DateStarts'] = Gdn_Format::toDateTime($ts);
            }
        }
        if (!empty($Event['DateEnds'])) {
            $ts = strtotime($Event['DateEnds']);
            if ($ts !== false) {
                $Event['DateEnds'] = Gdn_Format::toDateTime($ts);
            }
        }

        // Add a timezone in case the database wasn't updated.
        touchValue('Timezone', $Event, Gdn::session()->getTimeZone()->getName());

        $this->Validation->applyRule('DateStarts', 'ValidateDate');
        $this->Validation->applyRule('DateEnds', 'ValidateDate');

        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $PrimaryKeyVal = val($this->PrimaryKey, $Event, false);
        $Insert = $PrimaryKeyVal == false ? true : false;
        if ($Insert) {
            $this->addInsertFields($Event);
        } else {
            $this->addUpdateFields($Event);
        }

        // Validate the form posted values
        $isValid = $this->validate($Event, $Insert) === true;
        $this->EventArguments['IsValid'] = &$isValid;
        $this->EventArguments['Fields'] = &$Event;
        $this->fireEvent('AfterValidateEvent');

        if (!$isValid) {
            return false;
        }

        return parent::save($Event);
    }

    /**
     * Get precompiled timezone list.
     *
     * @staticvar array $Built
     * @staticvar array $Timezones
     * @return array
     */
    public static function timezones($LookupTimezone = null) {
        static $Built = null;

        static $Timezones = array(
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
        );

        // Build TZ list
        if (is_null($Built)) {
            $Builder = array(); $Now = new DateTime('now');
            foreach ($Timezones as $TimezoneID => $LocationName) {
                try {
                    $Timezone = new DateTimeZone($TimezoneID);
                    $Offset = $Timezone->getOffset($Now);
                    $Location = $Timezone->getLocation();
                    $Transition = array_shift($T = $Timezone->getTransitions($Now->getTimestamp(), $Now->getTimestamp()));
                    $OffsetHours = ($Offset / 3600);

                    $BuilderLabel = $OffsetHours.'-'.$Location['longitude'];
                    $Builder[$BuilderLabel] = [
                        'Timezone' => $TimezoneID,
                        'Label' => formatString("({Label}) {Location} {Abbreviation}", [
                            'Label' => 'GMT '.(($OffsetHours >= 0) ? "+{$OffsetHours}" : $OffsetHours),
                            'Location' => $LocationName,
                            'Abbreviation' => $Transition['abbr']
                        ])
                    ];
                } catch (Exception $Ex) {}
            }
            ksort($Builder, SORT_NUMERIC);
            foreach ($Builder as $BuildTimezone) {
                $Built[$BuildTimezone['Timezone']] = trim($BuildTimezone['Label']);
            }
        }

        if (is_null($LookupTimezone)) {
            return $Built;
        }
        return GetValue($LookupTimezone, $Built);
    }

    /**
     * Delete an event.
     *
     * @param array|string $Where
     * @param integer|bool $Limit
     * @param boolean $ResetData Unused.
     * @return Gdn_DataSet
     */
    public function delete($Where = '', $Limit = false, $ResetData = false) {
        // Get list of matching events
        $MatchEvents = $this->getWhere($Where,'','',$Limit);

        // Delete events
        $Deleted = parent::delete($Where, $Limit ? ['limit' => $Limit] : []);

        // Clean up UserEvents
        $EventIDs = [];
        foreach ($MatchEvents as $Event) {
            $EventIDs[] = val('EventID', $Event);
        }
        $this->SQL->delete('UserEvent', ['EventID' => $EventIDs]);

        return $Deleted;
    }

}
