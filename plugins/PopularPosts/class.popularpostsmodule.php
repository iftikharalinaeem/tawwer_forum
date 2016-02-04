<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

class PopularPostsModule extends Gdn_Module {

    // We use writeDiscussion() function from the Discussions view and it needs this variable.
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

        // TODO remove that + 90 :D.
        $maxAge = 60 * 60 * 24 * (c('Plugin.PopularPosts.MaxAge', 30) + 90);
        $where = array('DateInserted >=' => date('Y-m-d', time() - $maxAge));

        $currentCategory = category();
        if ($currentCategory !== null) {
            $where['CategoryID'] = $currentCategory['CategoryID'];
        }

        $discussions = $discussionModel->getWhere($where, 'd.CountViews', 'desc', $this->CountCommentsPerPage);

        // Restore allowedSortFields just by precaution.
        DiscussionModel::allowedSortFields($originalAllowedFields);

        if ($discussions->count()) {
            $this->setData('popularPosts', $discussions);
        }
    }
}