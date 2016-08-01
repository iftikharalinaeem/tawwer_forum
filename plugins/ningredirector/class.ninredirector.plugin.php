<?php
/**
 * NingRedirector Plugin
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['ningredirector'] = [
    'Name' => 'Ning Redirector',
    'Description' => 'Redirect Ning\'s URLs',
    'Version' => '1.0',
    'RequiredApplications' => ['Vanilla' => '2.2'],
    'License' => 'GNU GPL2',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com',
    'AuthorUrl' => 'https://github.com/DaazKu',
];

/**
 * Class NingRedirectorPlugin
 */
class NingRedirectorPlugin extends Gdn_Plugin {
    /**
     * Catch the NotFound error and try to redirect!
     */
    public function gdn_dispatcher_notFound_handler() {
        $path = Gdn::request()->path();
        $get = Gdn::request()->get();

        $args = [];

        $pathParts = explode('/', $path);
        $base = null;
        if (count($pathParts)) {
            $base = array_shift($pathParts);
            foreach($pathParts as $index => $pathPart) {
                $args['_arg'.$index] = $pathPart;
            }
        }
        // Let's assume the URL will never have get parameters with _arg# in it.
        $args = array_merge($args, $get);

        $urlData = false;
        if ($base === 'forum') {
            if (val('_arg0', $args) == 'categories') {
                $urlData = [
                    'CategoryCode' => val('_arg1', $args),
                    'CategoryID' => $this->ningIDFilter(val('categoryId', $args)),
                    'Page' => val('page', $args),
                ];
            } elseif (val('_arg0', $args) == 'topics') {
                $urlData = [
                    'DiscussionNameCode' => val('_arg1', $args),
                    'DiscussionID' => $this->ningIDFilter(val('id', $args)),
                    'CommentID' => $this->ningIDFilter(val('commentId', $args)),
                    'Page' => val('page', $args),
                ];
            }
        } elseif ($base === 'nx') {
            if (val('_arg0', $args) == 'detail') {
                $details = $this->ningIDSplitter($args['_arg1']);

                if ($details['Type'] == 'Comment') {
                    $urlData = [
                        'CommentID' => $this->ningIDFilter(val('_arg1', $args)),
                    ];
                } elseif($details['Type'] == 'Topic') {
                    $urlData = [
                        'DiscussionID' => $this->ningIDFilter(val('_arg1', $args)),
                    ];
                } elseif ($details['Type'] == 'Group') {
                    $urlData = [
                        'GroupID' => $this->ningIDFilter(val('_arg1', $args)),
                    ];
                }
            }
        } elseif ($base === 'profile') {
             $urlData = [
                'UserNameCode' => val('_arg0', $args),
            ];
        } elseif ($base === 'group') {
             $urlData = [
                'GroupNameCode' => val('_arg0', $args),
            ];
        }

        if ($urlData) {
            $this->redirectRequest($urlData);
        }
    }

    /*
     * Treat "user not found" as "dispatcher didn't find correct route"
     * Needed for ning since they use /profile/NAME
     *
     * @param ProfileController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function profileController_userLoaded_handler($sender, $args) {
        if (val('User', $sender) === false) {
            $this->gdn_dispatcher_notFound_handler();
        }
    }

    /*
     * Treat "group not found" as "dispatcher didn't find correct route"
     * Needed for ning since they use /group/NAME
     *
     * @param GroupController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function groupController_groupLoaded_handler($sender, $args) {
        if (val('Group', $args) === false) {
            $this->gdn_dispatcher_notFound_handler();
        }
    }

    /**
     * Determine the correct URL to redirect the request to, from the supplied data.
     *
     * @param $urlData Data containing the IDs or URLCode used to find the correct route.
     */
    protected function redirectRequest($urlData) {
        $destinationURL = false;

        if (isset($urlData['CommentID'])) {
            trace("Looking up comment {$urlData['CommentID']}.");

            $commentModel = new CommentModel();
            $comment = $commentModel->getID($urlData['CommentID']);

            if ($comment) {
                $destinationURL = commentUrl($comment, '//');
            }
        } elseif (isset($urlData['DiscussionID'])) {
            trace("Looking up discussion {$urlData['DiscussionID']}.");

            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($urlData['DiscussionID']);

            if ($discussion) {
                $destinationURL = discussionUrl($discussion, $this->pageNumber($urlData, 'Vanilla.Comments.PerPage'), '//');
            }
        } elseif (isset($urlData['GroupID']) && class_exists('GroupModel')) {
            trace("Looking up group {$urlData['GroupID']}.");

            $groupModel = new GroupModel();
            $group = $groupModel->getID($urlData['GroupID']);

            if ($group) {
                $destinationURL = groupUrl($group, null, '//');
            }
        } elseif (isset($urlData['CategoryID'])) {
            trace("Looking up category {$urlData['CategoryID']}.");

            $category = CategoryModel::categories($urlData['CategoryID']);

            if ($category) {
                $destinationURL = categoryUrl($category, $this->pageNumber($urlData, 'Vanilla.Discussions.PerPage'), '//');
            }
        }

        // Let's try a fallback.
        if (!$destinationURL) {
            if (isset($urlData['UserNameCode'])) {
                trace("Looking up user code[{$urlData['UserNameCode']}].");

                $user = Gdn::userModel()->getWhere(['LegacyNameURLCode' => $urlData['UserNameCode']])->firstRow(DATASET_TYPE_ARRAY);
                if ($user) {
                    $destinationURL = url(userUrl($user), '//');
                }
            } elseif (isset($urlData['DiscussionNameCode'])) {
                trace("Looking up user code[{$urlData['DiscussionNameCode']}].");

                $discussionModel = new DiscussionModel();

                $discussion = $discussionModel->getWhere(['LegacyNameURLCode' => $urlData['DiscussionNameCode']])->firstRow(DATASET_TYPE_ARRAY);
                if ($discussion) {
                    $destinationURL = discussionUrl($discussion, $this->pageNumber($urlData, 'Vanilla.Comments.PerPage'), '//');
                }
            } elseif (isset($urlData['GroupNameCode']) && class_exists('GroupModel')) {
                trace("Looking up group code[{$urlData['GroupNameCode']}].");

                $groupModel = new GroupModel();
                $group = $groupModel->getWhere(['LegacyNameURLCode' => $urlData['GroupNameCode']])->firstRow(DATASET_TYPE_ARRAY);
                if ($group) {
                    $destinationURL = groupUrl($group, null, '//');
                }
            } elseif (isset($urlData['CategoryCode'])) {
                trace("Looking up category code[{$urlData['CategoryCode']}].");

                $category = CategoryModel::instance()->getByCode($urlData['CategoryCode']);
                if ($category) {
                    $pageNumber = $this->pageNumber($urlData, 'Vanilla.Discussions.PerPage');

                    if ($pageNumber > 1) {
                        $pageParam = '?Page='.$pageNumber;
                    } else {
                        $pageParam = null;
                    }
                    $destinationURL = categoryUrl($category, '', '//').$pageParam;
                }
            }
        }

        if ($destinationURL) {
            if (debug()) {
                echo '<pre>'.print_r($destinationURL, true);
                die();
            } else {
                redirect($destinationURL, 301);
            }
        }
    }

    /**
     * Split a Ning's ID string into its components
     *
     * @param $value int:string:int
     * @return array [ParentID, IDType, ID]
     */
    protected function ningIDSplitter($value) {
        list($idParent, $type, $id) = explode(':', $value);
        return [
            'IdParent' => $idParent,
            'Type' => $type,
            'Id' => $id,
        ];
    }

    /**
     * Get ID value from a Ning's ID string.
     *
     * @param $value int:string:int
     * @return mixed
     */
    protected function ningIDFilter($value) {
        return $this->ningIDSplitter($value)['Id'];
    }

    /**
     * Return the page number from the given variables that may have an offset or a page.
     *
     * @param array $vars The variables that should contain an Offset or Page key.
     * @param int|string $pageSize The pagesize or the config key of the pagesize.
     * @return int
     */
    protected function pageNumber($vars, $pageSize) {
        if (isset($vars['Page'])) {
            return $vars['Page'];
        }

        if (isset($vars['Offset'])) {
            if (is_numeric($pageSize)) {
                return pageNumber($vars['Offset'], $pageSize, false, Gdn::session()->isValid());
            } else {
                return pageNumber($vars['Offset'], c($pageSize, 30), false, Gdn::session()->isValid());
            }
        }
        return 1;
    }
}
