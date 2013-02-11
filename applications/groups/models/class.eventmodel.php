<?php

/**
 * Groups Application - Event Model
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
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
    * Get events that this user is invited to
    * 
    * @param integer $UserID
    * @return type
    */
   public function GetByUser($UserID) {
      $UserGroups = $this->SQL->GetWhere('UserGroup', array('UserID' => $UserID))->ResultArray();
      $IDs = ConsolidateArrayValuesByKey($UserGroups, 'GroupID');
      
      $Result = $this->GetWhere(array('GroupID' => $IDs), 'Name')->ResultArray();
      return $Result;
   }
   
   /**
    * Get an event by ID
    * 
    * @param integer $EventID
    * @param integer $DatasetType
    * @return type
    */
   public function GetID($EventID, $DatasetType = DATASET_TYPE_ARRAY) {
      $EventID = self::ParseID($EventID);
      
      $Row = parent::GetID($EventID, $DatasetType);
      return $Row;
   }
   
   /**
    * Get events by date
    * 
    * @param strtotime $Future Relateive time offset. Like "+30 days"
    * @param array $Where
    * @param boolean $Ended Optional. Only events that have ended?
    * @return type
    */
   public function GetUpcoming($Future, $Where = NULL, $Ended = FALSE) {
      $UTC = new DateTimeZone('UTC');
      $StartDate = new DateTime('now', $UTC);
      if ($Future) {
         $LimitDate = new DateTime('now', $UTC);
         $LimitDate->modify($Future);
      }
      
      // Handle 'invited' state manually
      if ($InvitedUserID = GetValue('Invited', $Where)) {
         unset($Where['Invited']);
      }
      
      // Limit to a future date, but after right now
      if ($LimitDate > $StartDate) {
         $Where['DateStarts >'] = $StartDate->format('Y-m-d H:i:s');
         if ($Future)
            $Where['DateStarts <='] = $LimitDate->format('Y-m-d H:i:s');
      } else {
         $Where['DateStarts <'] = $StartDate->format('Y-m-d H:i:s');
         if ($Future)
            $Where['DateStarts >='] = $LimitDate->format('Y-m-d H:i:s');
      }
      
      // Only events that are over
      if ($Ended)
         $Where['DateEnds <='] = $StartDate->format('Y-m-d H:i:s');
      
      $EventsQuery = $this->SQL
         ->Select('e.*')
         ->Where($Where)
         ->OrderBy('DateStarts', 'asc');
      
      if ($InvitedUserID) {
         $EventsQuery
            ->From('UserEvent ue')
            ->Join('Event e', 'eu.EventID = e.EventID');
      } else {
         $EventsQuery->From('Event e');
      }
      
      return $EventsQuery->Get()->ResultArray();
   }
   
   /**
    * Check permission on a group.
    * 
    * @param string $Permission The permission to check. Valid values are:
    *  - Member: User is a member of the group.
    *  - Leader: User is a leader of the group.
    *  - Join: User can join the group.
    *  - Leave: User can leave the group.
    *  - Edit: The user may edit the group.
    *  - Delete: User can delete the group.
    *  - View: The user may view the group's contents.
    *  - Moderate: The user may moderate the group.
    * @param int $GroupID
    * @return boolean
    */
   public function CheckPermission($Permission, $GroupID) {
      static $Permissions = array();
      
      $UserID = Gdn::Session()->UserID;
      
      if (is_array($GroupID)) {
         $Group = $GroupID;
         $GroupID = $Group['GroupID'];
      }

      $Key = "{$UserID}-{$GroupID}";
      
      if (!isset($Permissions[$Key])) {
         // Get the data for the group.
         if (!isset($Group))
            $Group = $this->GetID($GroupID);
         
         if ($UserID) {
            $UserGroup = Gdn::SQL()->GetWhere('UserGroup', array('GroupID' => $GroupID, 'UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
            $GroupApplicant = Gdn::SQL()->GetWhere('GroupApplicant', array('GroupID' => $GroupID, 'UserID' => Gdn::Session()->UserID))->FirstRow(DATASET_TYPE_ARRAY);
         } else {
            $UserGroup = FALSE;
            $GroupApplicant = FALSE;
         }
         
         // Set the default permissions.
         $Perms = array(
            'Member' => FALSE,
            'Leader' => FALSE,
            'Join' => Gdn::Session()->IsValid(),
            'Leave' => FALSE,
            'Edit' => FALSE,
            'Delete' => FALSE,
            'Moderate' => FALSE,
            'View' => TRUE);
         
         // The group creator is always a member and leader.
         if ($UserID == $Group['InsertUserID']) {
            $Perms['Delete'] = TRUE;
            
            if (!$UserGroup)
               $UserGroup = array('Role' => 'Leader');
         }
            
         if ($UserGroup) {
            $Perms['Join'] = FALSE;
            $Perms['Join.Reason'] = T('You are already a member of this group.');
            
            $Perms['Member'] = TRUE;
            $Perms['Leader'] = ($UserGroup['Role'] == 'Leader');
            $Perms['Edit'] = $Perms['Leader'];
            $Perms['Moderate'] = $Perms['Leader'];
            
            if ($UserID != $Group['InsertUserID']) {
               $Perms['Leave'] = TRUE;
            } else {
               $Perms['Leave.Reason'] = T("You can't leave the group you started.");
            }
         } else {
            if ($Group['Visibility'] != 'Public') {
               $Perms['View'] = FALSE;
               $Perms['View.Reason'] = T('Join this group to view its content.');
            }
         }
         
         if ($GroupApplicant) {
            $Perms['Join'] = FALSE; // Already applied or banned.
            switch (strtolower($GroupApplicant['Type'])) {
               case 'application':
                  $Perms['Join.Reason'] = T("You've applied to join this group.");
                  break;
               case 'denied':
                  $Perms['Join.Reason'] = T("You're application for this group was denied.");
                  break;
               case 'ban':
                  $Perms['Join.Reason'] = T("You're banned from joining this group.");
                  break;
            }
         }
         
         // Moderators can view and edit all groups.
         if ($UserID == Gdn::Session()->UserID && Gdn::Session()->CheckPermission('Garden.Moderation.Manage')) {
            $Perms['Edit'] = TRUE;
            $Perms['Delete'] = TRUE;
            $Perms['View'] = TRUE;
            unset($Perms['View.Reason']);
            $Perms['Moderate'] = TRUE;
         }
         
         $Permissions[$Key] = $Perms;
      }
      
      $Perms = $Permissions[$Key];
      
      if (!$Permission)
         return $Perms;
      
      if (!isset($Perms[$Permission])) {
         if (strpos($Permission, '.Reason') === FALSE) {
            trigger_error("Invalid group permission $Permission.");
            return FALSE;
         } else {
            $Permission = StringEndsWith($Permission, '.Reason', TRUE, TRUE);
            if ($Perms[$Permission])
               return '';
            
            if (in_array($Permission, array('Member', 'Leader'))) {
               $Message = T(sprintf("You aren't a %s of this group.", strtolower($Permission)));
            } else {
               $Message = sprintf(T("You aren't allowed to %s this group."), T(strtolower($Permission)));
            }
            
            return $Message;
         }
      } else {
         return $Perms[$Permission];
      }
   }
   
   /**
    * Parse the ID out of a slug
    * 
    * @param type $ID
    * @return type
    */
   public static function ParseID($ID) {
      $Parts = explode('-', $ID, 2);
      return $Parts[0];
   }
   
   /**
    * Invite someone to an event
    * 
    * @param integer $UserID
    * @param integer $EventID
    */
   public function Invite($UserID, $EventID) {
      return $this->SQL->Insert('UserEvent', array(
         'EventID'      => $EventID,
         'UserID'       => $UserID,
         'Attending'    => 'Invited'
      ));
   }
   
   /**
    * Invite an entire group to this event
    * 
    * @param integer $EventID
    * @param integer $GroupID
    */
   public function InviteGroup($EventID, $GroupID) {
      $Event = $this->GetID($EventID, DATASET_TYPE_ARRAY);
      $GroupModel = new GroupModel();
      $GroupMembers = $GroupModel->GetMembers($GroupID);
      
      // Notify the users of the invitation
      $ActivityModel = new ActivityModel();
      foreach ($GroupMembers as $GroupMember) {
         $ActivityID = $ActivityModel->Add(
            Gdn::Session()->UserID,
            'Events',
            '',
            $GroupMember['UserID'],
            '',
            EventUrl($Event),
            FALSE
         );
         
         $Story = GetValue('Name', $Event, '');
         $ActivityModel->SendNotification($ActivityID, $Story);
      }
   }
   
   /**
    * Get list of invited
    * @param type $EventID
    * @return type
    */
   public function Invited($EventID) {
      $CollapsedInvited = $this->SQL->GetWhere('UserEvent', array(
         'EventID'   => $EventID
      ))->ResultArray();
      Gdn::UserModel()->JoinUsers($CollapsedInvited, array('UserID'));
      $Invited = array();
      foreach ($CollapsedInvited as $Invitee)
         $Invited[$Invitee['Attending']][] = $Invitee;
      return $Invited;
   }
   
   /**
    * Check if a User is invited to an Event
    * 
    * @param integer $UserID
    * @param integer $EventID
    */
   public function IsInvited($UserID, $EventID) {
      $IsInvited = $this->SQL->GetWhere('UserEvent', array(
         'UserID'    => $UserID,
         'EventID'   => $EventID
      ))->FirstRow(DATASET_TYPE_ARRAY);
      $IsInvited = GetValue('Attending', $IsInvited, FALSE);
      return $IsInvited;
   }
   
   /**
    * Change user attending status for event
    * 
    * @param integer $UserID
    * @param integer $EventID
    * @param enum $Attending [Yes, No, Maybe, Invited]
    */
   public function Attend($UserID, $EventID, $Attending) {
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "insert into {$Px}UserEvent (EventID, UserID, DateInserted, Attending)
         values (:EventID, :UserID, :DateInserted, :Attending)
         on duplicate key update Attending = :Attending1";
      
      $this->Database->Query($Sql, array(
         ':EventID'      => $EventID,
         ':UserID'       => $UserID,
         ':DateInserted' => date('Y-m-d H:i:s'),
         ':Attending'    => $Attending,
         ':Attending1'   => $Attending
      ));
   }
   
   /**
    * Override event save
    * 
    * Set 'Fix' = FALSE to bypass date munging
    * 
    * @param array $Event
    */
   public function Save($Event) {
      
      // Fix malformed or partial dates
      if (GetValue('Fix', $Event, TRUE)) {
         
         $Event['AllDayEvent'] = 0;
         
         // Get some Timezone objects
         $Timezone = new DateTimeZone($Event['Timezone']);
         $UTC = new DateTimeZone('UTC');

         // Check if DateStarts triggers 'AllDay' mode
         if (!empty($Event['DateStarts'])) {
            
            if (empty($Event['TimeStarts']))
               $Event['AllDayEvent'] = 1;
            
         } else { unset($Event['DateStarts']); }

         // Check if DateEnds triggers 'AllDay' mode
         if (!empty($Event['DateEnds'])) {
            
            if (empty($Event['TimeEnds']))
               $Event['AllDayEvent'] = 1;
            
         } else { unset($Event['DateEnds']); }

         // If we're 'AllDay', munge the times to midnight
         $ForceEndTime = FALSE;
         if ($Event['AllDayEvent']) {
            $Event['TimeStarts'] = '12:00am';
            if (empty($Event['TimeEnds'])) {
               $Event['TimeEnds'] = '11:59pm';
               $ForceEndTime = TRUE;
            }
         }
         
         $InputDateFormat = '!m/d/Y h:ia';
         $OneDay = new DateInterval('P1D');
         
         // Load and format start date
         try {
            $EventDateStartsStr = "{$Event['DateStarts']} {$Event['TimeStarts']}";
            $EventDateStarts = DateTime::createFromFormat($InputDateFormat, $EventDateStartsStr, $Timezone);
            if (!$EventDateStarts) throw new Exception();
            $EventDateStarts->setTimezone($UTC);
            $Event['DateStarts'] = $EventDateStarts->format('Y-m-d H:i:00');
         } catch (Exception $Ex) {
            $this->Validation->AddValidationResult('DateStarts', 'ValidateDate');
         }

         // Load and format end date
         try {
            // Force a sane end date
            if (!isset($Event['DateEnds']) || is_null($Event['DateEnds'])) {
               $DateEnds = DateTime::createFromFormat($InputDateFormat, $EventDateStartsStr, $Timezone);
               $DateEnds->modify($Event['TimeEnds']);
               $ForceEndTime = FALSE;

               $Event['DateEnds'] = $DateEnds->format('m/d/Y');
               $Event['TimeEnds'] = $DateEnds->format('h:ia');
               unset($DateEnds);
            }

            $EventDateEndsStr = "{$Event['DateEnds']} {$Event['TimeEnds']}";
            $EventDateEnds = DateTime::createFromFormat($InputDateFormat, $EventDateEndsStr, $Timezone);
            if (!$EventDateEnds) throw new Exception();
            $EventDateEnds->setTimezone($UTC);
            $Event['DateEnds'] = $EventDateEnds->format('Y-m-d H:i:00');
         } catch (Exception $Ex) {
            $this->Validation->AddValidationResult('DateEnds', 'ValidateDate');
         }
         
         // Fix clean up
         unset($OneDay);
         unset($EventDateStarts);
         unset($EventDateEnds);
         unset($Timezone);
         unset($UTC);
      }
      
      // Default clean up
      unset($Event['TimeStarts']);
      unset($Event['TimeEnds']);
      
      $this->Validation->ApplyRule('DateStarts', 'ValidateDate');
      $this->Validation->ApplyRule('DateEnds', 'ValidateDate');
      
      return parent::Save($Event);
   }
   
   /**
    * Get precompiled timezone list
    * 
    * @staticvar array $Built
    * @staticvar array $Timezones
    * @return array
    */
   public static function Timezones($LookupTimezone = NULL) {
      static $Built = NULL;
      static $Timezones = array(
         'Pacific/Midway'       => "Midway Island",
         'US/Samoa'             => "Samoa",
         'US/Hawaii'            => "Hawaii",
         'US/Alaska'            => "Alaska",
         'US/Pacific'           => "Pacific Time",
         'America/Tijuana'      => "Tijuana",
         'US/Arizona'           => "Arizona",
         'US/Mountain'          => "Mountain Time",
         'America/Chihuahua'    => "Chihuahua",
         'America/Mazatlan'     => "Mazatlan",
         'America/Mexico_City'  => "Mexico City",
         'America/Monterrey'    => "Monterrey",
         'Canada/Saskatchewan'  => "Saskatchewan",
         'US/Central'           => "Central Time",
         'US/Eastern'           => "Eastern Time",
         'US/East-Indiana'      => "Indiana (East)",
         'America/Bogota'       => "Bogota",
         'America/Lima'         => "Lima",
         'America/Caracas'      => "Caracas",
         'Canada/Atlantic'      => "Atlantic Time",
         'America/La_Paz'       => "La Paz",
         'America/Santiago'     => "Santiago",
         'Canada/Newfoundland'  => "Newfoundland",
         'America/Buenos_Aires' => "Buenos Aires",
         'Greenland'            => "Greenland",
         'Atlantic/Stanley'     => "Stanley",
         'Atlantic/Azores'      => "Azores",
         'Atlantic/Cape_Verde'  => "Cape Verde Is.",
         'Africa/Casablanca'    => "Casablanca",
         'Europe/Dublin'        => "Dublin",
         'Europe/Lisbon'        => "Lisbon",
         'Europe/London'        => "London",
         'Africa/Monrovia'      => "Monrovia",
         'Europe/Amsterdam'     => "Amsterdam",
         'Europe/Belgrade'      => "Belgrade",
         'Europe/Berlin'        => "Berlin",
         'Europe/Bratislava'    => "Bratislava",
         'Europe/Brussels'      => "Brussels",
         'Europe/Budapest'      => "Budapest",
         'Europe/Copenhagen'    => "Copenhagen",
         'Europe/Ljubljana'     => "Ljubljana",
         'Europe/Madrid'        => "Madrid",
         'Europe/Paris'         => "Paris",
         'Europe/Prague'        => "Prague",
         'Europe/Rome'          => "Rome",
         'Europe/Sarajevo'      => "Sarajevo",
         'Europe/Skopje'        => "Skopje",
         'Europe/Stockholm'     => "Stockholm",
         'Europe/Vienna'        => "Vienna",
         'Europe/Warsaw'        => "Warsaw",
         'Europe/Zagreb'        => "Zagreb",
         'Europe/Athens'        => "Athens",
         'Europe/Bucharest'     => "Bucharest",
         'Africa/Cairo'         => "Cairo",
         'Africa/Harare'        => "Harare",
         'Europe/Helsinki'      => "Helsinki",
         'Europe/Istanbul'      => "Istanbul",
         'Asia/Jerusalem'       => "Jerusalem",
         'Europe/Kiev'          => "Kyiv",
         'Europe/Minsk'         => "Minsk",
         'Europe/Riga'          => "Riga",
         'Europe/Sofia'         => "Sofia",
         'Europe/Tallinn'       => "Tallinn",
         'Europe/Vilnius'       => "Vilnius",
         'Asia/Baghdad'         => "Baghdad",
         'Asia/Kuwait'          => "Kuwait",
         'Africa/Nairobi'       => "Nairobi",
         'Asia/Riyadh'          => "Riyadh",
         'Asia/Tehran'          => "Tehran",
         'Europe/Moscow'        => "Moscow",
         'Asia/Baku'            => "Baku",
         'Europe/Volgograd'     => "Volgograd",
         'Asia/Muscat'          => "Muscat",
         'Asia/Tbilisi'         => "Tbilisi",
         'Asia/Yerevan'         => "Yerevan",
         'Asia/Kabul'           => "Kabul",
         'Asia/Karachi'         => "Karachi",
         'Asia/Tashkent'        => "Tashkent",
         'Asia/Kolkata'         => "Kolkata",
         'Asia/Kathmandu'       => "Kathmandu",
         'Asia/Yekaterinburg'   => "Ekaterinburg",
         'Asia/Almaty'          => "Almaty",
         'Asia/Dhaka'           => "Dhaka",
         'Asia/Novosibirsk'     => "Novosibirsk",
         'Asia/Bangkok'         => "Bangkok",
         'Asia/Jakarta'         => "Jakarta",
         'Asia/Krasnoyarsk'     => "Krasnoyarsk",
         'Asia/Chongqing'       => "Chongqing",
         'Asia/Hong_Kong'       => "Hong Kong",
         'Asia/Kuala_Lumpur'    => "Kuala Lumpur",
         'Australia/Perth'      => "Perth",
         'Asia/Singapore'       => "Singapore",
         'Asia/Taipei'          => "Taipei",
         'Asia/Ulaanbaatar'     => "Ulaan Bataar",
         'Asia/Urumqi'          => "Urumqi",
         'Asia/Irkutsk'         => "Irkutsk",
         'Asia/Seoul'           => "Seoul",
         'Asia/Tokyo'           => "Tokyo",
         'Australia/Adelaide'   => "Adelaide",
         'Australia/Darwin'     => "Darwin",
         'Asia/Yakutsk'         => "Yakutsk",
         'Australia/Brisbane'   => "Brisbane",
         'Australia/Canberra'   => "Canberra",
         'Pacific/Guam'         => "Guam",
         'Australia/Hobart'     => "Hobart",
         'Australia/Melbourne'  => "Melbourne",
         'Pacific/Port_Moresby' => "Port Moresby",
         'Australia/Sydney'     => "Sydney",
         'Asia/Vladivostok'     => "Vladivostok",
         'Asia/Magadan'         => "Magadan",
         'Pacific/Auckland'     => "Auckland",
         'Pacific/Fiji'         => "Fiji",
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
               
               $Builder[$BuilderLabel] = array(
                  'Timezone'  => $TimezoneID,
                  'Label'     => FormatString("({Label}) {Location} {Abbreviation}", array(
                     'Label'        => 'GMT '.(($OffsetHours >= 0) ? "+{$OffsetHours}" : $OffsetHours),
                     'Location'     => $LocationName,
                     'Abbreviation' => $Transition['abbr']
                  ))
               );
            } catch (Exception $Ex) {}
         }
         ksort($Builder, SORT_NUMERIC);
         foreach ($Builder as $BuildTimezone)
            $Built[$BuildTimezone['Timezone']] = trim($BuildTimezone['Label']);
      }
      
      if (is_null($LookupTimezone))
         return $Built;
      return GetValue($LookupTimezone, $Built);
   }
   
}
