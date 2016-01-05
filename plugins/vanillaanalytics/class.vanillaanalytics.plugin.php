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
            'comment' => AnalyticsData::comment(val('CommentID', $args))
        ];

        if ($data) {
            $event = val('Insert', $args) ? 'commentInsert' : 'commentEdit';
            AnalyticsTracker::getInstance()->trackEvent($event, $data);
        }
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, &$args) {
        $data = [
            'discussion' => AnalyticsData::discussion(val('DiscussionID', $args))
        ];

        if ($data) {
            $event = val('Insert', $args) ? 'discussionInsert' : 'discussionEdit';
            AnalyticsTracker::getInstance()->trackEvent($event, $data);
        }
    }

    /**
     * Track when a user successfully registers for the site.
     *
     * @param $sender Current isntance of EntryController
     * @param $args Event arguments, passed from EntryController, specifically for the event.
     */
    public function entryController_registrationSuccessful_handler($sender, &$args) {
        AnalyticsTracker::getInstance()->trackEvent('userRegistration');
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

        AnalyticsTracker::getInstance()->trackEvent('reaction', $data);
    }
}
