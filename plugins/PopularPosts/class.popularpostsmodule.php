<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

class PopularPostsModule extends Gdn_Module {

    public function __construct(&$sender = '') {
        parent::__construct($sender);

        // DaazKu: that module is sooooo gonna appear before the normal disucssions :D
        // TODO: Check if we could put that code in a less called function
        $modulesPositions = c('Modules.Vanilla.Content');
        if (!in_array(__CLASS__, $modulesPositions)) {
            $pos   = array_search('DiscussionFilterModule', $modulesPositions);

            if ($pos !== false) {
                array_splice($modulesPositions, $pos, 0, __CLASS__);
                saveToConfig('Modules.Vanilla.Content', $modulesPositions);
            }
        }
    }

    public function assetTarget() {
        return 'Content';
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
        $discussions = $discussionModel->getWhere(false, 'd.CountViews', 'desc', 10);

        // Restore allowedSortFields just by precaution.
        DiscussionModel::allowedSortFields($originalAllowedFields);

        $this->setData('popularPosts', $discussions);
    }

}