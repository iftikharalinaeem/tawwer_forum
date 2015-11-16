<?php
/**
 * VanillaAnalytics plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Analytics
 */

$PluginInfo['VanillaAnalytics'] = array(
    'Name' => 'Vanilla Analytics',
    'Description' => 'Support for transmitting events to analytics services.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'Author' => 'Ryan Perry',
    'AuthorEmail' => 'ryan.p@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.org/profile/initvector'
);

/**
 * Facilitate the tracking of events from Vanilla to one (or more) analytics services.
 */
class VanillaAnalytics extends Gdn_Plugin {

    public function __construct() {

        // Grab the Composer autoloader.
        require_once(dirname(__FILE__) . '/vendor/autoload.php');

        // For now, using keen.io is hardwired.
        AnalyticsTracker::addTracker(new KeenIOTracker());
    }

    /**
     * Track the generic 404 response.
     */
    public function Gdn_Dispatcher_NotFound_Handler() {
        AnalyticsTracker::trackEvent('notFound');
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the AfterSaveDiscussion event.
     */
    public function DiscussionModel_AfterSaveDiscussion_Handler($sender, &$args) {

        $data = AnalyticsData::discussion(val('DiscussionID', $args));

        if ($data) {
            $event = val('Insert', $args) ? 'discussionInsert' : 'discussionEdit';
            AnalyticsTracker::trackEvent($event, $data);
        }
    }
}
