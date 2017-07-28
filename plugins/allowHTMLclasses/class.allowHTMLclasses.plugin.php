<?php

/**
 * This plugin allows admins to create a whitelist of HTML classes in user input.
 *
 * Class AllowHTMLClassesPlugin
 */
class AllowHTMLClassesPlugin extends Gdn_Plugin {

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
            $trustedHTMLClasses = self::convertFilterList($trustedHTMLClasses);
            $form->setFormValue('Garden.TrustedHTMLClasses', $trustedHTMLClasses);

            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validBeginsWith', 'One or more class names provided begins with one of the following strings "'.c('TrustedHTMLClasses.RestrictedPrefixes', 'js-').'" which cannot be used.');
            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validEndsWith', 'One or more class names provided ends with one of the following strings "'.c('TrustedHTMLClasses.RestrictedSuffixes').'" which cannot be used.');
            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validContains', 'One or more class names provided contains one of the following strings "'.c('TrustedHTMLClasses.RestrictedContains').'" which cannot be used.');
            $form->validateRule('Garden.TrustedHTMLClasses', 'function:validNames', 'One or more class names provided is one of the following strings "'.c('TrustedHTMLClasses.RestrictedNames').'" which cannot be used.');
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
     * @param $trustedHTMLClasses
     * @return array|string
     */
    static function convertFilterList($trustedHTMLClasses) {
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

function validBeginsWith($data) {
    $trustedHTMLClasses = AllowHTMLClassesPlugin::convertFilterList($data);
    $trustedHTMLClasses = array_filter(explode("\n", $trustedHTMLClasses));
    $restrictedPrefix = array_filter(explode(',', c('TrustedHTMLClasses.RestrictedPrefixes', 'js-')));
    if (!$trustedHTMLClasses && ! $restrictedPrefix) {
        return true;
    }
    foreach($trustedHTMLClasses as $HTMLClass) {
        foreach($restrictedPrefix as $prefix) {
            $end = stringBeginsWith($HTMLClass, $prefix, true);
            if (stringBeginsWith($HTMLClass, $prefix, true)) {
                return false;
            }
        }
    }
    return true;
}


function validEndsWith($data) {
    $trustedHTMLClasses = AllowHTMLClassesPlugin::convertFilterList($data);
    $trustedHTMLClasses = array_filter(explode("\n", $trustedHTMLClasses));
    $restrictedSuffix = array_filter(explode(',', c('TrustedHTMLClasses.RestrictedSuffixes')));
    if (!$trustedHTMLClasses && ! $restrictedSuffix) {
        return true;
    }
    foreach($trustedHTMLClasses as $HTMLClass) {
        foreach($restrictedSuffix as $suffix) {
            $end = stringEndsWith($HTMLClass, $suffix, true);
            if (stringEndsWith($HTMLClass, $suffix, true)) {
                return false;
            }
        }
    }
    return true;
}


function validContains($data) {
    $trustedHTMLClasses = AllowHTMLClassesPlugin::convertFilterList($data);
    $trustedHTMLClasses = explode("\n", $trustedHTMLClasses);
    $restrictedString = explode(',', c('TrustedHTMLClasses.RestrictedContains'));
    foreach($trustedHTMLClasses as $HTMLClass) {
        foreach($restrictedString as $string) {
            if (strripos($HTMLClass, $string)) {
                return false;
            }
        }
    }
    return true;
}


function validNames($data) {
    $trustedHTMLClasses = AllowHTMLClassesPlugin::convertFilterList($data);
    $trustedHTMLClasses = explode("\n", $trustedHTMLClasses);
    $restrictedNames = explode(',', c('TrustedHTMLClasses.RestrictedNames', 'Hidden,Body'));
    foreach($trustedHTMLClasses as $HTMLClass) {
        foreach($restrictedNames as $name) {
            if (stripos($HTMLClass, $name) !== false) {
                return false;
            }
        }
    }
    return true;
}
