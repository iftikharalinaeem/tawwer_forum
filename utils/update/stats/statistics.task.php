<?php
class StatisticsTask extends Task {

   protected $TrackedItems = array(
      'comments'        => 'Comment',
      'discussions'     => 'Discussion',
      'registrations'   => 'User'
   );

   const RESOLUTION_HOUR = 'hour';
   const RESOLUTION_DAY = 'day';
   const RESOLUTION_WEEK = 'week';
   const RESOLUTION_MONTH = 'month';
   
   const FILL_ZERO = 'zero';
   const FILL_NULL = 'null';

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }

   protected function Run() {
      TaskList::Event("Updating site stats... ", TaskList::NOBREAK);
      $SiteID = isset($this->ClientInfo['SiteID']) ? $this->ClientInfo['SiteID'] : FALSE;
      if ($SiteID === FALSE) {
         TaskList::Event("site not found");
         return;
      }
      
      // Switch to the client's database.
      mysql_select_db($this->ClientInfo['DatabaseName'], $this->Database);
      
      $Tables = mysql_query("SHOW TABLES LIKE 'GDN_Statistics'", $this->Database);
      if (!mysql_num_rows($Tables)) {
         mysql_query("CREATE TABLE `GDN_Statistics` (
           `DateRangeStart` datetime NOT NULL,
           `DateRangeEnd` datetime NOT NULL,
           `DateRangeType` enum('hour','day','week','month') collate utf8_unicode_ci NOT NULL,
           `IndexType` varchar(32) collate utf8_unicode_ci NOT NULL,
           `IndexQualifier` varchar(32) collate utf8_unicode_ci default NULL,
           `IndexValue` int(11) default NULL,
           `DateUpdated` datetime default NULL,
           UNIQUE KEY `UX_Statistics` (`DateRangeStart`,`DateRangeEnd`,`DateRangeType`,`IndexType`,`IndexQualifier`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci", $this->Database);
      }

      $Success = TRUE;
      foreach ($this->TrackedItems as $TrackType => $TrackTable) {
               
         try {
            $Status = $this->CatchupGeneric($TrackType);
            if (!$Status)
               throw new Exception();

         } catch(Exception $e) {
            TaskList::Event("failed");
            $Success = FALSE;
            break;
         }
      }
      
      if ($Success)
         TaskList::Event("complete");

      // Select vfcom again.
      mysql_select_db(DATABASE_MAIN, $this->Database);

      return;
   }
   
   protected function CatchupGeneric($TrackType) {

      $Type = $this->TrackedItems[$TrackType];
      
      $FirstDate = mysql_query("SELECT DateInserted FROM GDN_{$Type} 
         ORDER BY DateInserted ASC LIMIT 1 OFFSET 0", $this->Database);
      if (!mysql_num_rows($FirstDate)) return TRUE;
      $FirstDate = mysql_fetch_assoc($FirstDate); $FirstDate = $FirstDate['DateInserted'];
      
      $LastDate = mysql_query("SELECT DateInserted FROM GDN_{$Type} 
         ORDER BY DateInserted ASC LIMIT 1 OFFSET 0", $this->Database);
      if (!mysql_num_rows($LastDate)) return TRUE;
      $LastDate = mysql_fetch_assoc($LastDate); $LastDate = $LastDate['DateInserted'];
            
      $LastHour = self::DateFormatByResolution($LastDate, self::RESOLUTION_HOUR);
      $LastHourValue = strtotime($LastHour);
      
      $FinalBlock = self::NextDate($LastHour, self::RESOLUTION_HOUR);
      $FinalBlockValue = strtotime($FinalBlock);
      
      // Clear data for this tracktype
      mysql_query("DELETE FROM GDN_Statistics WHERE IndexType='{$TrackType}'", $this->Database);
      
      // Loop over lowest denomination chunks and use intelligent summing for larger blocks
      $CurrentHour = self::DateFormatByResolution($FirstDate, self::RESOLUTION_HOUR);
      do {
         $NextHour = self::NextDate($CurrentHour, self::RESOLUTION_HOUR);
         $ItemResult = mysql_query("SELECT COUNT(DateInserted) AS Hits FROM GDN_{$Type} WHERE
            DateInserted >= '{$CurrentHour}' AND
            DateInserted < '{$NextHour}'", $this->Database);

         if (mysql_num_rows($ItemResult)) {
            $Item = mysql_fetch_assoc($ItemResult); $Hits = $Item['Hits'];
            $this->CachedTrackEvent($TrackType, 'none', $CurrentHour, $Items['Hits']);
         }
         
         $CurrentHour = $NextHour;
         $NextHourValue = strtotime($NextHour);
      } while ($NextHourValue <= $FinalBlockValue);
      $this->CachedTrackEvent($TrackType, 'none', NULL, NULL);
      
      return TRUE;
   }
   
   /**
   * Receive a chunk of hourly data and cache it against the day, week and month
   * 
   * @param mixed $RealType
   * @param mixed $Qualifier
   * @param mixed $Date
   */
   protected function CachedTrackEvent($RealType, $Qualifier = 'none', $Date = NULL, $Hits = 1) {
      static $LocalCache = null;
      static $Resolutions = array(self::RESOLUTION_HOUR, self::RESOLUTION_DAY, self::RESOLUTION_WEEK, self::RESOLUTION_MONTH);
      
      $ForceReset = FALSE;
      
      // Caching some data. Figure out what boxes the new data belongs to
      if (!is_null($Date)) {
         $InstanceDates = array();
         foreach ($Resolutions as $Resolution)
            $InstanceDates[$Resolution] = self::DateFormatByResolution($Date, $Resolution);
      }
            
      if (is_null($LocalCache) || $ForceReset) {
         $LocalCache = array();
         foreach ($Resolutions as $Resolution)
            $LocalCache[$Resolution] = array('Date' => $InstanceDates[$Resolution], 'Hits' => 0);
      }
      
      foreach ($LocalCache as $CacheResolution => &$CacheValue) {
         if (is_null($Date)) {
            if ($CacheValue['Hits'] > 0)
               $this->TrackItem($RealType, $Qualifier, $CacheValue['Date'], $CacheResolution, $CacheValue['Hits']);
         } else {
            // New box for this resolution. Store and reset.
            if ($CacheValue['Date'] != $InstanceDates[$CacheResolution]) {
               // Store
               if ($CacheValue['Hits'] > 0)
                  $this->TrackItem($RealType, $Qualifier, $CacheValue['Date'], $CacheResolution, $CacheValue['Hits']);
               
               // Reset
               $CacheValue = array('Date' => $InstanceDates[$CacheResolution], 'Hits' => 0);
            }
            
            // Update
            $CacheValue['Hits'] += $Hits;
         }
      }
   }
   
   protected static function NextDate($CurrentDate, $Resolution) {
      $DateRaw = (!is_int($CurrentDate)) ? strtotime($CurrentDate) : $CurrentDate;
      if ($DateRaw === FALSE)
         throw new Exception("Invalid range start date '{$CurrentDate}' while calculating next date");
      
      $TimeAdvance = "+1 {$Resolution}";
      if ($Resolution == self::RESOLUTION_WEEK)
         $TimeAdvance = "+8 days";
      $NextDateRaw = strtotime($TimeAdvance, $DateRaw);
      return self::DateFormatByResolution($NextDateRaw, $Resolution);
   }
   
   protected static function DateFormatByResolution($Date, $Resolution) {
   
      $DateRaw = (!is_int($Date)) ? strtotime($Date) : $Date;
      if ($DateRaw === FALSE)
         throw new Exception("Invalid date '{$Date}', unable to convert to epoch");
      
      switch ($Resolution) {
         case self::RESOLUTION_HOUR:
            return date('Y-m-d H:00:00',$DateRaw);
         case self::RESOLUTION_WEEK:
            return date('Y-m-d',strtotime('last sunday',$DateRaw));
         case self::RESOLUTION_DAY:
            return date('Y-m-d',$DateRaw);
         case self::RESOLUTION_MONTH:
            return date('Y-m-01',$DateRaw);
            
         default:
            throw new Exception("Invalid date resolution '{$Resolution}'");
      }
   }
   
   public static function TrackItem($Type, $Qualifier, $Datetime, $Range, $Amount = 1) {
      if (!is_int($Amount))
         throw new Exception("Tried to add non-integer tracking quantity '{$Amount}' to '{$Type}:{$Qualifier}'");
      
      $DateRaw = (!is_int($Datetime)) ? strtotime($Datetime) : $Datetime;
      if ($DateRaw === FALSE)
         throw new Exception("Invalid anchor date '{$Date}' used when attempting to track '{$Type}:{$Qualifier}'");
      
      switch ($Range) {
         case self::RESOLUTION_HOUR:
            $DateStart = date('Y-m-d H:00:00',$DateRaw);
            $DateEnd = date('Y-m-d H:00:00',$DateRaw);
            break;
         case self::RESOLUTION_WEEK:
            $DateStart = date('Y-m-d',strtotime('last sunday',$DateRaw));
            $DateEnd = date('Y-m-d',strtotime('this saturday',$DateRaw));
            break;
         case self::RESOLUTION_DAY:
            $DateStart = date('Y-m-d',$DateRaw);
            $DateEnd = date('Y-m-d',$DateRaw);
            break;
         case self::RESOLUTION_MONTH:
            $DateStart = date('Y-m-01',$DateRaw);
            $DateEnd = date('Y-m-t',$DateRaw);
            break;
         default:
            throw new Exception("Invalid range resolution '{$Range}' used when attempting to track '{$Type}:{$Qualifier}'");
      }
      
      try {
         $Rows = mysql_query("INSERT INTO GDN_Statistics (
            IndexType,
            IndexQualifier,
            DateRangeStart,
            DateRangeEnd,
            DateRangeType,
            IndexValue,
            DateUpdate
         ) VALUES (
            '{$Type}',
            '{$Qualifier}',
            '{$DateStart}',
            '{$DateEnd}',
            '{$Range}',
            '{$Amount}',
            '".date('Y-m-d H:i:s')."'
         )", $this->Database);
         if (!mysql_affected_rows($Rows))
            throw new Exception();
      } catch (Exception $e) {
         mysql_query("UPDATE GDN_Statistics 
            SET IndexValue = IndexValue+{$Amount},
            SET DateUpdated = '".date('Y-m-d H:i:s')."'
            WHERE
               DateRangeType = '{$Range}' AND
               IndexType = '{$Type}' AND
               IndexQualifier = '{$Qualifier}' AND
               DateRangeStart = '{$DateStart}' AND
               DateRangeEnd = '{$DateEnd}'", $this->Database);
      }
   }

}