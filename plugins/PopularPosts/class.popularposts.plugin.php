<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['PopularPosts'] = array(
    'Name' => 'Popular posts',
    'Description' => 'Shows popular posts',
    'Version' => '1.0',
    //'RequiredApplications' => array('Vanilla' => '????'), // TODO Ask how to determine plugin version
    /*'RequiredTheme' => false,
    'HasLocale' => false,*/
    //'SettingsUrl' => '/settings/PopularPosts',
    //'SettingsPermission' => 'Garden.Settings.Manage',
    /*'MobileFriendly' => true,*/
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class PrivateCommunityPlugin
 */
class PopularPostsPlugin extends Gdn_Plugin {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Inject the popular posts module on discussions/index
     *
     * @param $sender Sending controller instance
     */
    public function discussionsController_render_before($sender) {

        if ($sender->OriginalRequestMethod === 'index') {
            $sender->addModule('popularPostsModule');
        }

    }
}