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

    protected $analyticsTracker;

    /**
     *
     */
    public function analyticsTracker() {
        if (empty($this->analyticsTracker)) {
            $this->analyticsTracker = AnalyticsTracker::getInstance();

            // For now, using keen.io is hardwired.
            if (c('VanillaAnalytics.KeenIO.ProjectID') && c('VanillaAnalytics.KeenIO.WriteKey')) {
                $this->analyticsTracker->addTracker(new KeenIOTracker());
            }
        }

        return $this->analyticsTracker;
    }

    /**
     * Track the generic 404 response.
     */
    public function gdn_dispatcher_notFound_handler() {
        $this->analyticsTracker()->trackEvent('notFound');
    }

    /**
     * @param $sender Controller instance
     */
    public function base_render_before($sender) {
        $this->analyticsTracker()->addJsFiles($sender);
        $this->analyticsTracker()->addDefinitions($sender);
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
            $this->analyticsTracker()->trackEvent($event, $data);
        }
    }
}
