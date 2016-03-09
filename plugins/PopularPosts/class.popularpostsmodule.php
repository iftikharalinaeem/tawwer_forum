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

        $showSpecificCategory = $this->categoryID !== null;
        $userCategoryPerm = DiscussionModel::categoryPermissions();
        $hasAllPermissions = $userCategoryPerm === true;

        // Abort if we are to show posts from a specific category and the user does not have acces to it
        if ($showSpecificCategory && !$hasAllPermissions && !in_array($this->categoryID, $userCategoryPerm)) {
            return;
        }

        // Max age in days
        $maxAge = 60 * 60 * 24 * c('PopularPosts.MaxAge', 30);
        $minDateInserted = date('Y-m-d', time() - $maxAge);

        $countCommentPerPage = ctype_digit((string)$this->CountCommentsPerPage) ? $this->CountCommentsPerPage : 10;

        $cacheKey = "popularPosts.data[top:$countCommentPerPage,mindate:$minDateInserted]";
        $data = Gdn::cache()->get($cacheKey);

        // Cache the top 10 posts of each categories
        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
            $query = "
                select
                    GDN_Discussion.*
                from GDN_Discussion
                    inner join (
                        /* Get the list of the top 10 most viewed discussions by categories */
                        select
                            GDN_Discussion.CategoryID,
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(GDN_Discussion.DiscussionID order by GDN_Discussion.CountViews desc),
                                ',',
                                $countCommentPerPage
                            ) as GroupedDiscussionIDs
                        from GDN_Discussion
                        where GDN_Discussion.DateInserted >= '$minDateInserted'
                        group by GDN_Discussion.CategoryID
                    ) as tmp
                    /* Join discussion on matching category and then on discussion id
                       found in the list of top 10 discussions of that category */
                    on GDN_Discussion.CategoryID = tmp.CategoryID
                        and GDN_Discussion.DateInserted >= '$minDateInserted'
                        and FIND_IN_SET(GDN_Discussion.DiscussionID, tmp.GroupedDiscussionIDs) != 0
            ";
            $discussions = Gdn::sql()->query($query)->result();

            // Index discussions by categories for easier filtering later on.
            $discussionsByCategories = [];
            foreach($discussions as $discussion) {
                if (!isset($discussionsByCategories[$discussion->CategoryID])) {
                    $discussionsByCategories[$discussion->CategoryID] = [];
                }
                $discussionsByCategories[$discussion->CategoryID][] = $discussion;
            }

            Gdn::cache()->store($cacheKey, serialize($discussionsByCategories), [Gdn_Cache::FEATURE_EXPIRY => 10 * 60]);
        } else {
            $discussionsByCategories = unserialize($data);
            if ($discussionsByCategories === false) {
                trace('Popular Posts caching retrieval failed');
                return;
            }
        }

        if (!empty($discussionsByCategories)) {

            // Aggregate and filter down the posts to match user permissions and/or specified category
            $filteredDiscussions = [];
            if ($showSpecificCategory) {
                $filteredDiscussions = val($discussion->CategoryID, $discussionsByCategories, []);
            } else {
                $userAllowedData = $discussionsByCategories;
                if (!$hasAllPermissions) {
                    $userAllowedData = array_intersect_key($discussionsByCategories, array_flip($userCategoryPerm));
                }

                foreach($userAllowedData as $categoryID => $discussions) {
                    $filteredDiscussions += $discussions;
                }
            }

            $needSlicing = !$showSpecificCategory;
            $needSorting = $needSlicing || (!$needSlicing && !$this->sortMethod);

            // Order the posts by CountViews!
            if ($needSorting) {
                uasort($filteredDiscussions, function ($a, $b) {
                    if ($a->CountViews === $b->CountViews) {
                        return 0;
                    }

                    return $a->CountViews > $b->CountViews ? -1 : 1;
                });
            }

            if ($needSlicing) {
                $filteredDiscussions = array_slice($filteredDiscussions, 0, $countCommentPerPage, true);
            }

            $discussionModel = new DiscussionModel();
            array_walk($filteredDiscussions, array($discussionModel, 'calculate'));

            // Join user data
            Gdn::userModel()->joinUsers($filteredDiscussions, array('FirstUserID', 'LastUserID'));
            // Join categories
            CategoryModel::JoinCategories($filteredDiscussions);

            switch($this->sortMethod) {
                case 'date-asc':
                    uasort($filteredDiscussions, function($a, $b) {
                        if ($a->DateInserted === $b->DateInserted) {
                            return 0;
                        }

                        return (strtotime($a->DateInserted) < strtotime($b->DateInserted)) ? -1 : 1;
                    });
                    break;
                case 'date-desc':
                    uasort($filteredDiscussions, function($a, $b) {
                        if ($a->DateInserted === $b->DateInserted) {
                            return 0;
                        }

                        return (strtotime($a->DateInserted) > strtotime($b->DateInserted)) ? -1 : 1;
                    });
                    break;
                // Default = don't do anything!
            }

            $this->setData('popularPosts', $filteredDiscussions);
        }
    }
}
