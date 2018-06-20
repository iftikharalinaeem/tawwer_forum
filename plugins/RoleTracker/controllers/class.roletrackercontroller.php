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
     * @throws Exception
     */
    public function untrack($discussionID) {
        $this->form = new Gdn_Form();

        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);

        if (!$discussion) {
            throw notFoundException('Discussion');
        }

        $trackedRoles = RoleTrackerModel::instance()->getTrackedRoles();
        if (!$trackedRoles) {
            throw notFoundException();
        }

        if (!Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion))) {
            return permissionException('Vanilla.Discussions.Edit');
        }

        $discussionTags = val('Tags', $discussion);
        if ($discussionTags) {
            $discussionTags = Gdn_DataSet::index($discussionTags, 'TagID');
        } else {
            $tagModel = new TagModel();
            $discussionTags = $tagModel->getDiscussionTags(val('DiscussionID', $discussion), TagModel::IX_TAGID);
        }
        if (!$discussionTags) {
            throw notFoundException();
        }

        $trackedRolesByTag = Gdn_DataSet::index($trackedRoles, 'TrackerTagID');
        $discussionsTrackedTagIDs = array_intersect(array_keys($trackedRolesByTag), array_keys($discussionTags));
        if (!$discussionsTrackedTagIDs) {
            throw notFoundException();
        }

        if ($this->form->authenticatedPostBack()) {
            $tags = $this->form->getFormValue('trackedTag');
            $tagDiscussionModel = new Gdn_Model('TagDiscussion');
            foreach($tags as $tagID) {
                if (in_array($tagID, $discussionsTrackedTagIDs)) {
                    $tagDiscussionModel->delete([
                        'DiscussionID' => $discussionID,
                        'TagID' => $tagID,
                    ]);
                }
            }

            Gdn::controller()->jsonTarget('', '', 'Refresh');
            $this->render('blank', 'utility', 'dashboard');
            return;
        }

        $this->setData('Discussion', $discussion);
        $this->setData('TrackedTagIDs', $discussionsTrackedTagIDs);
        $this->setData('DiscussionTags', $discussionTags);

        $this->render();
    }
}
