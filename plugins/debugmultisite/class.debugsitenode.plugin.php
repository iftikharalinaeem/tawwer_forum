<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license proprietary
 */

/**
 * Class DebugSiteNodePlugin
 *
 * Utility scripts to run multisite actions locally for debugging purposes.
 */

class DebugSiteNodePlugin extends SiteNodePlugin {

    /**
     * Make a call to a Site Hub spoofing as one of its nodes.
     *
     * @param UtilityController $sender
     * @throws Gdn_UserException For missing config settings.
     */
    public function utilityController_spoofSyncNode_create(UtilityController $sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('title', 'Synchronize From a Hub');
        $sender->setData('messageclass', 'danger');
        $sender->setData('instructions', 'Be careful you are about to overwrite a lot of data in your local database.');
        $sender->setData('configuredHubURL', c('Hub.Spoof.Values.hubURL', 'You have not configured a forum from which to sync. (Hub.Spoof.Values.hubURL)'));
        $sender->setData('configuredSpoofSlug', c('Hub.Spoof.Values.spoofSlug'), 'You have not configured the node slug you wish to imitate. (Hub.Spoof.Values.spoofSlug)');
        $sender->setData('configuredSpoofToken', c('Hub.Spoof.Values.spoofToken'), 'You have not configured API V1 Token of the hube site. (Hub.Spoof.Values.spoofToken)');
        $spoofValues = c('Hub.Spoof.Values', []);
        $params = $this->_generateParams($spoofValues);
        if ($sender->Form->AuthenticatedPostBack()) {
            if (!$spoofValues['hubURL']) {
                throw new Gdn_UserException('You need to provide a URL of a hub site that you want to sync from in Hub.Spoof.Values.hubURL');
            }

            if (!$spoofValues['spoofSlug']) {
                throw new Gdn_UserException('You need to provide the slug of the node site that you want to sync in Hub.Spoof.Values.spoofSlug');
            }

            if (!$spoofValues['spoofToken']) {
                throw new Gdn_UserException('You need to provide API V1 token of the hub site that you want to sync from in Hub.Spoof.Values.spoofToken');
            }

            $this->syncNode($params);
            $sender->setData('instructions', 'Sychronized successfully!');
            $sender->setData('messageclass', 'success');
        }

        /**
         * TODO loop through the config and display what is configured to happen during syncing.
         */
        if ($params) {
            $config = $this->hubApi('/multisites/nodeconfig.json', 'GET', $params + ['from' => $this->slug()], true);
            $sender->setData('hubConfig', $config);
        }
        $sender->render('spoofsyncnode', '', 'plugins/debugsitenode');
    }

    /**
     * Get the parameters from the confg and arrange them into a nice array.
     *
     * @param array $spoofValues Configured values from that imitate a node's values.
     * @return array
     */
    private function _generateParams($spoofValues): array {
        $this->hubUrl = $spoofValues['hubURL'];
        $localhost = $spoofValues['localhost'] ?? false;
        $params = [];
        if ($spoofValues['hubURL'] && $spoofValues['spoofToken'] && $spoofValues['spoofSlug']) {
            $params = ['from' => $spoofValues['spoofSlug'], 'spoofToken' => $spoofValues['spoofToken'], 'localhost' => $localhost, 'mode' => 'debug'];
        }
        return  $params;
    }
}
