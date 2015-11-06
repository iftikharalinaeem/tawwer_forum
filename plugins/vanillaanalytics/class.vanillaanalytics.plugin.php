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

class VanillaAnalytics extends Gdn_Plugin {
    public function DiscussionModel_AfterSaveDiscussion_Handler($sender, &$args) {
        $discussionModel = new DiscussionModel();

        $discussion = $discussionModel->getID(val('DiscussionID', $args));
        $isInserted = val('Insert', $args);

        if ($discussion) {
            $eventData = [
                'discussionID' => $discussion->DiscussionID
            ];

            $eventData['discussionName'] = $discussion->Name;

            $eventData['categories'] = [];
            $categories = CategoryModel::getAncestors($discussion->CategoryID);
            foreach ($categories as $currentCategory) {
                $eventData['categories'][] = [
                    'CategoryID' => $currentCategory['CategoryID'],
                    'Name' => $currentCategory['Name']
                ];
            }

            if ($isInserted) {
                AnalyticsTracker::trackEvent('discussionInsert', $eventData);
            } else {
                AnalyticsTracker::trackEvent('discussionEdit', $eventData);
            }
        } else {
            trace('Discussion not found: ' . val('DiscussionID', $args, 'No ID specified.'), TRACE_ERROR);
        }
    }
}
