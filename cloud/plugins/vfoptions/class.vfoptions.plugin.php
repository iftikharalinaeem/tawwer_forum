<?php

/**
 * @copyright 2010-2017 Vanilla Forums Inc
 * @license Proprietary
 */

// Define the plugin:
use Vanilla\Models\AddonModel;

$PluginInfo['vfoptions'] = array(
    'Name' => 'VF.com Admin Options',
    'Description' => 'VF.com admin options.',
    'Version' => '2.0.5',
    'MobileFriendly' => true,
    'Author' => "Vanilla Developers",
    'AuthorEmail' => 'dev@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com',
    'Hidden' => true
);

/**
 * VF Options
 *
 * Changes
 * 2.0.5    Hide s3files plugin even when enabled
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package infrastructure
 * @subpackage vfoptions
 * @since 1.0
 */
class VFOptionsPlugin implements Gdn_IPlugin {

    protected $lockedPlugins;
    protected $hiddenPlugins;

    /**
     * Whether current user should have access to cloud options.
     *
     * @return bool
     */
    public function hasInfPermission() {
        if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return false;
        }

        if (Gdn::session()->User->Admin != 2) {
            return false;
        }

        return true;
    }

    /**
     * Set environment changes at very start of the app load.
     *
     * @param $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {

        // Disable management of Captcha API key in Dashboard -> Registration.
        saveToConfig('Garden.Registration.ManageCaptcha', false, false);

        // Analytics default level
        if (strtolower($sender->controller()) == 'settings' && !c('VanillaAnalytics.Level') && class_exists('Infrastructure')) {
            $plan = Infrastructure::plan();
            $analyticsLevel = valr('Addons.Analytics', $plan);
            if ($analyticsLevel) {
                saveToConfig('VanillaAnalytics.Level', $analyticsLevel, false);
            }
        }

        // Addons to show 'Contact Us' instead of 'Enable'
        $this->lockedPlugins = c('VFCom.Plugins.Locked', [
            'jsConnect',
            'Sphinx',
            'VanillaPop',
            'SalesForce'
        ]);

        // Addons to hide even when enabled
        $this->hiddenPlugins = c('VFCom.Plugins.Hidden', [
            'CustomTheme',
            'CustomDomain',
            'CustomizeText',
            'HtmLawed',
            'cloudfiles',
            'dynmonkey',
            'cloudmonkey',
            'riakmonkey',
            'queuemonkey',
            'elasticmonkey',
            'lithecompiler',
            'lithecustomizer',
            'litheextensions',
            'lithestyleguide',
            's3files',
            'Sphinx',
            'sitehub',
            'sitenode',
            'immunio',
            'vfshared',
            'vfcom',
            'vfdeploy',
            'vfcustom',
            'vfsupport',
            'vfsso',
            'vfspoof',
            'vfapi'
        ]);
    }

    /**
     * Add 'My Account' link to Dashboard.
     *
     * @param $sender
     */
    public function base_beforeUserOptionsMenu_handler($sender) {
        $url = 'https://accounts.vanillaforums.com/account';
        echo anchor('My Account', $url, 'MyAccountLink');
    }

    /**
     * Re-format dashboard menu items for customers
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = &$sender->EventArguments['SideMenu'];

        // Clean out options hosting customers should not see
        $menu->removeLink('Add-ons', t('Plugins'));
        $menu->removeLink('Add-ons', t('Applications'));
        $menu->removeLink('Site Settings', t('Routes'));
        $menu->removeLink('Forum', t('Statistics'));
        $menu->removeLink('Site Settings', t('Statistics'));

        $menu->addLink('Add-ons', t('Addons'), 'dashboard/settings/addons', 'Garden.Settings.Manage');

        // Remove import group
        $importLink = 'dashboard/import';
        if (property_exists($menu, 'Items') && array_key_exists('Import', $menu->Items)) {
            $importItems = &$menu->Items['Import'];
            $importLinks = &$importItems['Links'];

            // Remove group link
            $importItems['Url'] = false;

            // Remove importer link from Links if its in there
            if (array_key_exists($importLink, $importLinks)) {
                unset($importLinks[$importLink]);

                if (!count($importLinks)) {
                    $menu->removeGroup('Import');
                }
            }
        }

        Gdn::locale()->setTranslation('You can place files in your /uploads folder.', 'If your file is
   too large to upload directly to this page you can
   <a href="mailto:support@vanillaforums.com?subject=Importing+to+VanillaForums">contact us</a>
   to import your data for you.');
    }

    /**
     * Add the footer nav links.
     *
     * @param Gdn_Controller $sender
     */
    public function dashboard_footerNav_handler($sender, $args) {
        // If we're using the admin master view, make sure to add links to the footer for T's & C's
        $domain = c('Garden.Domain', '');
        $url = strpos($domain, 'vanilladev') > 0 ? 'vanilladev' : 'vanillaforums';
        $attrs = ['target' => '_blank', 'class' => 'footer-nav-item nav-item'];

        $footer = anchor('<strong>Customer Support Forum</strong>', 'https://support.vanillaforums.com', '', $attrs)
            . anchor('Terms of Service', 'http://' . $url . '.com/info/termsofservice', '', $attrs)
            . anchor('Privacy Policy', 'http://' . $url . '.com/info/privacy', '', $attrs)
            . anchor('Refund Policy', 'http://' . $url . '.com/info/refund', '', $attrs)
            . anchor('Contact', 'http://' . $url . '.com/info/contact', '', $attrs);

        echo $footer;

        $args['ShowVanillaVersion'] = false;
    }

    /**
     * If the domain in the config doesn't match that in the url, this will
     * redirect to the domain in the config. Also includes Google Analytics on
     * all pages if the conf file contains Plugins.GoogleAnalytics.TrackerCode
     * and Plugins.GoogleAnalytics.TrackerDomain.
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        // Translations
        Gdn::locale()->setTranslation('PluginHelp', "Plugins allow you to add functionality to your site.");
        Gdn::locale()->setTranslation('ApplicationHelp', "Applications allow you to add large groups of functionality to your site.");
        Gdn::locale()->setTranslation('ThemeHelp', "Themes allow you to change the look &amp; feel of your site.");
        Gdn::locale()->setTranslation('AddonProblems', '');

        // If we're using the admin master view, make sure to add links to the footer for T's & C's
        if ($sender->MasterView == 'admin') {
            $sender->addDefinition('DashboardReleasesFeed', false);
        }

        // Analytics
        $trackerCode = Gdn::config('Plugins.GoogleAnalytics.TrackerCode');
        $trackerDomain = Gdn::config('Plugins.GoogleAnalytics.TrackerDomain');
        $vanillaCode = 'UA-12713112-1';

        if ($trackerCode && $trackerCode != '' && $trackerCode != $vanillaCode && $sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $script = "<script type=\"text/javascript\">
var gaJsHost = ((\"https:\" == document.location.protocol) ? \"https://ssl.\" : \"http://www.\");
document.write(unescape(\"%3Cscript src='\" + gaJsHost + \"google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E\"));
</script>
<script type=\"text/javascript\">
try {
var pageTracker = _gat._getTracker(\"" . $trackerCode . "\");";
            if ($trackerDomain) {
                $script .= '
pageTracker._setDomainName("' . $trackerDomain . '");';
            }

            $script .= "
pageTracker._trackPageview();
} catch(err) {}</script>";

            $sender->addAsset('Content', $script);

        }
    }

    /**
     * Tweak the dashboard nav before rendering.
     *
     * This removes links that are not appropriate for hosted customers.
     *
     * @param DashboardNavModule $nav
     */
    public function dashboardNavModule_render_handler($nav) {
        $nav->removeItem("add-ons.plugins");
        $nav->removeItem("add-ons.applications");
        $nav->removeItem("site-settings.routes");
        $nav->removeItem("site-settings.statistics");
        $nav->removeItem("forum-data.import");
    }

    /**
     * Suspend this plugin for the rest of this session.
     */
    public function pluginController_suspendVFOptions_create($sender) {
        // Permission check
        $isSytemUser = (Gdn::session()->UserID == Gdn::userModel()->getSystemUserID());
        if (!checkPermission('Garden.Admin.Only') || !$isSytemUser) {
            return;
        }

        redirect('/dashboard/settings');
    }

    /**
     * Overrides Outgoing Email management screen.
     *
     * @access public
     */
    public function settingsController_email_create($sender, $args = array()) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('dashboard/settings/email');
        $sender->addJsFile('email.js');
        $sender->title(T('Outgoing Email'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'Garden.Email.SupportName',
            'Garden.Email.SupportAddress'
        ));

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            // Define some validation rules for the fields being saved
            $configurationModel->Validation->applyRule('Garden.Email.SupportName', 'Required');
            $configurationModel->Validation->applyRule('Garden.Email.SupportAddress', 'Required');
            $configurationModel->Validation->applyRule('Garden.Email.SupportAddress', 'Email');

            if ($sender->Form->save() !== false) {
                $sender->informMessage(t("Your settings have been saved."));
            }
        }

        $sender->render('Email', '', 'plugins/vfoptions');
    }

    /**
     * Don't let the users access the items under the "Add-ons" menu section of
     * the dashboard: applications & plugins (themes was moved to the "Appearance" section.
     *
     * @param Gdn_Controller $sender
     */
    public function settingsController_render_before($sender) {
        // Inform users if they're on a "secret" page.
        $viewingPlugins = (strcasecmp($sender->RequestMethod, 'plugins') == 0);
        $viewingApps = (strcasecmp($sender->RequestMethod, 'applications') == 0);

        if ($viewingPlugins || $viewingApps) {
            if ($this->hasInfPermission()) {
                $sender->informMessage('You can see this page because you have special permission.');
                return;
            } else {
                throw permissionException();
            }
        }

        // Theme pruning
        $themes = $sender->data('AvailableThemes');
        if (is_array($themes)) {

            $clientName = defined('CLIENT_NAME') ? CLIENT_NAME : '';
            $alwaysVisibleThemes = explode(',', strtolower(c('Garden.Themes.Visible', '')));

            // Remove any themes that are not available.
            $themes = $sender->data('AvailableThemes');
            $remove = [];
            foreach ($themes as $index => $theme) {

                // Check if theme visibility is explicitly set
                $hidden = val('Hidden', $theme, null);

                if (is_null($hidden)) { // If not set, then check for white listed sites
                    $hidden = true;

                    $sites = val('Sites', $theme, []);
                    $site  = val('Site', $theme, false);

                    if (!empty($site) && is_string($site)) {
                        array_push($sites, $site);
                    }

                    foreach ($sites as $s) {
                        if ($s === $clientName || fnmatch($s, $clientName)) {
                            $hidden = false;
                            break;
                        }
                    }
                }

                if ($hidden && !in_array(strtolower($index), $alwaysVisibleThemes)) {
                    $remove[] = $index;
                }
            }

            // Remove orphans
            foreach ($remove as $index) {
                unset($sender->Data['AvailableThemes'][$index]);
            }
        }
    }

    /**
     * No setup required.
     */
    public function setup() {

    }

    /**
     * Gets a url suitable to ping the statistics server.
     *
     * @param string $path
     * @param array $params
     * @return string
     */
    public static function statsUrl($path, $params = array()) {
        $analyticsServer = c('Garden.Analytics.Remote', 'http://analytics.vanillaforums.com');

        $path = '/' . trim($path, '/');

        $timestamp = time();
        $defaultParams = array(
            'vid' => Gdn::installationID(),
            't' => $timestamp,
            's' => md5($timestamp . Gdn::installationSecret())
        );

        $params = array_merge($defaultParams, $params);

        $result = $analyticsServer . $path . '?' . http_build_query($params);
        return $result;
    }

    /**
     *
     *
     * @param SettingsController $sender
     * @param array $args
     */
    public function settingsController_addons_create($sender, $args = array()) {
        $sender->title('Vanilla Addons');
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('dashboard/settings/addons');

        // Parameters
        $filter = val(0, $args);
        $action = strtolower(val(1, $args));
        $addonSlug = val(2, $args);

        // Filtering
        if (!in_array($filter, array('enabled', 'disabled'))) {
            $filter = 'all';
        }
        $sender->Filter = $filter;

        // Build available / enabled lists
        $availablePlugins = Gdn::pluginManager()->availablePlugins();
        $availableApps = Gdn::applicationManager()->availableApplications();
        $enabledPlugins = Gdn::pluginManager()->enabledPlugins();
        $enabledApps = Gdn::applicationManager()->enabledApplications();

        // Kludge on the Groups app
        // Apps are misnamed for some reason.
        $groupsApp = $availableApps['Groups'] ?? null;
        if ($groupsApp) {
            $availablePlugins['groups'] = $groupsApp;
        }

        $groupsApp = $enabledApps['Groups'] ?? null;
        if ($groupsApp) {
            $enabledPlugins['groups'] = $groupsApp;
        }

        // Determine plan's plugin availability
        $planPlugins = false;
        if (class_exists('Infrastructure')) {
            $plan = Infrastructure::plan();
            $planPlugins = valr('addons.Plugins', $plan, false);
        }

        if (!$planPlugins) {
            $planPlugins = c('VFCom.Plugins.Default', [
                "EmojiExtender",
                "Facebook",
                "editor",
                "GooglePlus",
                "Gravatar",
                "OpenID",
                "StopForumSpam",
                "Twitter",
                "vanillicon",
                "ProfileExtender",
                "AllViewed",
                "Disqus",
                "GooglePrettify",
                "IndexPhotos",
                "Participated",
                "PostCount",
                "PrivateCommunity",
                "QnA",
                "Quotes",
                "Reactions",
                "RoleTitle",
                "ShareThis",
                "Signatures",
                "Tagging",
                "groups"
            ]);
        } elseif (!in_array('groups', $planPlugins)) {
            $planPlugins[] = 'groups';
        }
        $allowedPlugins = array();
        foreach ($planPlugins as $key) {
            $info = $availablePlugins[$key] ?? null;
            if ($info) {
                $allowedPlugins[$key] = $info;
            }
        }

        // Exclude hidden, vf*, and *monkey from enabled plugins
        foreach ($enabledPlugins as $key => $name) {
            // Skip all vf* plugins
            if (in_array($key, $this->hiddenPlugins) || strpos($key, 'vf') === 0 || stringEndsWith($key, 'monkey', true)) {
                unset($enabledPlugins[$key]);
            }
        }

        // Show allowed + previously enabled
        $addons = array_merge($allowedPlugins, $enabledPlugins);

        // Filter & add conditional data to plugins
        foreach ($addons as $key => &$info) {
            // Enabled?
            $info['Enabled'] = $enabled = array_key_exists($key, $enabledPlugins);

            // Hide social connect plugins.
            if (val('SocialConnect', $info) === true) {
                unset($addons[$key]);
            }

            // Find icon
            if (!$iconUrl = val('IconUrl', $info)) {
                $iconPath = '/plugins/' . val('Folder', $info, '') . '/icon.png';
                $iconPath = file_exists(PATH_ROOT . $iconPath) ? $iconPath : 'applications/dashboard/design/images/plugin-icon.png';
                $iconPath = file_exists(PATH_ROOT . $iconPath) ? $iconPath : 'plugins/vfoptions/design/plugin-icon.png';
                $info['IconUrl'] = $iconPath;
            }

            // Toggle button
            if (!$enabled && in_array($key, $this->lockedPlugins)) {
                // Locked plugins need admin intervention to enable. Doesn't stop URL circumvention.
                $info['ToggleText'] = 'Contact Us';
                $info['ToggleUrl'] = '/settings/vanillasupport';
            } else {
                $info['ToggleText'] = $toggleText = $enabled ? 'Disable' : 'Enable';
                $info['ToggleUrl'] = "/settings/addons/" . $sender->Filter . "/" . strtolower($toggleText) . "/$key/" . Gdn::session()->transientKey();
            }
        }

        // Filter & add conditional data to plugins
        foreach ($addons as $key => &$info) {
            $isEnabled = array_key_exists($key, $enabledPlugins);
            $this->addData($key, $info, $isEnabled, $sender->Filter);
        }

        // Sort & set Addons
        uasort($addons, 'AddonSort');
        $sender->setData('Addons', $addons);

        // Get counts
        $PluginCount = 0;
        $EnabledCount = 0;
        foreach ($addons as $PluginKey => &$info) {
            if (val($PluginKey, $availablePlugins)) {
                $PluginCount++;
                if (array_key_exists($PluginKey, $enabledPlugins)) {
                    $EnabledCount++;
                    $info['Enabled'] = true;
                }
            }
        }

        $sender->setData('PluginCount', $PluginCount);
        $sender->setData('EnabledCount', $EnabledCount);
        $sender->setData('DisabledCount', $PluginCount - $EnabledCount);

        if ($action && $addonSlug && array_key_exists($addonSlug, $addons)) {
            if ($action === 'enable') {
                $this->settingsController_enableAddon_create($sender, [$addonSlug, $filter]);
            } elseif ($action === 'disable') {
                $this->settingsController_disableAddon_create($sender, [$addonSlug, $filter, array_key_exists($addonSlug, $allowedPlugins)]);
            }
        } else {
            $sender->render('Addons', '', 'plugins/vfoptions');
        }
    }

    public function addData($key, &$info, $isEnabled, $filter) {

        $isLocked = in_array($key, $this->lockedPlugins);

        // Enabled?
        $info['Enabled'] = $isEnabled;

        // Find icon
        if (!$iconUrl = val('IconUrl', $info)) {
            $iconPath = '/plugins/' . val('Folder', $info, '') . '/icon.png';
            $iconPath = file_exists(PATH_ROOT . $iconPath) ? $iconPath : 'applications/dashboard/design/images/addon-placeholder.png';
            $iconPath = file_exists(PATH_ROOT . $iconPath) ? $iconPath : 'plugins/vfoptions/design/addon-placeholder.png';
            $info['IconUrl'] = $iconPath;
        }

        // Toggle button
        if (!$isEnabled && $isLocked) {
            // Locked plugins need admin intervention to enable. Doesn't stop URL circumvention.
            $info['ToggleText'] = 'Contact Us';
            $info['ToggleUrl'] = '/settings/vanillasupport';
        } else {
            $info['ToggleText'] = $toggleText = $isEnabled ? 'Disable' : 'Enable';
            $info['ToggleUrl'] = "/settings/addons/" . $filter . "/" . strtolower($toggleText) . "/$key";
        }
    }

    public function settingsController_enableAddon_create($sender, $args) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $sender->permission('Garden.Settings.Manage');

        $addonName = $args[0];
        $filter = $args[1];

        if (!$filter) {
            $filter = 'all';
        }

        $action = 'none';
        if ($filter == 'disabled') {
            $action = 'SlideUp';
        }

        $addon = Gdn::addonManager()->lookupAddon($addonName);

        if (!$addon) {
            $sender->Form->addError(t('Addon does not exist.'));
        } else {
            $addonInfo = $addon->getInfo();
            $addonInfo['IconUrl'] = $addon->getIcon();
            try {
                /** @var AddonModel $addonModel */
                $addonModel = Gdn::getContainer()->get(AddonModel::class);
                $addonModel->enable($addon);
                $sender->informMessage(sprintf(t('%s Enabled.'), val('name', $addonInfo, t('Addon'))));

            } catch (Exception $ex) {
                $sender->Form->addError($ex);
            }
        }

        $this->handleAddonToggle($sender, $addonName, $addonInfo, true, $filter, $action);
        $sender->reloadPanelNavigation('Settings', '/dashboard/settings/addons');
        $sender->render('blank', 'utility', 'dashboard');
    }

    public function settingsController_disableAddon_create($sender, $args) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        $sender->permission('Garden.Settings.Manage');

        [$addonName, $filter, $isAllowed] = $args;

        if (!$filter) {
            $filter = 'all';
        }

        $action = 'none';
        if ($filter == 'enabled' || $isAllowed == false) {
            $action = 'SlideUp';
        }

        $addon = Gdn::addonManager()->lookupAddon($addonName);
        if (!$addon) {
            $sender->Form->addError(t('Addon does not exist.'));
        } else {
            $addonInfo = $addon->getInfo();
            $addonInfo['IconUrl'] = $addon->getIcon();
            try {
                /** @var AddonModel $addonModel */
                $addonModel = Gdn::getContainer()->get(AddonModel::class);
                $addonModel->disable($addon);
                $sender->informMessage(sprintf(t('%s Disabled.'), val('name', $addonInfo, t('Addon'))));

            } catch (Exception $ex) {
                $sender->Form->addError($ex);
            }
        }

        $this->handleAddonToggle($sender, $addonName, $addonInfo, false, $filter, $action);
        $sender->reloadPanelNavigation('Settings', '/dashboard/settings/addons');
        $sender->render('blank', 'utility', 'dashboard');
    }

    private function handleAddonToggle($sender, $addonName, $addonInfo, $isEnabled, $filter = '', $action = '') {

        $this->addData($addonName, $addonInfo, $isEnabled, $filter);

        require_once($sender->fetchViewLocation('helper_functions', '', 'plugins/vfoptions'));

        if ($sender->Form->errorCount() > 0) {
            $sender->informMessage($sender->Form->errors());
        } else {
            if ($action === 'SlideUp') {
                $sender->jsonTarget('#'.Gdn_Format::url(strtolower($addonName)).'-addon', '', 'SlideUp');
            } else {
                ob_start();
                writeAddonMediaItem($addonName, $addonInfo, $isEnabled);
                $row = ob_get_clean();
                $sender->jsonTarget('#'.Gdn_Format::url(strtolower($addonName)).'-addon', $row, 'ReplaceWith');
            }
        }
    }

    /**
     * Add additional data to the dashboard master view.
     *
     * @param Gdn_Controller $sender The controller for the page.
     */
    public function dashboard_render_handler($sender) {
        $sender->setData(
            'meList.account',
            ['text' => 'My Account', 'url' => 'https://accounts.vanillaforums.com/account']
        );
        $sender->setData(
            'meList.support',
            ['text' => 'Customer Support', 'url' => 'https://support.vanillaforums.com']
        );
    }
}

/**
 * Sorting function for plugin info array.
 */
function addonSort($pluginInfo, $pluginInfoCompare) {
    return strcmp(val('Name', $pluginInfo), val('Name', $pluginInfoCompare));
}
