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
            $this->RedirectUrl = $url;
            if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                redirectTo($url, 302, false);
            }
        } else {
            throw new Gdn_UserException(t('Error fetching next tracked comment.'), 400);
        }

        $this->render('blank', 'utility', 'dashboard');
    }
}
