<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

class PopularPostsModule extends Gdn_Module {

    // We use writeDiscussion() function from the Discussions view and it needs this variable.
    public $CountCommentsPerPage = 10;

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
     * Load the 10 most popular (highest view count) discussions,
     * filtered by category if applicable, that are below the MaxAge configuration.
     */
    protected function loadPopularPosts() {
        if (!Gdn_Cache::activeEnabled() && c('Cache.Enabled')) {
            trace('Popular Posts caching has failed');
            return;
        }

        $key = 'plugin.popularPosts.data';
        $currentCategory = category();

        // Cache data per category too
        if ($currentCategory !== null) {
            $key .= '.category'.$currentCategory['CategoryID'];
        }

        $data = Gdn::cache()->get($key);
        decho($data);
        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
            $originalAllowedFields = DiscussionModel::allowedSortFields();
            $customAllowedFields = array_merge($originalAllowedFields, ['d.CountViews']);
            DiscussionModel::allowedSortFields($customAllowedFields);

            $discussionModel = new DiscussionModel();

            // Max age in days
            $maxAge = 60 * 60 * 24 * c('Plugin.PopularPosts.MaxAge', 30);
            $where = array('DateInserted >=' => date('Y-m-d', time() - $maxAge));


            if ($currentCategory !== null) {
                $where['CategoryID'] = $currentCategory['CategoryID'];
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
            $this->setData('popularPosts', $discussions);
        }
    }
}
