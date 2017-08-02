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

            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validBeginsWith', sprintf(t('One or more class names provided begins with one of the following strings "%s" which cannot be used.'), implode(',', self::$restrictedPrefixes)));
            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validEndsWith', sprintf(t('One or more class names provided ends with one of the following strings "%s" which cannot be used.'), implode(',', self::$restrictedSuffixes)));
            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validContains', sprintf(t('One or more class names provided contains one of the following strings "%s" which cannot be used.'), implode(',', self::$restrictedStrings)));
            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validNames', sprintf(t('One or more class names provided is one of the following strings "%s" which cannot be used.'), implode(',', self::$restrictedNames)));
            // Save new settings
            $saved = $form->save();
            if ($saved !== false) {
                $sender->informMessage(t("Your changes have been saved."));
            }
        }

        $sender->addJsFile('settings.js', 'plugins/allowhtmlclasses');
        $sender->setHighlightRoute('vanilla/settings/allowhtmlclasses');
        $sender->title(t('Trusted HTML Classes'));
        $sender->render('settings', '', 'plugins/allowhtmlclasses');
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
     * @param Container $container
     */
    public function container_init_handler($container) {
        if (c('Garden.AllowTrustedClasses')) {
            $trustedHTMLClasses = explode("\n", c('Garden.TrustedHTMLClasses'));
            $container->get(VanillaHtmlFormatter::class)->addExtraAllowedClasses($trustedHTMLClasses);
        }
    }
}

//------------------- Validation Functions ---------------------------------//

if (!function_exists('validBeginsWith')) {

    /**
     * Optionally do not allow any HTML classes that have a particular prefix.
     *
     * @param string $data The list of HTML classes to be allowed.
     * @return bool
     */
    function validBeginsWith($data) {
        $trustedHTMLClasses = AllowHTMLClassesPlugin::filterClassList($data);
        $trustedHTMLClasses = array_filter(explode("\n", $trustedHTMLClasses));
        $restrictedPrefix = AllowHTMLClassesPlugin::$restrictedPrefixes;
        if (!$trustedHTMLClasses && ! $restrictedPrefix) {
            return true;
        }
        foreach($trustedHTMLClasses as $HTMLClass) {
            foreach($restrictedPrefix as $prefix) {
                if (stringBeginsWith($HTMLClass, trim($prefix), true)) {
                    return false;
                }
            }
        }
        return true;
    }
}

if (!function_exists('validEndsWith')) {

    /**
     * Optionally do not allow any HTML classes that have a particular ending.
     *
     * @param string $data The list of HTML classes to be allowed.
     * @return bool
     */
    function validEndsWith($data) {
        $trustedHTMLClasses = AllowHTMLClassesPlugin::filterClassList($data);
        $trustedHTMLClasses = array_filter(explode("\n", $trustedHTMLClasses));
        $restrictedSuffixes = AllowHTMLClassesPlugin::$restrictedSuffixes;
        if (!$trustedHTMLClasses && ! $restrictedSuffixes) {
            return true;
        }
        foreach($trustedHTMLClasses as $HTMLClass) {
            foreach($restrictedSuffixes as $suffix) {
                if (stringEndsWith($HTMLClass, trim($suffix), true)) {
                    return false;
                }
            }
        }
        return true;
    }
}

if (!function_exists('validContains')) {

    /**
     * Optionally do not allow any HTML classes contain a particular string.
     *
     * @param string $data The list of HTML classes to be allowed.
     * @return bool
     */
    function validContains($data) {
        $trustedHTMLClasses = AllowHTMLClassesPlugin::filterClassList($data);
        $trustedHTMLClasses = explode("\n", $trustedHTMLClasses);
        $restrictedString = AllowHTMLClassesPlugin::$restrictedStrings;
        foreach($trustedHTMLClasses as $HTMLClass) {
            foreach($restrictedString as $string) {
                if (strripos($HTMLClass, trim($string)) !== false) {
                    return false;
                }
            }
        }
        return true;
    }
}

if (!function_exists('validNames')) {

    /**
     * Optionally do not allow particular class names.
     *
     * @param string $data The list of HTML classes to be allowed.
     * @return bool
     */
    function validNames($data) {
        $trustedHTMLClasses = AllowHTMLClassesPlugin::filterClassList($data);
        $trustedHTMLClasses = explode("\n", $trustedHTMLClasses);
        $restrictedNames = AllowHTMLClassesPlugin::$restrictedNames;
        foreach($trustedHTMLClasses as $HTMLClass) {
            foreach($restrictedNames as $name) {
                if (strcasecmp($HTMLClass, trim($name)) === 0) {
                    return false;
                }
            }
        }
        return true;
    }
}
