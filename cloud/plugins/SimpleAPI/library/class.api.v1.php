<?php if (!defined('APPLICATION')) exit();

/**
 * API Mapper v1
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @license Proprietary
 */
class ApiMapper extends SimpleApiMapper {

    public $Version = '1.0';

    public function __construct() {

        $this->URIMap = [
            // Categories
            'categories/add'        => 'vanilla/settings/addcategory',
            'categories/edit'       => 'vanilla/settings/editcategory',
            'categories/delete'     => 'vanilla/settings/deletecategory',
            'categories/list'       => 'vanilla/categories/apiv1list',
            'categories/get'        => 'vanilla/settings/getcategory',

            // Discussions
            'discussions/add'       => 'vanilla/post/discussion',
            'discussions/bookmark'  => 'vanilla/discussion/bookmark',
            'discussions/edit'      => 'vanilla/post/editdiscussion',
            'discussions/category'  => 'vanilla/categories',
            'discussions/list'      => 'vanilla/discussions',

            // Comments
            'comments/add'          => 'vanilla/post/comment',
            'comments/edit'         => 'vanilla/post/editcomment',

            // Badges
            'badges/give'           => 'badge/giveuser',
            'badges/revoke'         => 'badge/revoke',
            'badges/user'           => 'badges/user',
            'badges/list'           => 'badges/all',

            // Users
            'users/authenticate'    => 'dashboard/user/authenticate',
            'users/edit'            => 'dashboard/profile/edit',
            'users/photo'           => 'dashboard/profile/picture',
            'users/multi'           => 'dashboard/profile/multi',
            'users/notifications'   => 'dashboard/profile/preferences',
            'users/discussions'     => 'dashboard/profile/discussions',
            'users/comments'        => 'dashboard/profile/comments',
            'users/get'             => 'dashboard/profile',
            'users/save'            => 'dashboard/user/save',
            'users/add'             => 'dashboard/user/save',
            'users/sso'             => 'dashboard/user/sso',
            'users/delete'          => 'dashboard/user/delete2',
            'users/merge'           => 'dashboard/user/merge',

            // Roles
            'roles/add'             => 'dashboard/role/add',
            'roles/edit'            => 'dashboard/role/edit',
            'roles/list'            => 'dashboard/role',
            'roles/get'             => 'dashboard/role',

            // Configuration
            'configuration'         => 'dashboard/settings/configuration'

        ];

        $this->Filter = [
            'users/multi' => ['Users'],
            'users/notifications' => ['Profile', 'Preferences', 'PreferenceList']
        ];

    }

    public function map($aPIRequest) {
        $trimmedRequest = trim($aPIRequest, ' /');
        foreach ($this->URIMap as $matchURI => $mapURI) {
            if (preg_match("`{$matchURI}(\.(:?json|xml))?$`i", $trimmedRequest)) {
                $this->Mapping = $matchURI;
                return preg_replace("`{$matchURI}(\.(:?json|xml))?`i", "{$mapURI}\$1", $trimmedRequest);
            }
        }

        return $aPIRequest;
    }

    public function filter(&$data) {
        $filter = val($this->Mapping, $this->Filter, NULL);
        if (empty($filter)) return;

        $data = arrayTranslate($data, $filter);
    }

}