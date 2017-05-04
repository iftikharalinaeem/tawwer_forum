<?php

// 2.1 - Added a fix so that css files are in a more unique url so it works with autostatic (which is not forum-specific).
// 2.2 - Allow mobile style override.

$PluginInfo['CustomTheme'] = [
    'Name' => 'Custom Theme',
    'Description' => 'Allows administrators to customize the CSS & master HTML template of the currently enabled theme.',
    'Version' => '2.3.0',
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com',
    'SettingsUrl' => '/settings/customtheme',
    'Icon' => 'custom_theme.png',
    'UsePopupSettings' => false,
    'MobileFriendly' => true
];

/**
 * Class CustomThemePlugin
 */
class CustomThemePlugin extends Gdn_Plugin {

    /**
     *
     *
     * @param $key
     * @return mixed
     */
    public static function getRevisionID($key) {
        if (stringEndsWith($key, 'ID')) {
            $value = c("Plugins.CustomTheme.$key", '0');
        } else {
            $value = $key;
        }

        $parts = explode('_', $value, 2);
        return $parts[0];
    }

    /**
     *
     *
     * @param $revisionID
     * @param $time
     * @return string
     */
    public static function setRevisionID($revisionID, $time) {
        return $revisionID . '_' . str_replace([' ', ':'], '-', $time);
    }

    /**
     *
     *
     * @param $sender
     * @param $args
     */
    public function assetModel_generateETag_handler($sender, $args) {
        if (isMobile() && !c('Plugins.CustomTheme.OverrideMobile')) {
            return;
        }

        // We don't want to add the custom theme when previewing.
        $liveEditCSS = Gdn::session()->getPreference('LiveEditCSS', false);
        $doPreview = Gdn::session()->getPreference('PreviewCustomTheme', false) || $liveEditCSS;

        if ($doPreview) {
            $args['Suffix'] .= '-prv';
        }

        $liveRevisionID = c('Plugins.CustomTheme.LiveRevisionID', 0);
        $args['ETagData']["customtheme-$liveRevisionID"] = true;
    }

    /**
     *
     *
     * @param AssetModel $sender
     * @return type
     */
    public function assetModel_styleCss_handler($sender, $Args) {
        if (isMobile() && !c('Plugins.CustomTheme.OverrideMobile')) {
            return;
        }

        // We don't want to add the custom theme when previewing.
        $eTag = val('ETag', $Args);
        $doPreview = strpos($eTag, '-prv') !== false;

        if ($doPreview) {
            return;
        }

        $liveRevisionID = c('Plugins.CustomTheme.LiveRevisionID', 0);
        $revisionID = CustomThemePlugin::getRevisionFromFileName($liveRevisionID);

        $cacheKey = "customtheme.css[$revisionID]";
        $data = Gdn::cache()->get($cacheKey);

        if ($data === Gdn_Cache::CACHEOP_FAILURE) {
            $content = Gdn::sql()->reset()->getWhere('CustomThemeRevision', ['RevisionID' => $revisionID])->value('CSS');
            $serveFile = (bool)trim(preg_replace('#/\*.*?\*/#s', null, $content));

            $data = [
                'serveFile' => $serveFile,
                'content' => $content,
            ];

            Gdn::cache()->store($cacheKey, $data, [Gdn_Cache::FEATURE_EXPIRY => c('Plugins.CustomTheme.CSSCacheTime', 3600)]);
        }

        if ($data['serveFile']) {
            $host = Gdn::request()->host();
            $sender->addCssFile(false, "/plugin/customcss/{$host}/rev_{$liveRevisionID}.css", ['Css' => $data['content'], 'Sort' => 2000]);
        }
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        if (!$this->canCustomizeTheme()) {
            return;
        }

        $menu = &$sender->EventArguments['SideMenu'];
        $menu->addLink('Appearance', 'Customize Theme', 'settings/customtheme', 'Garden.Settings.Manage');
    }

    /**
     *
     *
     * @param $sender
     */
    public function base_render_before($sender) {
        if (isMobile() && !c('Plugins.CustomTheme.OverrideMobile')) {
            return;
        }

        // If we are in preview mode...
        if (Gdn::session()->getPreference('PreviewCustomTheme')) {
            // Add the css file that styles the preview inform message buttons
            $sender->addCssFile('previewtheme.css', 'plugins/CustomTheme');

            // Inform the user of the preview status
            $form = new Gdn_Form();
            $message = 'You are previewing your custom theme revisions.'
                .$form->open(['action' => url('settings/customtheme')]).'<div><strong>Options:</strong> ';

            if (c('Plugins.CustomTheme.Enabled')) {
                $message .= $form->button('Apply Changes', ['class' => 'PreviewThemeButton']);
            }
            $message .= $form->button('Exit Preview', ['class' => 'PreviewThemeButton']).'</div>'. $form->close();

            $sender->informMessage($message, ['CssClass' => 'NoDismiss', 'id' => 'InPreviewCustomTheme']);
        } else if (Gdn::session()->getPreference('LiveEditCSS')) {
            // Inform the user of the preview status
            $message = 'You are in "Edit CSS" mode: ' . anchor('Exit', 'settings/customtheme/exiteditcss', ['target' => '_top']);
            $sender->informMessage($message, ['CssClass' => 'NoDismiss InLiveEditCssMode', 'id' => 'InLiveEditCssMode']);
        }
    }

    /**
     * Can the current theme be customized?
     */
    private function canCustomizeTheme() {
        $themeInfo = Gdn::addonManager()->lookupTheme(self::getCurrentThemeKey());
        if (empty($themeInfo)) {
            return false;
        }
        // Make sure the current theme uses a smarty master template instead of php
        $themeRoot = PATH_THEMES . '/' . val('Folder', $themeInfo, '');
        return val('Index', $themeInfo) === 'default' || !file_exists($themeRoot . '/views/default.master.php');
    }

    /**
     * Get the root of the theme.
     *
     * @param string $subfolder The name of the subfolder, if any. (ex. views)
     * @return string Returns the theme root.
     */
    public static function getThemeRoot($subfolder = '') {
        if ($subfolder) {
            $path = paths(PATH_THEMES, self::getCurrentThemeKey(), ltrim($subfolder, '/'));
        } else {
            $path = paths(PATH_THEMES, self::getCurrentThemeKey());
        }

        return $path;
    }

    /**
     * Add the theme CSS customizations.
     */
    public function base_beforeAddCss_handler($sender) {
        if (isMobile() && !c('Plugins.CustomTheme.OverrideMobile')) {
            return;
        }

        // If we are using the default master view, and in preview mode, use custom css & html files
        $doPreview = Gdn::session()->getPreference('PreviewCustomTheme', false);
        $isDefaultMaster = $sender->MasterView == 'default' || $sender->MasterView == '';
        $workingRevisionID = c('Plugins.CustomTheme.WorkingRevisionID', 0);
        $liveRevisionID = c('Plugins.CustomTheme.LiveRevisionID', 0);

        if ($isDefaultMaster && $workingRevisionID == 0 && $liveRevisionID == 0) {
            // Fallbacks to old method
            $cssFiles = val('CssFiles', $sender->EventArguments);
            $previewCSSFile = c('Plugins.CustomTheme.PreviewCSS', '');
            if ($previewCSSFile != '' && $doPreview) {
                $cssFiles[] = ['FileName' => $previewCSSFile, 'AppFolder' => 'false'];
            } else {
                $cssFiles[] = ['FileName' => 'customtheme.css', 'AppFolder' => 'false'];
            }

            $sender->EventArguments['CssFiles'] = $cssFiles;
        }
    }

    /**
     * Gets the current theme's key.
     *
     * @return string
     */
    private static function getCurrentThemeKey() {
        return Gdn::themeManager()->getEnabledDesktopThemeKey();
    }

    /**
     * New method of adding css to page (from database). And handle changing the master view.
     *
     * @param $sender
     */
    public function base_beforeFetchMaster_handler($sender) {
        if (isMobile() && !c('Plugins.CustomTheme.OverrideMobile')) {
            return;
        }

        // If we are using the default master view, and in preview mode, use custom css & html files
        $liveEditCSS = Gdn::session()->getPreference('LiveEditCSS', false);
        $doPreview = Gdn::session()->getPreference('PreviewCustomTheme', false) || $liveEditCSS;

        $addCustomCss = arrayHasValue(Gdn::controller()->cssFiles(), 'style.css'); //
        $isDefaultMaster = $sender->MasterView == 'default' || $sender->MasterView == '';
        $isHead = property_exists($sender, 'Head') && is_object($sender->Head);

        $workingRevisionID = c('Plugins.CustomTheme.WorkingRevisionID', 0);
        $liveRevisionID = c('Plugins.CustomTheme.LiveRevisionID', 0);

        if ($isHead && $addCustomCss) {
            // New method
            if ($doPreview && $workingRevisionID > 0) {
                $sender->Head->addCss('/plugin/customcss/' . Gdn_Format::url(Gdn::request()->host()) . '/rev_' . $workingRevisionID . '.css', 'all');
            }
        }

        // Backwards compatibility
        $theme = self::getCurrentThemeKey();
        $previewHtml = c('Plugins.CustomTheme.PreviewHtml', '');
        $htmlFile = paths(PATH_THEMES, $theme, 'views', $previewHtml);
        if ($previewHtml == '' || !file_exists($htmlFile)) {
            $htmlFile = '';
        }

        if ($liveRevisionID > 0) {
            $htmlFile = 'customtheme:default_master_' . $liveRevisionID . '.tpl';
        }

        if ($doPreview && $workingRevisionID > 0) {
            $htmlFile = 'customtheme:default_master_' . $workingRevisionID . '.tpl';
        }

        if ($htmlFile != '' && $isDefaultMaster) {
            $sender->EventArguments['MasterViewPath'] = $htmlFile;
        }
    }

    /**
     * Hook smarty up with our custom template resource (functions at bottom of this file).
     *
     * @param Smarty $smarty Vanilla's instance of the Smarty object.
     */
    public function gdn_smarty_init_handler($smarty) {
        // Register the resource name "customtheme"
        $smarty->registerResource("customtheme", new Smarty_Resource_CustomTheme($smarty, self::getDefaultMasterView()));
    }

    /**
     *
     *
     * @param $fileName
     * @param int $default
     * @return int|string
     */
    public static function getRevisionFromFileName($fileName, $default = 0) {
        if ($fileName === false) {
            return $default;
        }

        // Note: the _css and _tpl is because PHP replaces url dots with underscores automatically.
        $revision = str_replace(['default_master_', 'rev_', 'custom_', '.css', '.tpl', '_css', '_tpl'], ['', '', '', '', '', '', ''], $fileName);
        $revision = self::getRevisionID($revision);
        return is_numeric($revision) ? $revision : $default;
    }

    /**
     * Renders the requested css from the db.
     *
     * @param $sender
     * @param $domain
     * @param $filename
     */
    public function pluginController_customCSS_create($sender, $domain, $filename) {
        header('Content-Type: text/css', true); // Force browsers to agree that this is css
        $sender->MasterView = 'none';
        $fileToLoad = $filename;
        $revisionID = CustomThemePlugin::getRevisionFromFileName($fileToLoad);

        $pluginEnabled = c('Plugins.CustomTheme.Enabled', false);
        $cssEnabled = $pluginEnabled;
        $themeData = false;

        if ($cssEnabled) {
            $themeData = Gdn::sql()
                    ->select()
                    ->from('CustomThemeRevision')
                    ->where('RevisionID', $revisionID)
                    ->get()
                    ->firstRow();
        }

        if ($themeData) {
            echo $themeData->CSS;
        }

        // Cleanup
        Gdn::database()->closeConnection();
    }

    /**
     * Write the button to customize the current theme.
     *
     * @param SettingsController $sender
     */
    public function settingsController_afterCurrentTheme_handler($sender, $args) {
        if (val('IsMobile', $args)) {
            return;
        }
        if ($this->canCustomizeTheme()) {
            echo wrap(sprintf(t('You can customize the HTML and CSS for this theme on the %s page.'),
                anchor('Customize Theme', 'settings/customtheme')), 'div', ['class' => 'CustomThemeOptions']);
        }
    }

    /**
     * Fetch content of default master
     * @return String
     */
    public function getDefaultMasterView() {
        $htmlContents = '';
        $themeKey = self::getCurrentThemeKey();
        $folder = paths(PATH_THEMES, $themeKey);
        $themeMasterView = paths($folder, 'views/default.master.tpl');
        if (file_exists($themeMasterView)) {
            $htmlContents = file_get_contents($themeMasterView);
        } else {
            $htmlContents = file_get_contents(PATH_APPLICATIONS . '/dashboard/views/default.master.tpl');
        }

        return $htmlContents;
    }



    /**
     * Settings page.
     *
     * @param Gdn_Controller $sender
     */
    public function settingsController_customTheme_create($sender) {
        $session = Gdn::session();
        $userModel = Gdn::userModel();
        $pluginEnabled = c('Plugins.CustomTheme.Enabled', false);
        $sender->permission('Garden.Settings.Manage');
        $sender->title('Customize Theme');
        $sender->addSideMenu('settings/customtheme');


        if (!$pluginEnabled) {
            $sender->render($sender->fetchViewLocation('disabled', '', 'plugins/CustomTheme'));
            return;
        }

        $sender->Form = new Gdn_Form();
        if ($sender->Form->getFormValue('Exit_Preview') ? true : false || val(0, $sender->RequestArgs, '1') == 'exiteditcss') {
            $userModel->savePreference($session->UserID, 'PreviewCustomTheme', false);
            $userModel->savePreference($session->UserID, 'LiveEditCSS', false);
            redirect('/settings/customtheme');
        }

        $sender->addJsFile('customtheme.js', 'plugins/CustomTheme');
        $sender->addJsFile('jquery.textarea.js', 'plugins/CustomTheme');
        $sender->addCssFile('customtheme.css', 'plugins/CustomTheme');

        // Get our folder, which must match our key.
        $themeKey = self::getCurrentThemeKey();
        $folder = paths(PATH_THEMES, $themeKey);

        // This is the new method:
        $liveRevisionID = self::getRevisionID('LiveRevisionID');
        $workingRevisionID = self::getRevisionID('WorkingRevisionID');

        // Are we switching back to a previous revision (css OR html)?
        if (!$sender->Form->authenticatedPostBack()) {
            $requestedRevisionID = val(2, $sender->RequestArgs);
            if (is_numeric($requestedRevisionID) && $requestedRevisionID >= 0) {
                $workingRevisionID = $requestedRevisionID;
            }
        }

        // Get customizations from db
        $themeData = Gdn::sql()
                ->select()
                ->from('CustomThemeRevision')
                ->where('RevisionID', $workingRevisionID)
                ->get()
                ->firstRow();



        if ($themeData) {
            $htmlContents = $themeData->Html;

            if (stringIsNullOrEmpty($htmlContents)) {
               $htmlContents = self::getDefaultMasterView();
            }

            $cssContents = $themeData->CSS;
            $label = $themeData->Label;
            $saveWorkingRevisionID = self::setRevisionID($workingRevisionID, $themeData->DateInserted);
        } else {
            $label = '';
            $cssContents = '/* ---- Edit CSS ----

If you are unfamiliar with CSS, there are some learning resources in the help
section on the right-hand side of the page.

Here are some things you should know before you begin:

1. Any definitions you enter here will be related to your custom theme. If you
   change to a different theme, these definitions will no longer be visible on
   this page. If you switch back to your custom theme, your changes will be
   present.

2. Your custom css definitions will be included *after* the theme
   css files. So, your definitions will take precedence over the theme ones.

3. When you preview your changes, you are the only one who can see the preview.

4. Feel free to delete these comments!

*/';
            $htmlContents = self::getDefaultMasterView();
        }

        // If viewing the form for the first time
        if (!$sender->Form->authenticatedPostBack()) {
            $sender->Form->setValue('CustomCSS', $cssContents);
            $sender->Form->setValue('CustomHtml', $htmlContents);
            $sender->Form->setValue('Label', $label);
        } else {
            $values = $sender->Form->formValues();

            // If saving the form
            $isApply = (isset($values['Apply'])) ? true : false;
            $isPreview = (isset($values['Preview'])) ? true : false;
            $isApplyPreview = $sender->Form->getFormValue('Apply_Changes') ? true : false;

            // If applying the changes from a preview
            if ($isApplyPreview) {
                $sender->Form->setFormValue('CustomCSS', $cssContents);
                $sender->Form->setFormValue('CustomHtml', $htmlContents);
            }

            // Save the changes (if there are changes to save):
            $newCSS = $sender->Form->getFormValue('CustomCSS', '');

            $newHtml = $sender->Form->getFormValue('CustomHtml', '');
            $defaultMasterHtml = self::getDefaultMasterView();

            // The js plugin changes the new line characters, we need to strip them
            if (preg_replace('/\r/', '', $newHtml) == $defaultMasterHtml) { // No use in saving if it matches default master
                $newHtml = '';
            }

            $newLabel = $sender->Form->getFormValue('Label', null);

            if ($cssContents != $newCSS || ($htmlContents != $newHtml)) {
                $set = [
                    'ThemeName' => self::getCurrentThemeKey(),
                    'Html' => $newHtml,
                    'CSS' => $newCSS,
                    'Label' => $newLabel,
                    'IncludeThemeCSS' => 'Yes', // $NewIncludeThemeCSS,
                    'InsertUserID' => $session->UserID,
                    'DateInserted' => Gdn_Format::toDateTime(),
                    'Live' => 2
                ];

                // Look for an existing working revision.
                $workingRow = Gdn::sql()->getWhere('CustomThemeRevision', ['ThemeName' => self::getCurrentThemeKey(), 'Live' => 2])->firstRow(DATASET_TYPE_ARRAY);

                if ($workingRow) {
                    $workingRevisionID = $workingRow['RevisionID'];
                    Gdn::sql()->put('CustomThemeRevision', $set, ['RevisionID' => $workingRevisionID]);
                } else {
                    $workingRevisionID = Gdn::sql()->insert('CustomThemeRevision', $set);
                }
                $saveWorkingRevisionID = self::setRevisionID($workingRevisionID, $set['DateInserted']);
                saveToConfig('Plugins.CustomTheme.WorkingRevisionID', $saveWorkingRevisionID);
            } elseif ($newLabel != $label && $workingRevisionID) {
                Gdn::sql()->put('CustomThemeRevision', ['Label' => $newLabel], ['RevisionID' => $workingRevisionID]);
            }

            // Check to see if there are any fatal errors in the smarty template

            // Check for required assets
            if (!stringIsNullOrEmpty($newHtml)) {
                $smarty = new Gdn_Smarty();
                $smartyCompileError = !$smarty->testTemplate("customtheme:default_master_{$workingRevisionID}.tpl");
                $noHeadAsset = (stripos($newHtml, '{asset name="Head"}') === false) && (stripos($newHtml, "{asset name='Head'}") === false);
                $noContentAsset = (stripos($newHtml, '{asset name="Content"}') === false) && (stripos($newHtml, "{asset name='Content'}") === false);
                $noFootAsset = (stripos($newHtml, '{asset name="Foot"}') === false) && (stripos($newHtml, "{asset name='Foot'}") === false);
                $noAfterEvent = (stripos($newHtml, '{event name="AfterBody"}') === false) && (stripos($newHtml, "{event name='AfterBody'}") === false);
                $assetError = $noHeadAsset || $noContentAsset || $noFootAsset || $noAfterEvent;
            } else { // If no changes were made to template, bypass error checking
                $assetError = false;
                $smartyCompileError = false;
            }

            // If we are applying the changes, and the changes didn't cause crashes save the live revision number.
            if (!$assetError && !$smartyCompileError && ($isApply || $isApplyPreview)) {
                $userModel->savePreference($session->UserID, 'PreviewCustomTheme', false);

                $liveRevisionID = $workingRevisionID;
                $saveLiveRevisionID = isset($saveWorkingRevisionID) ? $saveWorkingRevisionID : $workingRevisionID;

                saveToConfig('Plugins.CustomTheme.WorkingRevisionID', $saveLiveRevisionID);
                saveToConfig('Plugins.CustomTheme.LiveRevisionID', $saveLiveRevisionID);
                saveToConfig('Plugins.CustomTheme.LiveTime', time());

                // Update out old live revision row(s)
                Gdn::sql()->update('CustomThemeRevision')
                        ->set('Live', 0)
                        ->where('ThemeName', self::getCurrentThemeKey())
                        ->put();

                // Update new live revision row
                Gdn::sql()->update('CustomThemeRevision')
                        ->set('Live', 1)
                        ->where('RevisionID', $liveRevisionID)
                        ->put();
            }

            if ($isPreview && !$smartyCompileError && !$assetError) {
                $userModel->savePreference($session->UserID, 'PreviewCustomTheme', true);
                redirect('/');
            }

            if ($smartyCompileError) {
                $sender->Form->addError('There was a templating error in your HTML customizations. Make sure that any javascript or inline CSS definitions are wrapped in {literal} tags, and all {if} statements have a closing {/if} tag.');
            } elseif ($assetError) {
                $sender->Form->addError('There was a templating error in your HTML customizations. You have removed one or more required templating tags: {asset name="Head"}, {asset name="Content"}, {asset name="Foot"}, and {event name="AfterBody"}.');
            } else {
                $sender->InformMessage = "Your changes have been applied.";
            }
        }
        $sender->setData('LiveRevisionID', $liveRevisionID);

        // Load revision history
        $sender->setData('RevisionData', Gdn::sql()
                ->select()
                ->from('CustomThemeRevision')
                ->where('ThemeName', self::getCurrentThemeKey())
                ->orderBy('RevisionID', 'desc')
                ->limit(10)
                ->get()
        );

        // If nothing was saved, still load defautl master, so the page isn't empty
        if (stringIsNullOrEmpty($newHtml)) {
            $sender->Form->setFormValue('CustomHtml', $defaultMasterHtml);
        }

        $sender->render(paths(PATH_PLUGINS, 'CustomTheme/views/customtheme.php'));
    }

    /**
     * After a theme has been enabled, reset its related revisions based on what's in the db.
     */
    public function settingsController_afterEnableTheme_handler($sender) {
        $this->setRevisionsByTheme(self::getCurrentThemeKey());
    }

    /**
     * Look at what's in the database for the specified theme and set it as the working & live customisations.
     *
     * @param string $themeName
     */
    public function setRevisionsByTheme($themeName) {
        $live = Gdn::sql()->select()
                ->from('CustomThemeRevision')
                ->where('ThemeName', $themeName)
                ->where('Live', 1)
                ->orderBy('RevisionID', 'desc')
                ->limit(1, 0)
                ->get()
                ->firstRow(DATASET_TYPE_ARRAY);

        if ($live) {
            saveToConfig('Plugins.CustomTheme.LiveRevisionID', self::setRevisionID($live['RevisionID'], $live['DateInserted']));
            saveToConfig('Plugins.CustomTheme.LiveTime', time());
        } else {
            saveToConfig('Plugins.CustomTheme.LiveRevisionID', 0);
            saveToConfig('Plugins.CustomTheme.LiveTime', time());
        }

        $working = Gdn::sql()->select()
                ->from('CustomThemeRevision')
                ->where('ThemeName', $themeName)
                ->where('Live', 2)
                ->orderBy('RevisionID', 'desc')
                ->limit(1, 0)
                ->get()
                ->firstRow(DATASET_TYPE_ARRAY);

        if ($working) {
            saveToConfig('Plugins.CustomTheme.WorkingRevisionID', self::setRevisionID($working['RevisionID'], $working['DateInserted']));
        } else {
            saveToConfig('Plugins.CustomTheme.WorkingRevisionID', c('Plugins.CustomTheme.LiveRevisionID'));
        }
    }

    /**
     *
     *
     * @param $sender
     */
    public function settingsController_customThemeUpgrade_create($sender) {
        $sender->render(paths(PATH_PLUGINS, 'CustomTheme/views/upgrade.php'));
    }

    /**
     *
     *
     * @param Gdn_Controller $sender
     */
    public function settingsController_editCSS_create($sender) {
        // Set a session var so that the iframe knows we are in editcss mode
        Gdn::session()->setPreference('LiveEditCSS', true);

        $sender->permission('Garden.Settings.Manage');
        $sender->title('Edit CSS');
        $sender->MasterView = 'empty';

        $sender->clearCSSFiles();
        $sender->addDefinition('DoInform', '0'); // No automatic inform messages on this page.
        $sender->addCssFile('editcss.css', 'plugins/CustomTheme');

        $workingRevisionID = self::getRevisionID('WorkingRevisionID');
        $themeData = Gdn::sql()
                ->select()
                ->from('CustomThemeRevision')
                ->where('RevisionID', $workingRevisionID)
                ->get()
                ->firstRow();

        if (!$sender->Form->authenticatedPostBack()) {
            if ($themeData) {
                $sender->Form->setFormValue('CSS', val('CSS', $themeData));
            }
            $sender->render('editcss', '', 'plugins/CustomTheme');
        } else {
            $isApply = $sender->Form->getFormValue('Apply') ? true : false;
            $set = [
                'ThemeName' => self::getCurrentThemeKey(),
                'Html' => '',
                'CSS' => $sender->Form->getFormValue('CSS', ''),
                'Label' => '',
                'IncludeThemeCSS' => 'Yes', // $NewIncludeThemeCSS,
                'InsertUserID' => Gdn::session()->UserID,
                'DateInserted' => Gdn_Format::toDateTime(),
                'Live' => 2
            ];

            // Look for an existing working revision.
            $workingData = Gdn::sql()->getWhere('CustomThemeRevision', ['ThemeName' => self::getCurrentThemeKey(), 'Live' => 2])->firstRow(DATASET_TYPE_ARRAY);

            if ($workingData) {
                // If there is a working revision, update it.
                $set['Label'] = val('Label', $workingData, '');
                $set['Html'] = val('Html', $workingData, '');
                Gdn::sql()->put('CustomThemeRevision', $set, ['RevisionID' => $workingRevisionID]);
            } else {
                // If there isn't a working revision, create it.
                $currentThemeInfo = Gdn::addonManager()->getTheme()->getInfo();
                $currentThemeFolder = basename(val('ThemeRoot', $currentThemeInfo));
                $folder = PATH_THEMES . DS . $currentThemeFolder;
                $themeMasterView = paths($folder, 'views/default.master.tpl');
                if (file_exists($themeMasterView)) {
                    $set['Html'] = file_get_contents($themeMasterView);
                } else {
                    $set['Html'] = file_get_contents(PATH_APPLICATIONS . '/dashboard/views/default.master.tpl');
                }

                $workingRevisionID = Gdn::sql()->insert('CustomThemeRevision', $set);
            }

            $saveWorkingRevisionID = self::setRevisionID($workingRevisionID, $set['DateInserted']);
            saveToConfig('Plugins.CustomTheme.WorkingRevisionID', $saveWorkingRevisionID);
            $inform = 'Your changes have been saved.';

            // If we are applying the changes, save the live revision number.
            if ($isApply) {
                $liveRevisionID = $workingRevisionID;
                $saveLiveRevisionID = isset($saveWorkingRevisionID) ? $saveWorkingRevisionID : $workingRevisionID;
                saveToConfig('Plugins.CustomTheme.WorkingRevisionID', $saveLiveRevisionID);
                saveToConfig('Plugins.CustomTheme.LiveRevisionID', $saveLiveRevisionID);
                saveToConfig('Plugins.CustomTheme.LiveTime', time());

                // Update out old live revision row(s)
                Gdn::sql()->update('CustomThemeRevision')
                        ->set('Live', 0)
                        ->where('ThemeName', self::getCurrentThemeKey())
                        ->put();

                // Update new live revision row
                Gdn::sql()->update('CustomThemeRevision')
                        ->set('Live', 1)
                        ->where('RevisionID', $liveRevisionID)
                        ->put();

                $inform = 'Your changes have been applied.';
            }
            $sender->informMessage($inform);
            $sender->deliveryMethod(DELIVERY_METHOD_JSON);
            $sender->render('editcss', '', 'plugins/CustomTheme');
        }
    }

    /**
     *
     */
    public function setup() {
        $this->structure();
    }

    /**
     *
     *
     * @throws Exception
     */
    public function structure() {
        Gdn::structure()
                ->table('CustomThemeRevision')
                ->primaryKey('RevisionID')
                ->column('Label', 'varchar(50)', true)
                ->column('ThemeName', 'varchar(255)')
                ->column('Html', 'mediumtext', null)
                ->column('CSS', 'mediumtext', null)
                ->column('Live', 'int', 0)
                ->column('IncludeThemeCSS', ['Yes', 'No'], 'Yes')
                ->column('InsertUserID', 'int', true, 'key')
                ->column('DateInserted', 'datetime')
                ->set();

        // Check if old config is used and replace with new variables if set

        // Make sure the theme revision exists in the database.
        $revisionID = c('Plugins.CustomTheme.LiveRevisionID');
        if ($revisionID) {
            $row = Gdn::sql()->getWhere('CustomThemeRevision', ['RevisionID' => $revisionID])->firstRow();
            if (!$row) {
                removeFromConfig('Plugins.CustomTheme.LiveRevisionID');
            }
        }
    }
}

/**
 * CustomTheme Smarty Resource
 *
 * Allow Smarty to load CustomTheme templates from the database.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 */
class Smarty_Resource_CustomTheme extends Smarty_Resource_Custom {

    protected $smarty;
    protected $defaultMasterTemplate;

    public function __construct($smarty, $defaultMasterTemplate) {
        $this->smarty = $smarty;
        $this->defaultMasterTemplate = $defaultMasterTemplate;
    }

    /**
     * Fetch a template and its modification time from database
     *
     * @param string $name template name
     * @param string $source template source
     * @param integer $mtime template modification timestamp (epoch)
     * @return void
     */
    protected function fetch($name, &$source, &$mtime) {
        // Do database call here to fetch your template, populating $tpl_source with actual template contents.
        $revisionID = CustomThemePlugin::getRevisionFromFileName($name);

        $pluginEnabled = c('Plugins.CustomTheme.Enabled', false);
        $htmlEnabled = $pluginEnabled && !c('Plugins.CustomTheme.DisableHtml', false);

        if ($htmlEnabled) {
            $data = Gdn::sql()->select('Html,DateInserted')->from('CustomThemeRevision')->where('RevisionID', $revisionID)->get()->firstRow();

            if ($data) {
                if (stringIsNullOrEmpty($data->Html)) {
                    $data->Html = $this->defaultMasterTemplate;
                }

                $dir = CustomThemePlugin::getThemeRoot('/views');
                if ($dir) {
                    $this->smarty->template_dir = $dir;
                }

                $mtime = strtotime($data->DateInserted);
                if (!$mtime) {
                    $mtime = time();
                }
                $source = $data->Html;
                return true;
            }
        }
        return false;
    }

    /**
     * Fetch a template's modification time from config
     *
     * This is a shortcut. If LiveTime has not been set, this returns null and
     * will cause a fallback to fetch(), which will pull from the database if needed.
     *
     * @param string $name template name
     * @return integer timestamp (epoch) the template was modified
     */
    protected function fetchTimestamp($name) {
        $modTime = c('Plugins.CustomTheme.LiveTime');
        if (!$modTime || !($mtime = strtotime($modTime))) {
            return null;
        }
        return $mtime;
    }
}
