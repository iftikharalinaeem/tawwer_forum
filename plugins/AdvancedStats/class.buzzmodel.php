<?php

class BuzzModel {
    public $SlotRange;
    public $ModUserIDs;

    public function get($slot = 'w', $date = FALSE) {
        $slotRange = self::slotDateRange($slot, $date);
        $this->SlotRange = $slotRange;
        $roleModel = new RoleModel();
        $modRoleIDs = $roleModel->getByPermission('Garden.Moderation.Manage')->resultArray();
        $modRoleIDs = array_column($modRoleIDs, 'RoleID');
        $slotString = Gdn_Statistics::timeSlot($slot, Gdn_Format::toTimestamp($date));

        $modUserIDs = Gdn::sql()
            ->select('UserID')
            ->from('UserRole')
            ->whereIn('RoleID', $modRoleIDs)
            ->get()
            ->resultArray();
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
        $result['CountUsers'] = Gdn::sql()->getCount('User', self::rangeWhere($slotRange));
        // Discussions.
        $result['CountDiscussions'] = Gdn::sql()->getCount('Discussion', self::rangeWhere($slotRange));
        $result['CountModDiscussions'] = Gdn::sql()->whereIn('InsertUserID', $modUserIDs)->getCount('Discussion', self::rangeWhere($slotRange));
        // Comments.
        $result['CountComments'] = Gdn::sql()->getCount('Comment', self::rangeWhere($slotRange));
        $result['CountModComments'] = Gdn::sql()->whereIn('InsertUserID', $modUserIDs)->getCount('Comment', self::rangeWhere($slotRange));

        // Count contributors.
        $db = Gdn::database();
        $px = $db->DatabasePrefix;
        $where = "r.DateInserted >= '{$slotRange[0]}' and r.DateInserted < '{$slotRange[1]}'";
        $modWhere = "$where and r.InsertUserID in $modIn";

        $countCommentUsers = $db->query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Comment r
         join {$px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $where")->value('CountUsers', 0);

        $countModCommentUsers = $db->query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Comment r
         join {$px}Discussion d
           on r.DiscussionID = d.DiscussionID
         where r.InsertUserID <> d.InsertUserID
            and $modWhere")->value('CountUsers', 0);

        $countDiscussionUsers = $db->query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Discussion r
         where $where")->value('CountUsers', 0);
        $result['CountAllContributors'] = $countCommentUsers + $countDiscussionUsers;

        $countModDiscussionUsers = $db->query("select count(distinct r.InsertUserID) as CountUsers
         from {$px}Discussion r
         where $modWhere")->value('CountUsers', 0);
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
        $row = Gdn::database()->query($fullSQL)->firstRow();
        $result['CountDiscussionsContributed'] = getValue('CountDiscussions', $row, 0);
        $result['CountDiscussionContributors'] = getValue('CountUsers', $row, 0);

        // We have a count of all of the users per discussion of discussions that have at least one comment.
        // Now we have to count the discussions with no comments.
        $countDiscussionUsers = Gdn::sql()
            ->select('r.DiscussionID', 'count', 'C')
            ->from('Discussion r')
            ->where('r.FirstCommentID', NULL)
            ->where(self::rangeWhere($slotRange, 'r.DateInserted'))
            ->get()->value('C', 0);
        // And we have to count the discussions that were commented on after this slot.
        $countDiscussionUsers += Gdn::sql()
            ->select('r.DiscussionID', 'count', 'C')
            ->from('Discussion r')
            ->join('Comment c', 'r.DiscussionID = c.DiscussionID and r.FirstCommentID = c.CommentID')
            ->where(self::rangeWhere($slotRange, 'r.DateInserted'))
            ->where('c.DateInserted >=', $slotRange[1])
            ->get()->value('C', 0);

        $result['CountDiscussionsContributed'] += $countDiscussionUsers;
        $result['CountDiscussionContributors'] += $countDiscussionUsers;

        $row = Gdn::database()->query("select count(UserID) as CountUsers, sum(unix_timestamp(DateInserted) - unix_timestamp(DateFirstVisit)) as C
         from {$px}User
         where DateInserted > DateFirstVisit")->firstRow();
        $result['TimeToRegister'] = getValue('C', $row, 0);
        $result['CountToRegister'] = getValue('CountUsers', $row, 0);

        $this->qnAStats($result);
        $this->tagStats($result);

        return $result;
    }

    protected function tagStats(&$result) {
        $tags = c('Plugins.AdvancedStats.TrackTags');
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
        $tagRows = Gdn::sql()->whereIn('Name', $tags)->get('Tag')->resultArray();
        $tagRows = Gdn_DataSet::index($tagRows, ['Name']);
        $tagIDs = array_column($tagRows, 'TagID');

        // Get all of the tag stats.
        $tagCounts = Gdn::sql()
            ->select('TagID')
            ->select('DiscussionID', 'count', 'CountDiscussions')
            ->from('TagDiscussion')
            ->whereIn('TagID', $tagIDs)
            ->where(self::rangeWhere($slotRange))
            ->groupBy('TagID')
            ->get()->resultArray();
        $tagCounts = Gdn_DataSet::index($tagCounts, ['TagID']);

        array_change_key_case($tagRows);
        foreach ($customConfig as $field => $tag) {
            $tagID = getValueR(strtolower($tag).'.TagID', $tagRows, 0);
            $count = getValueR($tagID.'.CountDiscussions', $tagCounts, 0);
            $result[$field] = $count;
        }

        $result['CustomDef'] = $customConfig;
    }

    protected function qnAStats(&$result) {
        if (!Gdn::addonManager()->isEnabled('QnA', \Vanilla\Addon::TYPE_ADDON)) {
            return;
        }

        $slotRange = $this->SlotRange;
        $modUserIDs = $this->ModUserIDs;

        // Count all of the questions.
        $result['CountQuestions'] = Gdn::sql()
            ->where('Type', 'Question')
            ->getCount('Discussion', self::rangeWhere($slotRange));
        $result['CountModQuestions'] = Gdn::sql()
            ->where('Type', 'Question')
            ->whereIn('InsertUserID', $modUserIDs)
            ->getCount('Discussion', self::rangeWhere($slotRange));


        // Count all of the answers.
        $result['CountAnswers'] = Gdn::sql()
            ->select('r.CommentID', 'count', 'CountAnswers')
            ->from('Comment r')
            ->join('Discussion d', 'd.DiscussionID = r.DiscussionID and d.FirstCommentID = r.CommentID')
            ->where(self::rangeWhere($slotRange, 'r.DateInserted'))
            ->where('d.Type', 'Question')
            ->get()->value('CountAnswers');

        // Count all of the accepted answers.
        $row = Gdn::sql()
            ->select('d.DiscussionID', 'count', 'CountAcceptedAnswers')
            ->select('unix_timestamp(d.DateOfAnswer) - unix_timestamp(d.DateInserted)', 'sum', 'TimeToAnswer')
            ->from('Discussion d')
            ->where(self::rangeWhere($slotRange, 'd.DateAccepted'))
            ->get()->firstRow();

        $result['CountAcceptedAnswers'] = getValue('CountAcceptedAnswers', $row, 0);
        $result['TimeToAnswer'] = getValue('TimeToAnswer', $row, 0);

        // Count the unanswered questions. This takes some doing...

        // Count all the questions without an accepted answer.
        $countUnanswered1 = Gdn::sql()
            ->select('r.DiscussionID', 'count', 'C')
            ->from('Discussion r')
            ->where('r.DateAccepted', NULL)
            ->where('r.DateInserted <', $slotRange[1])
            ->where('r.Type', 'Question')
            ->get()->value('C');

        // Count all of the questions that were not answered by this timeframe.
        $countUnanswered2 = Gdn::sql()
            ->select('r.DiscussionID', 'count', 'C')
            ->from('Discussion r')
            ->where('r.DateInserted <', $slotRange[1])
            ->where('r.DateAccepted >=', $slotRange[1])
            ->where('r.Type', 'Question')
            ->get()->value('C');

        $result['CountUnanswered'] = $countUnanswered1 + $countUnanswered2;
    }

    protected static function rangeWhere($range, $fieldName = 'DateInserted') {
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
    public static function slotDateRange($slot = 'w', $date = FALSE) {
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
                $result = [Gdn_Format::toDateTime($timestamp), Gdn_Format::toDateTime(strtotime('+1 day', $timestamp))];
                break;
            case 'w':
                $sub = gmdate('N', $timestamp) - 1;
                $add = 7 - $sub;
                $result = [Gdn_Format::toDateTime(strtotime("-$sub days", $timestamp)), Gdn_Format::toDateTime(strtotime("+$add days", $timestamp))];
                break;
            case 'm':
                $sub = gmdate('j', $timestamp) - 1;
                $timestamp = strtotime("-$sub days", $timestamp);
                $result = [Gdn_Format::toDateTime($timestamp), Gdn_Format::toDateTime(strtotime("+1 month", $timestamp))];
                break;
            case 'y':
                $timestamp = strtotime(date('Y-01-01', $timestamp));
                $result = [Gdn_Format::toDate($timestamp), Gdn_Format::toDateTime(strtotime("+1 year", $timestamp))];
                break;
        }

        return $result;
    }
}
