<?php

/**
 * Localligator Plugin
 *
 * @author    Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2016 (c) Becky Van Bussel
 * @license   MIT
 * @since     1.0.0
 */
class LocalligatorPlugin extends Gdn_Plugin {

    /**
     * The translation codes we've encountered that don't exist in site_core, dash_core, or the ignore file.
     *
     * @var Gdn_Configuration
     */
    private $untranslatedList = null;

    /** @var array All the translated strings in Vanilla. */
    private $translations = [];

    /** @var array All the translated strings in Vanilla. */
    private $translationFiles = [
        'Vanilla' => PATH_ROOT.'/locales/tx-source/site_core.php',
        'Dashboard' => PATH_ROOT.'/locales/tx-source/dash_core.php',
        'Ignore' => PATH_ROOT."/conf/localligator/ignore.php"
    ];

    /** @var boolean var Whether the tx-source translations are reachable. */
    private static $canLoadResources = false;

    private static $informUser = true;

    /** @var Storage of untranslated codes */
    const UNTRANSLATED_FILE_NAME = PATH_ROOT."/conf/localligator/untranslated.php";

    /** @var Gitignore path */
    const GIT_IGNORE_PATH = PATH_ROOT."/conf/localligator/.gitignore";

    /**
     * Fetch the site core translation strings.
     *
     * @return array The site core translations.
     */
    public function getTranslations() {
        if (empty($this->translations)) {
            $this->loadSiteCore();
        }
        return $this->translations;
    }

    /**
     * Load the site core translations and translations to ignore into an array.
     */
    private function loadSiteCore() {
        $Definition = [];
        foreach($this->translationFiles as $key => $file) {
            if (file_exists($file)) {
                if ($key === 'Vanilla') {
                    self::$canLoadResources = true;
                }
                include_once($file);
            }
        }
        $this->translations = $Definition;
    }

    /**
     * Returns the Configuration object that represents the missing definitions.
     *
     * @return Gdn_Configuration|null Configuration or null if there's an issue creating the untranslated file.
     */
    public function getUntranslatedList() {
        if ($this->untranslatedList === null) {
            $this->loadUntranslatedList();
        }
        return $this->untranslatedList;
    }

    /**
     * Loads (and possibily creates) our untranslated list in the form of a configuration object.
     */
    private function loadUntranslatedList() {
        $this->touchNestedFile(self::UNTRANSLATED_FILE_NAME);
        $this->addGitIgnore();
        $this->untranslatedList = $this->getConfig(self::UNTRANSLATED_FILE_NAME);
    }

    /**
     * If it doesn't exist, adds a gitignore to our localligator directory.
     */
    private function addGitIgnore() {
        if (!file_exists(self::GIT_IGNORE_PATH)) {
            $this->touchNestedFile(self::GIT_IGNORE_PATH);
            file_put_contents(self::GIT_IGNORE_PATH, '/.*');
        }
    }

    /**
     * Checks if a translation code is contained in site core and if not, adds it to the untranlated list
     * and informs the user.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_beforeTranslate_handler($sender, $args) {
        if (!Gdn_Statistics::checkIsLocalhost()) {
            trace('Localligator should only be run locally.');
            return;
        }

        $code = val('Code', $args);
        $default = val('Default', $args);

        if (!$code) {
            return;
        }

        $translations = $this->getTranslations();
        if (!array_key_exists($code, $translations) && self::$canLoadResources) {
            trace('Translation does not exist: '.$code);
            $exists = $this->getUntranslatedList()->get($code, false);

            if ($exists === false) {
                $this->getUntranslatedList()->saveToConfig($code, $default);

                if (self::$informUser) {
                    $anchor = anchor(t('Take me there!'), '/settings/localligator');
                    if (Gdn::controller() !== null) {
                        Gdn::controller()->informMessage(t('CHOMP! Adding strings to localligator.').' '.$anchor);
                    }
                }
                self::$informUser = false;
            }
        }
    }

    /**
     * Add Localligator to the nav.
     *
     * @param DashboardNavModule $nav
     */
    public function dashboardNavModule_init_handler($nav) {
        $nav->addLinkIf('Garden.Settings.Manage', 'Localligator', '/settings/localligator', 'forum-data.localligator');
    }

    /**
     * If it doesn't exist, creates a file given a path.
     *
     * @param string $path The absolute path to the file to create.
     */
    private function touchNestedFile($path) {
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        touch($path);
    }

    /**
     * Creates a locale-style Configuration object from a path.
     *
     * @param string $path The file to create a locale-style Configuration object from.
     * @return Gdn_Configuration|null The Configuration object, or null if the file doesn't exist.
     */
    private function getConfig($path) {
        $config = new Gdn_Configuration();
        $config->setFormatOption('SafePHP', false);
        $config->splitting(false);
        $config->caching(false);
        $config->setSortFlag(SORT_STRING|SORT_FLAG_CASE);

        if (file_exists($path)) {
            $config->load($path, 'Definition', true);
            return $config;
        }
        return null;
    }

    /**
     * Creates the localligator settings page. On save, saves the checked 'strings to add' fields to their designated
     * location, either site_core, dash_core or ignore.
     *
     * @param SettingsController $sender
     */
    public function settingsController_localligator_create($sender) {
        $this->getTranslations(); // Just in case we haven't tried loading our source yet...
        $sender->setData('CanLoadResources', self::$canLoadResources);
        if (!self::$canLoadResources) {
            $sender->render('localligator', '', 'plugins/localligator');
            return;
        }

        $sender->form = new Gdn_Form('', 'bootstrap');
        $localeStrings = val('Data', $this->getUntranslatedList());

        // Prepare our fields for something the Form object and/or our view expects.
        $applicationField = [];
        foreach ($this->translationFiles as $key => $coreFile) {
            $applicationField[$key] = [
                'Text' => $key,
                'value' => $key,
            ];
        }

        $localeField = [];
        foreach($localeStrings as $key => &$value) {
            $localeField[$key] = [
                'value' => $key,
                'class' => 'js-check-me',
                'title' => $key,
                'description' => $value
            ];
        }

        // Handle form post back.
        if ($sender->form->authenticatedPostBack() !== false) {
            $application = val('Application', $sender->form->formValues());
            $path = $this->translationFiles[$application];

            // Make sure we have a place to store our ignored translation codes.
            if ($application === 'Ignore') {
                $this->touchNestedFile($path);
                $this->addGitIgnore();
            }

            $translationsList = $this->getConfig($path);
            if ($translationsList === null) {
                $sender->form->addError('Cannot locate locale file.');
            }

            if ($sender->form->errorCount() == 0) {
                foreach(val('StringsToAdd', $sender->form->formValues()) as $code) {
                    $translationsList->saveToConfig($code, val($code, $localeStrings), ['SafePHP' => false]);
                    unset($localeField[$code]);
                    $this->getUntranslatedList()->removeFromConfig($code);
                }
                if ($application === 'Ignore') {
                    $sender->informMessage(t('Ignored.'));
                } else {
                    $sender->informMessage(t('Locale source files updated.').' '.t('Don\'t forget to make a pull request!'));
                }
            }
        }

        $sender->setData('ApplicationField', $applicationField);
        $sender->setData('StringsToAddField', $localeField);
        $sender->render('localligator', '', 'plugins/localligator');
    }
}