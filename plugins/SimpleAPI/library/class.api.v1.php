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

        $this->URIMap = array(
            // Categories
            'categories/add'        => 'vanilla/settings/addcategory',
            'categories/edit'       => 'vanilla/settings/editcategory',
            'categories/delete'     => 'vanilla/settings/deletecategory',
            'categories/list'       => 'vanilla/categories/all',
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
            'badges/give'           => 'reputation/badge/giveuser',
            'badges/revoke'         => 'reputation/badge/revoke',
            'badges/user'           => 'reputation/badges/user',
            'badges/list'           => 'reputation/badges/all',

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

        );

        $this->Filter = array(
            'users/multi' => array('Users'),
            'users/notifications' => array('Profile', 'Preferences', 'PreferenceList')
        );

    }

    public function map($APIRequest) {
        $TrimmedRequest = trim($APIRequest, ' /');
        foreach ($this->URIMap as $MatchURI => $MapURI) {
            if (preg_match("`{$MatchURI}(\.(:?json|xml))?$`i", $TrimmedRequest)) {
                $this->Mapping = $MatchURI;
                return preg_replace("`{$MatchURI}(\.(:?json|xml))?`i", "{$MapURI}\$1", $TrimmedRequest);
            }
        }

        return $APIRequest;
    }

    public function filter(&$Data) {
        $Filter = val($this->Mapping, $this->Filter, NULL);
        if (empty($Filter)) return;

        $Data = arrayTranslate($Data, $Filter);
    }

}