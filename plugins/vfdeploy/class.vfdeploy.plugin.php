<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['vfdeploy'] = array(
    'Name' => 'Infrastructure VIP Self Deploy',
    'Description' => "This plugin allows VIP customers to self-deploy their custom code.",
    'Version' => '1.1.0',
    'MobileFriendly' => true,
    'RequiredApplications' => false,
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => true,
    'RegisterPermissions' => [
        'VanillaForums.Code.Deploy'
    ],
    'Icon' => 'infrastructure_vip_self_deploy.png',
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Allow VIP customers to self-deploy their custom code.
 *
 * Changes
 *  1.0     Initial release
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package infrastructure
 * @subpackage vfdeploy
 * @since 1.0
 */
class VFDeployPlugin extends Gdn_Plugin {

    const DEPLOYING_KEY = 'vip.cluster.deploying';
    const DEPLOYING_SITE_KEY = 'vip.site.deploying.%d';

    const DEPLOY_DELAY = 10; // minutes

    /**
     * Add sidemenu
     *
     * @param Gdn_Controller $sender
     * @return type
     */
    public function base_getAppSettingsMenuItems_handler($sender) {

        // Is self-deploy configured for this site?
        $deploy = C('Deploy');
        if (!$deploy) {
            return;
        }

        // Is self-deploy allowed for this site right now?
        $canDeploy = val('Allow', $deploy);
        if (!$canDeploy) {
            return;
        }

        // Is self-deploy fully configured?
        $customerRepo = val('Repo', $deploy);
        if (!$customerRepo) {
            return;
        }

        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Dashboard', T('VIP Deploy'), '/settings/deploy', 'VanillaForums.Code.Deploy');
    }

    /**
     * Plugin interface sub-dispatcher
     *
     * @param SettingsController $sender
     */
    public function settingscontroller_deploy_create($sender) {
        $sender->permission('VanillaForums.Code.Deploy');
        $sender->addSideMenu('settings/deploy');
        $sender->Form = new Gdn_Form();

        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Deploy UI
     *
     * Provides a UI for deploying code to the current site's cluster.
     *
     * @param SettingsController $sender
     */
    public function controller_index($sender) {
        $sender->permission('VanillaForums.Code.Deploy');
        $sender->title('VIP Self Deploy');
        $sender->addCssFile('deploy.css', 'plugins/vfdeploy');

        $sender->form = $sender->Form;
        $this->prepareSender($sender);

        $sender->render('deploy', '', 'plugins/vfdeploy');
    }

    /**
     * Deploy send
     *
     * @param SettingsController $sender
     */
    public function controller_send($sender) {
        $sender->permission('VanillaForums.Code.Deploy');
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_DATA);

        $sender->form = $sender->Form;
        $deploy = $this->prepareSender($sender);
        $customerRepo = val('Repo', $deploy);

        if ($sender->form->authenticatedPostBack()) {

            if ($sender->data('DeployLock') != 'free') {
                $sender->form->addError(T("Code is currently being deployed, please be patient."));
            }

            if (!$sender->form->errorCount()) {
                try {

                    $siteID = Infrastructure::site('SiteID');
                    $deployStatusKey = sprintf(self::DEPLOYING_SITE_KEY, $siteID);

                    // Make other processes aware that a deploy is happening
                    Gdn::cache()->store(self::DEPLOYING_KEY, $siteID, [
                        Gdn_Cache::FEATURE_EXPIRY => (60 * self::DEPLOY_DELAY)
                    ]);

                    $sender->setData('DeployLock', 'site');

                    // Status: updating code
                    Gdn::cache()->store($deployStatusKey, "updating code", [
                        Gdn_Cache::FEATURE_EXPIRY => (60 * self::DEPLOY_DELAY)
                    ]);

                    // Update customer repo
                    $pull = Communication::data('/cluster/pull', Infrastructure::cluster())
                            ->method('post')
                            ->parameter('repositories', [
                                $customerRepo
                            ]);
                    $pull->send();
                    $pull->error();

                    // Status: pushing code
                    Gdn::cache()->store($deployStatusKey, "pushing code", [
                        Gdn_Cache::FEATURE_EXPIRY => (60 * self::DEPLOY_DELAY)
                    ]);

                    // Deploy cluster code
                    $userName = Gdn::session()->User->Name;
                    $userID = Gdn::session()->UserID;
                    $siteName = Infrastructure::site('Name');
                    $push = Communication::data('/cluster/deploy', Infrastructure::cluster())
                            ->method('post')
                            ->parameter('user', strtolower($userName))
                            ->parameter('message', "self-deploy from {$siteName} for {$userName}/{$userID}");
                    $push->send();
                    $push->error();

                    $sender->setData('success', true);

                } catch (Exception $Ex) {
                    $sender->form->addError($Ex->getMessage());
                }
            }
        }
    }

    /**
     * Get current push status
     *
     * @param SettingsController $sender
     */
    public function controller_status($sender) {
        $sender->permission('VanillaForums.Code.Deploy');
        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_DATA);

        $this->prepareSender($sender);

        $sender->render();
    }

    /**
     * Set required data fields on sender
     *
     * This method centralizes cache checking so that we don't have to dupe code.
     *
     * @param SettingsController $sender
     * @return array
     */
    protected function prepareSender($sender) {
        // Is self-deploy configured for this site?
        $deploy = C('Deploy');
        if (!$deploy) {
            throw ForbiddenException('self-deploy code to this site');
        }

        // Is self-deploy allowed for this site right now?
        $canDeploy = val('Allow', $deploy);
        if (!$canDeploy) {
            throw ForbiddenException('self-deploy code right now');
        }

        // Is self-deploy fully configured?
        $customerRepo = val('Repo', $deploy);
        if (!$customerRepo) {
            throw new Exception("Unable to deploy, self-deploy not fully configured.", 500);
        }

        $sender->setData('Deploy', $deploy);

        // Default ok to proceed with deployment
        $sender->setData('DeployLock', 'free');
        $sender->setData('DeployStatus', 'none');
        $sender->setData('Frequency', 30);

        // Are we deploying right now?

        $deploying = Gdn::cache()->get(self::DEPLOYING_KEY);
        $siteID = Infrastructure::site('SiteID');
        $deployStatusKey = sprintf(self::DEPLOYING_SITE_KEY, $siteID);

        if ($deploying) {
            $sender->setData('Frequency', 10);
            if ($deploying != $siteID) {
                $sender->setData('DeployLock', 'cluster');
            } else {
                $sender->setData('DeployLock', 'site');
                $deployStatus = Gdn::cache()->get($deployStatusKey);
                if ($deployStatus) {
                    $sender->setData('DeployStatus', $deployStatus);
                }
            }
        }

        return $deploy;
    }

    public function setup() {

    }

}
