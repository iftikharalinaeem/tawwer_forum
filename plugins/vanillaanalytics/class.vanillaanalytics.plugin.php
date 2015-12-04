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

    /**
     * Track the generic 404 response.
     */
    public function gdn_dispatcher_notFound_handler() {
        AnalyticsTracker::getInstance()->trackEvent('notFound');
    }

    /**
     * @param $sender Controller instance
     */
    public function base_render_before($sender) {
        AnalyticsTracker::getInstance()->addJsFiles($sender);
        AnalyticsTracker::getInstance()->addDefinitions($sender);
    }

    public function commentModel_afterSaveComment_handler($sender, &$args) {
        $data = AnalyticsData::comment(val('CommentID', $args));

        if ($data) {
            $event = val('Insert', $args) ? 'commentInsert' : 'commentEdit';
            AnalyticsTracker::getInstance()->trackEvent($event, $data);
        }
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the AfterSaveDiscussion event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, &$args) {
        $data = AnalyticsData::discussion(val('DiscussionID', $args));

        if ($data) {
            $event = val('Insert', $args) ? 'discussionInsert' : 'discussionEdit';
            AnalyticsTracker::getInstance()->trackEvent($event, $data);
        }
    }

    public function entryController_registrationSuccessful_handler($sender, &$args) {
        AnalyticsTracker::getInstance()->trackEvent('userRegistration');
    }

    public function reactionsPlugin_reaction_handler($sender, &$args) {
        $reactionData = val('ReactionData', $args);

        $data = [
            'reaction' => [
                'recordType' => val('RecordType', $reactionData),
                'recordID' => (int)val('RecordID', $args),
                'urlCode' => val('ReactionUrlCode', $args),
                'tagID' => (int)val('TagID', $reactionData),
                'total' => (int)val('Total', $reactionData)
            ]
        ];

        AnalyticsTracker::getInstance()->trackEvent('reaction', $data);
    }
}
