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
        AnalyticsTracker::getInstance()->trackEvent('error', 'error_notFound');
    }

    /**
     * Hook in before a view is rendered.
     *
     * @param $sender Controller instance
     */
    public function base_render_before($sender) {
        // Give analytics trackers the ability to add JavaScript files
        AnalyticsTracker::getInstance()->addJsFiles($sender);

        // Allow trackers to add to values in a page's gdn.meta JavaScript array
        AnalyticsTracker::getInstance()->addDefinitions($sender);
    }

    /**
     * Track when a comment is saved.  This includes inserts and updates.
     *
     * @param $sender Current instance of CommentModel
     * @param $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_afterSaveComment_handler($sender, &$args) {
        $data = [
            'comment' => AnalyticsData::getComment(val('CommentID', $args))
        ];

        $event = val('Insert', $args) ? 'comment_add' : 'comment_edit';
        AnalyticsTracker::getInstance()->trackEvent('post', $event, $data);
    }

    /**
     * Track when a comment is deleted.
     *
     * @param $sender Current instance of CommentModel
     * @param $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_deleteComment_handler($sender, &$args) {
        $data = [
            'comment' => AnalyticsData::getComment(val('CommentID', $args))
        ];

        AnalyticsTracker::getInstance()->trackEvent('post', 'comment_delete', $data);
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, &$args) {
        $data = [
            'discussion' => AnalyticsData::getDiscussion(val('DiscussionID', $args))
        ];

        $event = val('Insert', $args) ? 'discussion_add' : 'discussion_edit';
        AnalyticsTracker::getInstance()->trackEvent('post', $event, $data);
    }

    /**
     * Track when a discussion is deleted.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_deleteDiscussion_handler($sender, &$args) {
        $data = [
            'discussion' => AnalyticsData::getDiscussion(val('DiscussionID', $args))
        ];

        AnalyticsTracker::getInstance()->trackEvent('post', 'discussion_delete', $data);
    }

    /**
     * Track when a user successfully registers for the site.
     *
     * @param $sender Current instance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function entryController_registrationSuccessful_handler($sender, &$args) {
        AnalyticsTracker::getInstance()->trackEvent('registration', 'registration_success');
    }

    /**
     * Track when a user signs into the site.
     *
     * @param $sender Current instance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function gdn_session_start_handler($sender, $args) {
        AnalyticsTracker::getInstance()->trackEvent('session', 'session_start');
    }

    /**
     * Track when a user signs out of the site.
     *
     * @param $sender Current instance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function gdn_session_end_handler($sender, $args) {
        AnalyticsTracker::getInstance()->trackEvent('session', 'session_end');
    }

    /**
     * Track when a user performs a reaction.
     *
     * @param $sender Current instance of ReactionsPlugin
     * @param $args Event arguments, passed from ReactionsPlugin or ReactionModel, specifically for the event.
     */
    public function reactionsPlugin_reaction_handler($sender, &$args) {
        $reactionData = val('ReactionData', $args);

        // Grabbing the relevant information from the ReactionData event argument
        $data = [
            'reaction' => [
                'recordType' => strtolower(val('RecordType', $reactionData)),
                'recordID' => (int)val('RecordID', $args),
                'urlCode' => strtolower(val('ReactionUrlCode', $args)),
                'tagID' => (int)val('TagID', $reactionData),
                'total' => (int)val('Total', $reactionData)
            ]
        ];

        $event = val('Total', $reactionData) > 0 ? 'reaction_add' : 'reaction_delete';

        AnalyticsTracker::getInstance()->trackEvent('reaction', $event, $data);
    }
}
