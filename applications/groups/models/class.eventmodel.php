<?php

class EventModel extends Gdn_Model {
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @access public
    */
   public function __construct() {
      parent::__construct('Event');
   }
   
   public function GetByUser($UserID) {
      $UserGroups = $this->SQL->GetWhere('UserGroup', array('UserID' => $UserID))->ResultArray();
      $IDs = ConsolidateArrayValuesByKey($UserGroups, 'GroupID');
      
      $Result = $this->GetWhere(array('GroupID' => $IDs), 'Name')->ResultArray();
      return $Result;
   }
   
   public function GetID($ID, $DatasetType = DATASET_TYPE_ARRAY) {
      $ID = self::ParseID($ID);
      
      $Row = parent::GetID($ID, $DatasetType);
      return $Row;
   }
   
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
    * @param array $Event
    */
   public function Save($Event) {
      
      // Force a sane end date
      if (!isset($Event['DateEnds']) || is_null($Event['DateEnds'])) {
         $UTC = new DateTimeZone('UTC');
         $DateStartsTime = strtotime($Event['DateStarts']);
         $DateEndsTime = strtotime('midnight tomorrow', $DateStartsTime);
         $DateEnds = DateTime::createFromFormat('U', $DateEndsTime, $UTC);
         
         $Event['DateEnds'] = $DateEnds->format('Y-m-d H:i:s');
      }
      
      parent::Save($Event);
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
