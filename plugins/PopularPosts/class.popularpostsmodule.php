<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

class PopularPostsModule extends Gdn_Module {

    // We use writeDiscussion() function from the Discussions view and it needs this variable.
    public $CountCommentsPerPage = 10;

    // Usually set from the template. Ex: {module name="PopularPostsModule" categoryID="X"}
    public $categoryID = null;

    // Usually set from the template. Ex: {module name="PopularPostsModule" sortMethod="X"}
    public $sortMethod = null;

    /**
     * Returns the component as a string to be rendered to the screen.
     *
     * Unless this method is overridden, it will attempt to find and return a view
     * related to this module automatically.
     *
     * @return string
     */
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
     * Load the top popular posts
     *
     * Load the $CountCommentsPerPage most popular (highest view count) discussions,
     * filtered by category if applicable, that are below the MaxAge configuration.
     */
    protected function loadPopularPosts() {

        if (!Gdn_Cache::activeEnabled() && c('Cache.Enabled')) {
            trace('Popular Posts caching has failed');
            return;
        }

        $key = 'popularPosts.data';

        // Cache data per category too
        if ($this->categoryID !== null) {
            $key .= '.category'.$this->categoryID;
        }

        $data = Gdn::cache()->get($key);
        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
            $originalAllowedFields = DiscussionModel::allowedSortFields();
            $customAllowedFields = array_merge($originalAllowedFields, ['d.CountViews']);
            DiscussionModel::allowedSortFields($customAllowedFields);

            $discussionModel = new DiscussionModel();

            // Max age in days
            $maxAge = 60 * 60 * 24 * c('PopularPosts.MaxAge', 30);
            $where = array('DateInserted >=' => date('Y-m-d', time() - $maxAge));


            if ($this->categoryID !== null) {
                $where['CategoryID'] = $this->categoryID;
            }

            $discussions = $discussionModel->getWhere($where, 'd.CountViews', 'desc', $this->CountCommentsPerPage)->result();

            // Restore allowedSortFields just by precaution.
            DiscussionModel::allowedSortFields($originalAllowedFields);

            Gdn::cache()->store($key, serialize($discussions), array(Gdn_Cache::FEATURE_EXPIRY => 10 * 60));
        } else {
            $discussions = unserialize($data);
            if ($discussions === false) {
                trace('Popular Posts caching retrieval failed');
                return;
            }
        }

        if (!empty($discussions)) {

            switch($this->sortMethod) {
                case 'date-desc':
                    uasort($discussions, function($a, $b) {
                        if ($a['DateInserted'] === $b['DateInserted']) {
                            return 0;
                        }

                        return (strtotime($a['DateInserted']) > strtotime($b['DateInserted'])) ? -1 : 1;
                    });
                case 'date-asc':
                    array_reverse($discussions, true);
                    break;
                // Default = don't do anything!
            }

            $this->setData('popularPosts', $discussions);
        }
    }
}
