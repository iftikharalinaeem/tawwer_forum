<?php if (!defined('APPLICATION')) exit();

class BuzzModel {
   public $SlotRange;
   public $ModUserIDs;
   
   public function Get($Slot = 'w', $Date = FALSE) {
      $SlotRange = self::SlotDateRange($Slot, $Date);
      $this->SlotRange = $SlotRange;
      $RoleModel = new RoleModel();
      $ModRoleIDs = $RoleModel->GetByPermission('Garden.Moderation.Manage')->ResultArray();
      $ModRoleIDs = ConsolidateArrayValuesByKey($ModRoleIDs, 'RoleID');
      
      $ModUserIDs = Gdn::SQL()
         ->Select('UserID')
         ->From('UserRole')
         ->WhereIn('RoleID', $ModRoleIDs)
         ->Get()
         ->ResultArray();
      $ModUserIDs = ConsolidateArrayValuesByKey($ModUserIDs, 'UserID');
      if (count($ModUserIDs) == 0)
         $ModUserIDs[0] = 0;
      $this->ModUserIDs = $ModUserIDs;
      
      $ModIn = '('.implode(',', $ModUserIDs).')';
      
      $Result = array(
          'SlotType' => $Slot,
          'DateFrom' => $SlotRange[0],
          'DateTo' => $SlotRange[1],
      );
      
      // New Users.
      $Result['CountUsers'] = Gdn::SQL()->GetCount('User', self::RangeWhere($SlotRange));
      // Discussions.
      $Result['CountDiscussions'] = Gdn::SQL()->GetCount('Discussion', self::RangeWhere($SlotRange));
      $Result['CountModDiscussions'] = Gdn::SQL()->WhereIn('InsertUserID', $ModUserIDs)->GetCount('Discussion', self::RangeWhere($SlotRange));
      // Comments.
      $Result['CountComments'] = Gdn::SQL()->GetCount('Comment', self::RangeWhere($SlotRange));
      $Result['CountModComments'] = Gdn::SQL()->WhereIn('InsertUserID', $ModUserIDs)->GetCount('Comment', self::RangeWhere($SlotRange));
      
      // Count contributors.
      $Db = Gdn::Database();
      $Px = $Db->DatabasePrefix;
      $Where = "r.DateInserted >= '{$SlotRange[0]}' and r.DateInserted < '{$SlotRange[1]}'";
      $ModWhere = "$Where and r.InsertUserID in $ModIn";
      
      $CountCommentUsers = $Db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$Px}Comment r
         join {$Px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $Where")->Value('CountUsers', 0);
         
      $CountModCommentUsers = $Db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$Px}Comment r
         join {$Px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $ModWhere")->Value('CountUsers', 0);
         
      $CountDiscussionUsers = $Db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$Px}Discussion r
         where $Where")->Value('CountUsers', 0);
      $Result['CountAllContributors'] = $CountCommentUsers + $CountDiscussionUsers;
         
      $CountModDiscussionUsers = $Db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$Px}Discussion r
         where $ModWhere")->Value('CountUsers', 0);
      $Result['CountModContributors'] = $CountModDiscussionUsers + $CountModCommentUsers;
      
      // Users per discussion involves a tricky select.
      // The +1 user is for the user that started the discussion.
      $InnserSQL = "select r.DiscussionID, count(distinct case when r.InsertUserID = d.InsertUserID then null else r.InsertUserID end) + 1 as CountUsers
         from {$Px}Comment r
         join {$Px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where $Where
         group by r.DiscussionID";
      $FullSQL = "select count(DiscussionID) as CountDiscussions, sum(CountUsers) as CountUsers
         from ($InnserSQL) r";
      $Row = Gdn::Database()->Query($FullSQL)->FirstRow();
      $Result['CountDiscussionsContributed'] = GetValue('CountDiscussions', $Row, 0);
      $Result['CountDiscussionContributors'] = GetValue('CountUsers', $Row, 0);
      
      // We have a count of all of the users per discussion of discussions that have at least one comment.
      // Now we have to count the discussions with no comments.
      $CountDiscussionUsers = Gdn::SQL()
         ->Select('r.DiscussionID', 'count', 'C')
         ->From('Discussion r')
         ->Where('r.FirstCommentID', NULL)
         ->Where(self::RangeWhere($SlotRange, 'r.DateInserted'))
         ->Get()->Value('C', 0);
      // And we have to count the discussions that were commented on after this slot.
      $CountDiscussionUsers += Gdn::SQL()
         ->Select('r.DiscussionID', 'count', 'C')
         ->From('Discussion r')
         ->Join('Comment c', 'r.DiscussionID = c.DiscussionID and r.FirstCommentID = c.CommentID')
         ->Where(self::RangeWhere($SlotRange, 'r.DateInserted'))
         ->Where('c.DateInserted >=', $SlotRange[1])
         ->Get()->Value('C', 0);
      
      $Result['CountDiscussionsContributed'] += $CountDiscussionUsers;
      $Result['CountDiscussionContributors'] += $CountDiscussionUsers;
      
      $Row = Gdn::Database()->Query("select count(UserID) as CountUsers, sum(unix_timestamp(DateInserted) - unix_timestamp(DateFirstVisit)) as C
         from {$Px}User
         where DateInserted > DateFirstVisit")->FirstRow();
      $Result['TimeToRegister'] = GetValue('C', $Row, 0);
      $Result['CountToRegister'] = GetValue('CountUsers', $Row, 0);
      
      $this->QnAStats($Result);
      $this->TagStats($Result);
      
      return $Result;
   }
   
   protected function TagStats(&$Result) {
      $Tags = C('Plugins.AdvancedStats.TrackTags');
      if (!$Tags)
         return;
      if (is_string($Tags)) {
         $Tags = explode(',', $Tags);
      }
      
      $CustomConfig = array();
      $i = 1;
      foreach ($Tags as $Tag) {
         $CustomConfig['Custom'.$i] = $Tag;
         $i++;
      }
      
      $SlotRange = $this->SlotRange;
      $TagRows = Gdn::SQL()->WhereIn('Name', $Tags)->Get('Tag')->ResultArray();
      $TagRows = Gdn_DataSet::Index($TagRows, array('Name'));
      $TagIDs = ConsolidateArrayValuesByKey($TagRows, 'TagID');
      
      // Get all of the tag stats.
      $TagCounts = Gdn::SQL()
         ->Select('TagID')
         ->Select('DiscussionID', 'count', 'CountDiscussions')
         ->From('TagDiscussion')
         ->WhereIn('TagID', $TagIDs)
         ->Where(self::RangeWhere($SlotRange))
         ->GroupBy('TagID')
         ->Get()->ResultArray();
      $TagCounts = Gdn_DataSet::Index($TagCounts, array('TagID'));
      
      array_change_key_case($TagRows);
      foreach($CustomConfig as $Field => $Tag) {
         $TagID = GetValueR(strtolower($Tag).'.TagID', $TagRows, 0);
         $Count = GetValueR($TagID.'.CountDiscussions', $TagCounts, 0);
         $Result[$Field] = $Count;
      }
      
      $Result['CustomDef'] = $CustomConfig;
   }
   
   protected function QnAStats(&$Result) {
      if (!array_key_exists('QnA', Gdn::PluginManager()->EnabledPlugins())) {
         return;
      }
      
      $SlotRange = $this->SlotRange;
      $ModUserIDs = $this->ModUserIDs;
      
      // Count all of the questions.
      $Result['CountQuestions'] = Gdn::SQL()
         ->Where('Type', 'Question')
         ->GetCount('Discussion', self::RangeWhere($SlotRange));
      $Result['CountModQuestions'] = Gdn::SQL()
         ->Where('Type', 'Question')
         ->WhereIn('InsertUserID', $ModUserIDs)
         ->GetCount('Discussion', self::RangeWhere($SlotRange));
      
      
      // Count all of the answers.
      $Result['CountAnswers'] = Gdn::SQL()
         ->Select('r.CommentID', 'count', 'CountAnswers')
         ->From('Comment r')
         ->Join('Discussion d', 'd.DiscussionID = r.DiscussionID and d.FirstCommentID = r.CommentID')
         ->Where(self::RangeWhere($SlotRange, 'r.DateInserted'))
         ->Where('d.Type', 'Question')
         ->Get()->Value('CountAnswers');
      
      // Count all of the accepted answers.
      $Row = Gdn::SQL()
         ->Select('d.DiscussionID', 'count', 'CountAcceptedAnswers')
         ->Select('unix_timestamp(d.DateOfAnswer) - unix_timestamp(d.DateInserted)', 'sum', 'TimeToAnswer')
         ->From('Discussion d')
         ->Where(self::RangeWhere($SlotRange, 'd.DateAccepted'))
         ->Get()->FirstRow();
      
      $Result['CountAcceptedAnswers'] = GetValue('CountAcceptedAnswers', $Row, 0);
      $Result['TimeToAnswer'] = GetValue('TimeToAnswer', $Row, 0);
      
      // Count the unanswered questions. This takes some doing...
      
      // Count all the questions without an accepted answer.
      $CountUnanswered1 = Gdn::SQL()
         ->Select('r.DiscussionID', 'count', 'C')
         ->From('Discussion r')
         ->Where('r.DateAccepted', NULL)
         ->Where('r.DateInserted <', $SlotRange[1])
         ->Where('r.Type', 'Question')
         ->Get()->Value('C');

      // Count all of the questions that were not answered by this timeframe.
      $CountUnanswered2 = Gdn::SQL()
         ->Select('r.DiscussionID', 'count', 'C')
         ->From('Discussion r')
         ->Where('r.DateInserted <', $SlotRange[1])
         ->Where('r.DateAccepted >=', $SlotRange[1])
         ->Where('r.Type', 'Question')
         ->Get()->Value('C');

      $Result['CountUnanswered'] = $CountUnanswered1 + $CountUnanswered2;
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