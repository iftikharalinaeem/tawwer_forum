<?php
/**
 * VanillaAnalytics plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Analytics
 */

$PluginInfo['VanillaAnalytics'] = array(
    'Name'                 => 'Vanilla Analytics',
    'Description'          => 'Support for transmitting events to analytics services.',
    'Version'              => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'Author'               => 'Ryan Perry',
    'AuthorEmail'          => 'ryan.p@vanillaforums.com',
    'AuthorUrl'            => 'http://vanillaforums.org/profile/initvector'
);

/**
 * Facilitate the tracking of events from Vanilla to one (or more) analytics services.
 */
class VanillaAnalytics extends Gdn_Plugin {

    /**
     * Track the generic 404 response.
     */
    public function gdn_dispatcher_notFound_handler() {
        AnalyticsTracker::getInstance()->trackEvent('error', 'error_notfound');
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
        $type = val('Insert', $args) ? 'comment_add' : 'comment_edit';

        $data = AnalyticsData::getComment(val('CommentID', $args), $type);

        AnalyticsTracker::getInstance()->trackEvent('post', $type, $data);
    }

    /**
     * Track when a comment is deleted.
     *
     * @param $sender Current instance of CommentModel
     * @param $args Event arguments, passed from CommentModel, specifically for the event.
     */
    public function commentModel_deleteComment_handler($sender, &$args) {
        $data = AnalyticsData::getComment(val('CommentID', $args));

        AnalyticsTracker::getInstance()->trackEvent('post', 'comment_delete', $data);
    }

    /**
     * Track when a discussion is saved.  This can be used to record an event for inserts or edits.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_afterSaveDiscussion_handler($sender, &$args) {
        $type = val('Insert', $args) ? 'discussion_add' : 'discussion_edit';

        $data = AnalyticsData::getDiscussion(val('DiscussionID', $args));

        AnalyticsTracker::getInstance()->trackEvent('post', $type, $data);
    }

    /**
     * Track when a discussion is deleted.
     *
     * @param $sender Current instance of DiscussionModel
     * @param $args Event arguments, passed from DiscussionModel, specifically for the event.
     */
    public function discussionModel_deleteDiscussion_handler($sender, &$args) {
        $data = AnalyticsData::getDiscussion(val('DiscussionID', $args));

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
     * @todo Add siteDuration (time between sign-in and sign-out)
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

        $discussionID = 0;
        $recordID = val('RecordID', $args);
        $recordType = strtolower(val('RecordType', $reactionData));
        $recordUser = AnalyticsData::getUser(0);
        $urlCode = val('ReactionUrlCode', $args);

        $reactionType = ReactionModel::reactionTypes($urlCode);

        switch ($recordType) {
            case 'comment':
                $commentModel = new CommentModel();
                $commentDetails = $commentModel->getID($recordID);
                if ($commentDetails) {
                    $discussionID = $commentDetails->DiscussionID;
                    $recordUser = AnalyticsData::getUser($commentDetails->InsertUserID);
                }
                break;
            case 'discussion':
                $discussionModel = new DiscussionModel();
                $discussionDetails = $discussionModel->getID($recordID);
                if ($discussionDetails) {
                    $discussionID = $discussionDetails->DiscussionID;
                    $recordUser = AnalyticsData::getUser($discussionDetails->InsertUserID);
                }
                break;
            default:
        }

        // Grabbing the relevant information from the ReactionData event argument
        $data = [
            'reaction' => [
                'discussionID' => (int)$discussionID,
                'reactionType' => val('Name', $reactionType, null),
                'recordType'   => $recordType,
                'recordID'     => (int)$recordID,
                'recordUser'   => $recordUser,
                'urlCode'      => strtolower($urlCode),
                'tagID'        => (int)val('TagID', $reactionData),
                'total'        => (int)val('Total', $reactionData)
            ]
        ];

        $event = val('Total', $reactionData) > 0 ? 'reaction_add' : 'reaction_delete';

        AnalyticsTracker::getInstance()->trackEvent('reaction', $event, $data);
    }

    /**
     * Setup routine to run when the plug-in is enabled.
     *
     * @throws Gdn_UserException
     */
    public function setup() {
        $this->structure();
    }

    /**
     * @throws Gdn_UserException
     */
    public function structure() {
        // Are we missing a valid keen.io project ID or a write key?
        if (!c('VanillaAnalytics.KeenIO.ProjectID') || !c('VanillaAnalytics.KeenIO.WriteKey')) {
            // When the plug-in is first enabled, our KeenIOClient class won't be enabled.  Grab the class.
            if (!class_exists('KeenIOClient')) {
                require_once(PATH_PLUGINS . '/vanillaanalytics/models/class.keenioclient.php');
            }

            // Attempt to grab all the necessary data for creating a project with keen.io
            $defaultProjectUser = c('VanillaAnalytics.KeenIO.DefaultProjectUser');
            $site = class_exists('Infrastructure') ? Infrastructure::site('name') : c('Garden.Domain', null);
            $orgID  = c('VanillaAnalytics.KeenIO.OrgID');
            $orgKey = c('VanillaAnalytics.KeenIO.OrgKey');

            // All of these pieces are essential for creating a project.  Fail without them.
            if (!$orgID) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgID');
            }
            if (!$orgKey) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.OrgKey');
            }
            if (!$defaultProjectUser) {
                throw new Gdn_UserException('Empty value for VanillaAnalytics.KeenIO.DefaultProjectUser');
            }

            // Build the keen.io client and attempt to create a new project
            $keenIOConfig = [
                'orgID'  => $orgID,
                'orgKey' => $orgKey
            ];
            $keenIOClient = new KeenIOClient(null, $keenIOConfig);

            $project = $keenIOClient->addProject(
                $site,
                [
                    [
                        'email' => $defaultProjectUser
                    ]
                ]
            );

            // If we were successful, save the details.  If not, trigger an error.
            if ($project) {
                saveToConfig('VanillaAnalytics.KeenIO.ProjectID', $project->id);
                saveToConfig('VanillaAnalytics.KeenIO.ReadKey', $project->apiKeys->readKey);
                saveToConfig('VanillaAnalytics.KeenIO.WriteKey', $project->apiKeys->writeKey);
            } else {
                throw new Gdn_UserException('Unable to create project on keen.io');
            }
        }
    }
}
