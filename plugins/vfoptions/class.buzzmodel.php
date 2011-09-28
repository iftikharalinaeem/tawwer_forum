<?php if (!defined('APPLICATION')) exit();

class BuzzModel {
   public function Get($Slot = 'w', $Date = FALSE) {
      $SlotRange = self::SlotDateRange($Slot, $Date);
      
      $Result = array(
          'DateFrom' => $SlotRange[0],
          'DateTo' => $SlotRange[1],
      );
      
      // New Users.
      $Result['CountNewUsers'] = Gdn::SQL()->GetCount('User', self::RangeWhere($SlotRange));
      // Discussions.
      $Result['CountDiscussions'] = Gdn::SQL()->GetCount('Discussion', self::RangeWhere($SlotRange));
      // Comments.
      $Result['CountComments'] = Gdn::SQL()->GetCount('Comment', self::RangeWhere($SlotRange));
      
      // Count contributors.
      $Db = Gdn::Database();
      $Px = $Db->DatabasePrefix;
      $Where = "r.DateInserted >= '{$SlotRange[0]}' and r.DateInserted < '{$SlotRange[1]}'";
      
      $CountCommentUsers = $Db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$Px}Comment r
         join {$Px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $Where")->Value('CountUsers', 0);
         
      $CountDiscussionUsers = $Db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$Px}Discussion r
         where $Where")->Value('CountUsers', 0);
         
      $Result['CountContributors'] = $CountCommentUsers + $CountDiscussionUsers;
      
      return $Result;
   }
   
   protected static function RangeWhere($Range, $FieldName = 'DateInserted') {
      return array("$FieldName >=" => $Range[0], "$FieldName <" => $Range[1]);
   }
   
   /**
    * Gets the date range for a slot.
    * @param string $Slot One of:
    *  - d: Day
    *  - w: Week
    *  - m: Month
    * @param string|int $Date The date or timestamp in the slot.
    * @return array The dates in the form array(From, To).
    */
   public static function SlotDateRange($Slot = 'w', $Date = FALSE) {
      if (!$Date)
         $Timestamp = strtotime(gmdate('Y-m-d'));
      elseif (is_numeric($Date))
         $Timestamp = strtotime(gmdate('Y-m-d', $Date));
      else
         $Timestamp = strtotime(gmdate('Y-m-d', strtotime($Date)));

      $Result = NULL;
      switch ($Slot) {
         case 'd':
            $Result = array(Gdn_Format::ToDateTime($Timestamp), Gdn_Format::ToDateTime(strtotime('+1 day', $Timestamp)));
            break;
         case 'w':
            $Sub = gmdate('N', $Timestamp) - 1;
            $Add = 7 - $Sub;
            $Result = array(Gdn_Format::ToDateTime(strtotime("-$Sub days", $Timestamp)), Gdn_Format::ToDateTime(strtotime("+$Add days", $Timestamp)));
            break;
         case 'm':
            $Sub = gmdate('j', $Timestamp) - 1;
            $Timestamp = strtotime("-$Sub days", $Timestamp);
            $Result = array(Gdn_Format::ToDateTime($Timestamp), Gdn_Format::ToDateTime(strtotime("+1 month", $Timestamp)));
            break;
         case 'y':
            $Timestamp = strtotime(date('Y-01-01', $Timestamp));
            $Result = array(Gdn_Format::ToDate($Timestamp), Gdn_Format::ToDateTime(strtotime("+1 year", $Timestamp)));
            break;
      }

      return $Result;
   }
}