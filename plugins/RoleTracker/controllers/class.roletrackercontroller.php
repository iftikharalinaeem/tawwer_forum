<?php
/**
 * RoleTracker controller
 */
class RoleTrackerController extends Gdn_Controller {
    /**
     * Create a jump endpoint that will redirect a user to the first tracker post of a discussion.
     *
     * @param $discussionID
     * @param string $direction Direction of the search (next || previous)
     * @param string $from Date from which to start searching for the post.
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function jump($discussionID, $from = '') {
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);

        if (!$discussion) {
            return;
        }

        $url = false;
        $roleTrackerModel = RoleTrackerModel::instance();

        $discussionUserIsTracked = (bool)$roleTrackerModel->getUserTrackedRoles(val('InsertUserID', $discussion));

        if (!$from && $discussionUserIsTracked) {
            $url = discussionUrl($discussion).'#Item_0';
        } else {
            $trackedRoles = $roleTrackerModel->getTrackedRoles();

            $this->EventArguments['Discussion'] = $discussion;
            $this->EventArguments['TrackedRoles'] = &$trackedRoles;
            $this->fireEvent('BeforeTrackUsers');

            $trackedUsersData = Gdn::sql()
                ->select('UserID')
                ->from('UserRole')
                ->where(['RoleID' => array_keys($trackedRoles)])
                ->groupBy('UserID')
                ->limit(c('RoleTracker.MaxTrackedUser', 50))
                ->get()
                    ->resultArray();

            $trackedUsers = array_column($trackedUsersData, 'UserID');

            $commentModel = new CommentModel();

            $where = [
                'DiscussionID' => $discussion['DiscussionID'],
                'InsertUserID' => $trackedUsers,
            ];

            if ($from && strtotime($from)) {
                $where['DateInserted >'] = $from;
            }

            $comment = $commentModel->getWhere($where, 'DateInserted', 'asc', 1)->firstRow(DATASET_TYPE_ARRAY);
            if ($comment) {
                $url = commentUrl($comment);
            }

            if (!$url) {
                if ($discussionUserIsTracked) {
                    $url = discussionUrl($discussion).'#Item_0';
                } else {
                    $where['DateInserted >'] = $discussion['DateInserted'];

                    $comment = $commentModel->getWhere($where, 'DateInserted', 'asc', 1)->firstRow(DATASET_TYPE_ARRAY);
                    if ($comment) {
                        $url = commentUrl($comment);
                    }
                }
            }
        }

        if ($url) {
            $this->setRedirectTo($url);
            if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                redirectTo($url);
            }
        } else {
            throw new Gdn_UserException(t('Error fetching next tracked comment.'), 400);
        }

        $this->render('blank', 'utility', 'dashboard');
    }

    /**
     * Remove a tracking tag from a discussion.
     *
     * @param $discussionID
     * @param $tagID
     * @throws Exception
     * @throws \Vanilla\Exception\PermissionException
     */
    public function untrack($discussionID, $tagID) {
        // Make sure we are posting back.
        if (!Gdn::request()->isAuthenticatedPostBack()) {
            throw permissionException('Javascript');
        }

        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion || !Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion))) {
            throw new \Vanilla\Exception\PermissionException('Vanilla.Discussions.Edit');
        }

        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
        if (!$trackedRoles) {
            throw new Exception('No roles are tracked.');
        }
        $trackedRolesByTag = Gdn_DataSet::index($trackedRoles, 'TrackerTagID');

        $tagModel = new TagModel();
        $discussionTags = $tagModel->getDiscussionTags($discussionID, TagModel::IX_TAGID);
        if (!$discussionTags || !isset($discussionTags[$tagID])) {
            throw new Exception('The discussion is not tracked.');
        }

        $tagDiscussionModel = new Gdn_Model('TagDiscussion');
        $tagDiscussionModel->delete([
            'DiscussionID' => $discussionID,
            'TagID' => $tagID,
        ]);

        $this->informMessage(sprintft('%s was successfully un tracked.', htmlentities($discussionTags[$tagID]['FullName'])));

        $this->render('blank', 'utility', 'dashboard');
    }
}
