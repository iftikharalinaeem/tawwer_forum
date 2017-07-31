<?php

class BuzzModel {
    public $SlotRange;
    public $ModUserIDs;

    public function Get($slot = 'w', $date = FALSE) {
        $slotRange = self::SlotDateRange($slot, $date);
        $this->SlotRange = $slotRange;
        $roleModel = new RoleModel();
        $modRoleIDs = $roleModel->GetByPermission('Garden.Moderation.Manage')->ResultArray();
        $modRoleIDs = array_column($modRoleIDs, 'RoleID');
        $slotString = Gdn_Statistics::TimeSlot($slot, Gdn_Format::ToTimestamp($date));

        $modUserIDs = Gdn::SQL()
            ->Select('UserID')
            ->From('UserRole')
            ->WhereIn('RoleID', $modRoleIDs)
            ->Get()
            ->ResultArray();
        $modUserIDs = array_column($modUserIDs, 'UserID');
        if (count($modUserIDs) == 0) {
            $modUserIDs[0] = 0;
        }
        $this->ModUserIDs = $modUserIDs;

        $modIn = '('.implode(',', $modUserIDs).')';

        $result = [
            'SlotType' => $slot,
            'DateFrom' => $slotRange[0],
            'DateTo' => $slotRange[1],
            'Slot' => $slotString
        ];

        // New Users.
        $result['CountUsers'] = Gdn::SQL()->GetCount('User', self::RangeWhere($slotRange));
        // Discussions.
        $result['CountDiscussions'] = Gdn::SQL()->GetCount('Discussion', self::RangeWhere($slotRange));
        $result['CountModDiscussions'] = Gdn::SQL()->WhereIn('InsertUserID', $modUserIDs)->GetCount('Discussion', self::RangeWhere($slotRange));
        // Comments.
        $result['CountComments'] = Gdn::SQL()->GetCount('Comment', self::RangeWhere($slotRange));
        $result['CountModComments'] = Gdn::SQL()->WhereIn('InsertUserID', $modUserIDs)->GetCount('Comment', self::RangeWhere($slotRange));

        // Count contributors.
        $db = Gdn::Database();
        $px = $db->DatabasePrefix;
        $where = "r.DateInserted >= '{$slotRange[0]}' and r.DateInserted < '{$slotRange[1]}'";
        $modWhere = "$where and r.InsertUserID in $modIn";

        $countCommentUsers = $db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Comment r
         join {$px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $where")->Value('CountUsers', 0);

        $countModCommentUsers = $db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Comment r
         join {$px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $modWhere")->Value('CountUsers', 0);

        $countDiscussionUsers = $db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Discussion r
         where $where")->Value('CountUsers', 0);
        $result['CountAllContributors'] = $countCommentUsers + $countDiscussionUsers;

        $countModDiscussionUsers = $db->Query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Discussion r
         where $modWhere")->Value('CountUsers', 0);
        $result['CountModContributors'] = $countModDiscussionUsers + $countModCommentUsers;

        // Users per discussion involves a tricky select.
        // The +1 user is for the user that started the discussion.
        $innserSQL = "select r.DiscussionID, count(distinct case when r.InsertUserID = d.InsertUserID then null else r.InsertUserID end) + 1 as CountUsers
         from {$px}Comment r
         join {$px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where $where
         group by r.DiscussionID";
        $fullSQL = "select count(DiscussionID) as CountDiscussions, sum(CountUsers) as CountUsers
         from ($innserSQL) r";
        $row = Gdn::Database()->Query($fullSQL)->FirstRow();
        $result['CountDiscussionsContributed'] = GetValue('CountDiscussions', $row, 0);
        $result['CountDiscussionContributors'] = GetValue('CountUsers', $row, 0);

        // We have a count of all of the users per discussion of discussions that have at least one comment.
        // Now we have to count the discussions with no comments.
        $countDiscussionUsers = Gdn::SQL()
            ->Select('r.DiscussionID', 'count', 'C')
            ->From('Discussion r')
            ->Where('r.FirstCommentID', NULL)
            ->Where(self::RangeWhere($slotRange, 'r.DateInserted'))
            ->Get()->Value('C', 0);
        // And we have to count the discussions that were commented on after this slot.
        $countDiscussionUsers += Gdn::SQL()
            ->Select('r.DiscussionID', 'count', 'C')
            ->From('Discussion r')
            ->Join('Comment c', 'r.DiscussionID = c.DiscussionID and r.FirstCommentID = c.CommentID')
            ->Where(self::RangeWhere($slotRange, 'r.DateInserted'))
            ->Where('c.DateInserted >=', $slotRange[1])
            ->Get()->Value('C', 0);

        $result['CountDiscussionsContributed'] += $countDiscussionUsers;
        $result['CountDiscussionContributors'] += $countDiscussionUsers;

        $row = Gdn::Database()->Query("select count(UserID) as CountUsers, sum(unix_timestamp(DateInserted) - unix_timestamp(DateFirstVisit)) as C
         from {$px}User
         where DateInserted > DateFirstVisit")->FirstRow();
        $result['TimeToRegister'] = GetValue('C', $row, 0);
        $result['CountToRegister'] = GetValue('CountUsers', $row, 0);

        $this->QnAStats($result);
        $this->TagStats($result);

        return $result;
    }

    protected function TagStats(&$result) {
        $tags = C('Plugins.AdvancedStats.TrackTags');
        if (!$tags) {
            return;
        }
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        $customConfig = [];
        $i = 1;
        foreach ($tags as $tag) {
            $customConfig['Custom'.$i] = $tag;
            $i++;
        }

        $slotRange = $this->SlotRange;
        $tagRows = Gdn::SQL()->WhereIn('Name', $tags)->Get('Tag')->ResultArray();
        $tagRows = Gdn_DataSet::Index($tagRows, ['Name']);
        $tagIDs = array_column($tagRows, 'TagID');

        // Get all of the tag stats.
        $tagCounts = Gdn::SQL()
            ->Select('TagID')
            ->Select('DiscussionID', 'count', 'CountDiscussions')
            ->From('TagDiscussion')
            ->WhereIn('TagID', $tagIDs)
            ->Where(self::RangeWhere($slotRange))
            ->GroupBy('TagID')
            ->Get()->ResultArray();
        $tagCounts = Gdn_DataSet::Index($tagCounts, ['TagID']);

        array_change_key_case($tagRows);
        foreach ($customConfig as $field => $tag) {
            $tagID = GetValueR(strtolower($tag).'.TagID', $tagRows, 0);
            $count = GetValueR($tagID.'.CountDiscussions', $tagCounts, 0);
            $result[$field] = $count;
        }

        $result['CustomDef'] = $customConfig;
    }

    protected function QnAStats(&$result) {
        if (!Gdn::addonManager()->isEnabled('QnA', \Vanilla\Addon::TYPE_ADDON)) {
            return;
        }

        $slotRange = $this->SlotRange;
        $modUserIDs = $this->ModUserIDs;

        // Count all of the questions.
        $result['CountQuestions'] = Gdn::SQL()
            ->Where('Type', 'Question')
            ->GetCount('Discussion', self::RangeWhere($slotRange));
        $result['CountModQuestions'] = Gdn::SQL()
            ->Where('Type', 'Question')
            ->WhereIn('InsertUserID', $modUserIDs)
            ->GetCount('Discussion', self::RangeWhere($slotRange));


        // Count all of the answers.
        $result['CountAnswers'] = Gdn::SQL()
            ->Select('r.CommentID', 'count', 'CountAnswers')
            ->From('Comment r')
            ->Join('Discussion d', 'd.DiscussionID = r.DiscussionID and d.FirstCommentID = r.CommentID')
            ->Where(self::RangeWhere($slotRange, 'r.DateInserted'))
            ->Where('d.Type', 'Question')
            ->Get()->Value('CountAnswers');

        // Count all of the accepted answers.
        $row = Gdn::SQL()
            ->Select('d.DiscussionID', 'count', 'CountAcceptedAnswers')
            ->Select('unix_timestamp(d.DateOfAnswer) - unix_timestamp(d.DateInserted)', 'sum', 'TimeToAnswer')
            ->From('Discussion d')
            ->Where(self::RangeWhere($slotRange, 'd.DateAccepted'))
            ->Get()->FirstRow();

        $result['CountAcceptedAnswers'] = GetValue('CountAcceptedAnswers', $row, 0);
        $result['TimeToAnswer'] = GetValue('TimeToAnswer', $row, 0);

        // Count the unanswered questions. This takes some doing...

        // Count all the questions without an accepted answer.
        $countUnanswered1 = Gdn::SQL()
            ->Select('r.DiscussionID', 'count', 'C')
            ->From('Discussion r')
            ->Where('r.DateAccepted', NULL)
            ->Where('r.DateInserted <', $slotRange[1])
            ->Where('r.Type', 'Question')
            ->Get()->Value('C');

        // Count all of the questions that were not answered by this timeframe.
        $countUnanswered2 = Gdn::SQL()
            ->Select('r.DiscussionID', 'count', 'C')
            ->From('Discussion r')
            ->Where('r.DateInserted <', $slotRange[1])
            ->Where('r.DateAccepted >=', $slotRange[1])
            ->Where('r.Type', 'Question')
            ->Get()->Value('C');

        $result['CountUnanswered'] = $countUnanswered1 + $countUnanswered2;
    }

    protected static function RangeWhere($range, $fieldName = 'DateInserted') {
        return ["$fieldName >=" => $range[0], "$fieldName <" => $range[1]];
    }

    /**
     * Gets the date range for a slot.
     *
     * @param string $slot One of:
     *  - d: Day
     *  - w: Week
     *  - m: Month
     * @param string|int $date The date or timestamp in the slot.
     * @return array The dates in the form array(From, To).
     */
    public static function SlotDateRange($slot = 'w', $date = FALSE) {
        if (!$date) {
            $timestamp = strtotime(gmdate('Y-m-d'));
        } elseif (is_numeric($date)) {
            $timestamp = strtotime(gmdate('Y-m-d', $date));
        } else {
            $timestamp = strtotime(gmdate('Y-m-d', strtotime($date)));
        }

        $result = NULL;
        switch ($slot) {
            case 'd':
                $result = [Gdn_Format::ToDateTime($timestamp), Gdn_Format::ToDateTime(strtotime('+1 day', $timestamp))];
                break;
            case 'w':
                $sub = gmdate('N', $timestamp) - 1;
                $add = 7 - $sub;
                $result = [Gdn_Format::ToDateTime(strtotime("-$sub days", $timestamp)), Gdn_Format::ToDateTime(strtotime("+$add days", $timestamp))];
                break;
            case 'm':
                $sub = gmdate('j', $timestamp) - 1;
                $timestamp = strtotime("-$sub days", $timestamp);
                $result = [Gdn_Format::ToDateTime($timestamp), Gdn_Format::ToDateTime(strtotime("+1 month", $timestamp))];
                break;
            case 'y':
                $timestamp = strtotime(date('Y-01-01', $timestamp));
                $result = [Gdn_Format::ToDate($timestamp), Gdn_Format::ToDateTime(strtotime("+1 year", $timestamp))];
                break;
        }

        return $result;
    }
}
