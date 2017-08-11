<?php

/**
 * This plugin allows admins to create a whitelist of HTML classes in user input.
 *
 * Class AllowHTMLClassesPlugin
 */
class AllowHTMLClassesPlugin extends Gdn_Plugin {

    /** @var array Hard-coded HTML class prefixes we do not want to allow users to include in input */
    public static $restrictedPrefixes = ['js-', 'twitter-', 'tweet-'];

    /** @var array Hard-coded HTML class suffixes we do not want to allow users to include in input */
    public static $restrictedSuffixes = [];

    /** @var array Hard-coded strings we do not want to allow users to include as part of HTML classes in input */
    public static $restrictedStrings = [];

    /** @var array Hard-coded restricted HTML class names we do not wantto allow users to include in input */
    public static $restrictedNames = ['Popup', 'AjaxForm', 'BodyBox', 'mfp-image', 'Video', 'YouTube', 'VideoPreview'];

    /**
     * Constructor function that merges any hard-coded restrictions we have with ones that we can put in the config.
     */
    public function __construct() {
        self::$restrictedPrefixes = array_merge(c('TrustedHTMLClasses.RestrictedPrefixes'), self::$restrictedPrefixes);
        self::$restrictedSuffixes = array_merge(c('TrustedHTMLClasses.RestrictedSuffixes'), self::$restrictedSuffixes);
        self::$restrictedStrings = array_merge(c('TrustedHTMLClasses.RestrictedStrings'), self::$restrictedStrings);
        self::$restrictedNames = array_merge(c('TrustedHTMLClasses.RestrictedNames'), self::$restrictedNames);
    }

    /**
     * Create a page in the dashboard to configure a white-list of trusted HTML classes.
     *
     * @param SettingsController $sender
     * @param array $handler
     */
    public function settingsController_allowHTMLClasses_create($sender, $handler) {
        // Check permission
        $sender->permission('Garden.Settings.Manage');

        $form = $sender->Form;

        // Load up config options we'll be setting
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(['Garden.TrustedHTMLClasses', 'Garden.AllowTrustedClasses']);

        // Set the model on the form.
        $form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($form->authenticatedPostBack() === false) {
            // Format trusted domains as a string
            $trustedHTMLClasses = val('Garden.TrustedHTMLClasses', $configurationModel->Data);
            if (is_array($trustedHTMLClasses)) {
                $trustedHTMLClasses = implode("\n", $trustedHTMLClasses);
                $form->setFormValue('Garden.TrustedHTMLClasses', $trustedHTMLClasses);
            }

            // Apply the config settings to the form.
            $form->setData($configurationModel->Data);
        } else {
            // Format the entrusted HTML classes as an array based on newlines & spaces
            $trustedHTMLClasses = $form->getValue('Garden.TrustedHTMLClasses');
            $trustedHTMLClasses = self::filterClassList($trustedHTMLClasses);
            $form->setFormValue('Garden.TrustedHTMLClasses', $trustedHTMLClasses);

            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validateHtmlClassName');

            // Save new settings
            $saved = $form->save();
            if ($saved !== false) {
                $sender->informMessage(t("Your changes have been saved."));
            }
        }

        $sender->addJsFile('settings.js', 'plugins/allowHTMLclasses');
        $sender->setHighlightRoute('vanilla/settings/allowHTMLclasses');
        $sender->title(t('Trusted HTML Classes'));
        $sender->render('settings', '', 'plugins/allowHTMLclasses');
    }


    /**
     * Make sure there are no blanks or duplicates in the list.
     *
     * @param string $trustedHTMLClasses
     * @return array|string
     */
    static function filterClassList($trustedHTMLClasses) {
        $trustedHTMLClasses = explodeTrim("\n", $trustedHTMLClasses);
        $trustedHTMLClasses = array_unique($trustedHTMLClasses);
        $trustedHTMLClasses = implode("\n", $trustedHTMLClasses);
        return $trustedHTMLClasses;
    }

    /**
     * Add trusted HTML classes from the config to VanillaHtmlFormatter.
     *
     * @param Container $container
     */
    public function container_init_handler($container) {
        if (c('Garden.AllowTrustedClasses')) {
            $trustedHTMLClasses = explode("\n", c('Garden.TrustedHTMLClasses'));
            // Get an instance of VanillaHtmlFormatter and add extra allowed classes.
            $container->get(VanillaHtmlFormatter::class)->addExtraAllowedClasses($trustedHTMLClasses);
        }
    }
}

//------------------- Validation Functions ---------------------------------//

if (!function_exists('validateHtmlClassName')) {
    /**
     * In dashboard, validate the class names the admin is trying to whitelist.
     * We can config certain prefixes, suffixes, string or names so that they cannot be whitelisted.
     *
     * @param $data The class names submitted in the form.
     * @return bool|string
     */
    function validateHtmlClassName($data) {
        $trustedHTMLClasses = AllowHTMLClassesPlugin::filterClassList($data);
        $trustedHTMLClasses = array_filter(explode("\n", $trustedHTMLClasses));
        $restrictedPrefix = AllowHTMLClassesPlugin::$restrictedPrefixes;
        $restrictedSuffixes = AllowHTMLClassesPlugin::$restrictedSuffixes;
        $restrictedString = AllowHTMLClassesPlugin::$restrictedStrings;
        $restrictedNames = AllowHTMLClassesPlugin::$restrictedNames;
        if (!$trustedHTMLClasses && !$restrictedPrefix) {
            return true;
        }
        foreach ($trustedHTMLClasses as $HTMLClass) {
            foreach ($restrictedPrefix as $prefix) {
                if (stringBeginsWith($HTMLClass, trim($prefix), true)) {
                    return sprintf(t('Classes cannot begin with "%s".'), $prefix);
                }
            }
            foreach ($restrictedSuffixes as $suffix) {
                if (stringEndsWith($HTMLClass, trim($suffix), true)) {
                    return sprintf(t('Classes cannot end with "%s".'), $suffix);
                }
            }
            foreach ($restrictedString as $string) {
                if (strripos($HTMLClass, trim($string)) !== false) {
                    return sprintf(t('Classes cannot contain "%s".'), $string);
                }
            }
            foreach ($restrictedNames as $name) {
                if (strcasecmp($HTMLClass, trim($name)) === 0) {
                    return sprintf(t('Class name cannot be "%s".'), $name);
                }
            }
        }
        return true;
    }
}
