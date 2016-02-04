<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

class PopularPostsModule extends Gdn_Module {


    public $CountCommentsPerPage = 10;

    public function __construct(&$sender = '') {
        parent::__construct($sender);
    }

    public function toString() {
        $this->loadPopularPosts();

        $result = null;

        if (!empty($this->data('popularPosts'))) {
            ob_start();
                include(__DIR__ . '/views/posts.php');
                $result = ob_get_contents();
            ob_end_clean();
        }

        return $result;
    }

    /**
     * Load the 10 most popular (highest view count) discussions.
     */
    protected function loadPopularPosts() {

        $originalAllowedFields = DiscussionModel::allowedSortFields();
        $customAllowedFields = array_merge($originalAllowedFields, ['d.CountViews']);
        DiscussionModel::allowedSortFields($customAllowedFields);

        $discussionModel = new DiscussionModel();

        // TODO limit time to 30 days. Currently more because of test data :D
        $where = array('DateInserted >=' => date('Y-m-d', time() - 60 * 60 * 24 * 200));

        $currentCategory = category();
        if ($currentCategory !== null) {
            $where['CategoryID'] = $currentCategory['CategoryID'];
        }

        $discussions = $discussionModel->getWhere($where, 'd.CountViews', 'desc', 10);

        // Restore allowedSortFields just by precaution.
        DiscussionModel::allowedSortFields($originalAllowedFields);

        if ($discussions->count()) {
            $this->setData('popularPosts', $discussions);
        }
    }
}