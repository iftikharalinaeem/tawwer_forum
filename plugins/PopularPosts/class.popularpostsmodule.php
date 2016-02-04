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

        ob_start();
            include(__DIR__.'/views/posts.php');
            $result = ob_get_contents();
        ob_end_clean();

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
        // TODO limit time to 30 days. Currently 90 because of test data :D
        $discussions = $discussionModel->getWhere(array('DateInserted >=' => date('Y-m-d', time() - 60 * 60 * 24 * 90)), 'd.CountViews', 'desc', 10);

        // Restore allowedSortFields just by precaution.
        DiscussionModel::allowedSortFields($originalAllowedFields);

        $this->setData('popularPosts', $discussions);
    }

}