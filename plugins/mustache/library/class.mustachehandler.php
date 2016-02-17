<?php

/**
 * Mustache abstraction layer
 *
 * Vanilla PHP wrapper for Mustache templating engine.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 * @package internal
 * @subpackage Mustache
 * @since 2.0
 */
class MustacheHandler {

    /**
     * The Mustache Engine renderer
     * @var Mustache_Engine
     */
    protected $engine = null;

    /**
     * Get Mustache Engine object
     *
     * @return Mustache_Engine
     */
    public function engine() {
        // Create engine if it is not yet created
        if (is_null($this->engine)) {
            // Prepare mustache engine
            $this->engine = new Mustache_Engine([
                'cache' => paths(PATH_CACHE, 'mustache'),
                'escape' => function($value) {
                    return $value;
                }
            ]);
            Gdn::pluginManager()->callEventHandlers($this->engine, 'MustacheHandler', 'init');
        }
        return $this->engine;
    }

    /**
     * Prepare mustache to render from a certain folder
     *
     * @param string $path path to view
     * @param Gdn_Controller $controller controller off which view is being rendered
     */
    public function init($path, $controller) {
        $this->engine();

        // Get a friendly name for the controller.
        $controllerName = get_class($controller);
        $shortName = stringEndsWith($controllerName, 'Controller', true, true);
        if ($shortName) {
            $controllerName = $shortName;
        }

        // Prepare for template
        $viewDir = dirname($path);
        $viewLoader = new Mustache_Loader_FilesystemLoader($viewDir, [
            'extension' => 'mustache'
        ]);
        $this->engine()->setLoader($viewLoader);

        $partialsLoader = new Mustache_Loader_CascadingLoader();

        // Prepare for method partials
        $partialsDir = paths(dirname($path), '_partials');
        if (is_dir($partialsDir)) {
            $localPartialsLoader = new Mustache_Loader_FilesystemLoader($partialsDir, [
                'extension' => 'mustache'
            ]);
            $partialsLoader->addLoader($localPartialsLoader);
        }

        // Prepare for global partials
        $viewsDir = dirname($viewDir);
        $globalPartialsDir = paths($viewsDir, '_partials');
        if (is_dir($globalPartialsDir)) {
            $globalPartialsLoader = new Mustache_Loader_FilesystemLoader($globalPartialsDir, [
                'extension' => 'mustache'
            ]);
            $partialsLoader->addLoader($globalPartialsLoader);
        }

        $this->engine()->setPartialsLoader($partialsLoader);

        $virtualData = [];

        // Get an ID for the body.
        $bodyIdentifier = strtolower($controller->ApplicationFolder . '_' . $controllerName . '_' . Gdn_Format::AlphaNumeric(strtolower($controller->RequestMethod)));
        $virtualData['BodyID'] = $bodyIdentifier;

        // Assign some information about the user.
        if (Gdn::session()->isValid()) {
            $user = [
                'Name' => Gdn::session()->User->Name,
                'Photo' => '',
                'CountNotifications' => (int)val('CountNotifications', Gdn::session()->User, 0),
                'CountUnreadConversations' => (int)val('CountUnreadConversations', Gdn::session()->User, 0),
                'SignedIn' => TRUE
            ];

            $photo = Gdn::session()->User->Photo;
            if ($photo) {
                if (!isUrl($photo)) {
                    $photo = Gdn_Upload::url(changeBasename($photo, 'n%s'));
                }
            } else {
                if (function_exists('UserPhotoDefaultUrl')) {
                    $photo = userPhotoDefaultUrl(Gdn::session()->User, 'ProfilePhoto');
                } elseif ($configPhoto = C('Garden.DefaultAvatar')) {
                    $photo = Gdn_Upload::url($configPhoto);
                } else {
                    $photo = asset('/applications/dashboard/design/images/defaulticon.png', true);
                }
            }
            $user['Photo'] = $photo;
        } else {
            $user = false;
        }
        $virtualData['User'] = $user;

        // Make sure that any datasets use arrays instead of objects.
        foreach ($controller->Data as $Key => $value) {
            if ($value instanceof Gdn_DataSet) {
                $controller->Data[$Key] = $value->resultArray();
            } elseif ($value instanceof stdClass) {
                $controller->Data[$Key] = (array)$value;
            }
        }

        $bodyClass = val('CssClass', $controller->Data, '', true);
        $sections = Gdn_Theme::section(null, 'get');
        if (is_array($sections)) {
            foreach ($sections as $section) {
                $bodyClass .= ' Section-' . $section;
            }
        }

        $controller->Data['BodyClass'] = $bodyClass;

        // Set the current locale for themes to take advantage of.
        $locale = Gdn::locale()->Locale;

        // Kludge en-CA into just en until we can make our default local en.
        if ($locale === 'en-CA') {
            $locale = 'en';
        }
        $currentLocale = [
            'Key' => $locale,
            'Lang' => str_replace('_', '-', $locale) // mirrors html5 lang attribute
        ];
        if (class_exists('Locale')) {
            $currentLocale['Language'] = Locale::getPrimaryLanguage($locale);
            $currentLocale['Region'] = Locale::getRegion($locale);
            $currentLocale['DisplayName'] = Locale::getDisplayName($locale, $locale);
            $currentLocale['DisplayLanguage'] = Locale::getDisplayLanguage($locale, $locale);
            $currentLocale['DisplayRegion'] = Locale::getDisplayRegion($locale, $locale);
        }
        $virtualData['CurrentLocale'] = $currentLocale;

        $virtualData['Assets'] = (array)$controller->Assets;
        $virtualData['Path'] = Gdn::request()->path();

        // Prepare breadcrumbs
        $controller->Data['crumbs'] = function() use (&$controller) {
            $breadcrumbs = $controller->Data['breadcrumbs'];
            if (!is_array($breadcrumbs)) {
                $breadcrumbs = [];
            }

            $options = ['homeurl' => 'HomeUrl', 'hidelast' => 'HideLast'];
            return Gdn_Theme::breadcrumbs($breadcrumbs, true, $options);
        };

        $controller->Data = array_change_key_case(array_merge($controller->Data, $virtualData), CASE_LOWER);

        // Keep a UCFirst copy for 'title' and 'breadcrumbs' since the master template uses them.
        foreach (['title', 'breadcrumbs'] as $key) {
            $controller->setData(ucfirst($key), $controller->data($key, []));
        }
    }

    /**
     * Render the given view.
     *
     * @param string $path The path to the view's file.
     * @param Gdn_Controller $controller The controller that is rendering the view.
     */
    public function render($path, $controller) {
        $this->init($path, $controller);

        $templateName = basename($path);
        echo $this->engine()->render($templateName, $controller->Data);
    }

    /**
     * Render and return the given view.
     *
     * @param string $path The path to the view's file.
     * @param Gdn_Controller $controller The controller that is rendering the view.
     * @return string
     */
    public function get($path, $controller) {
        $this->init($path, $controller);

        $templateName = basename($path);
        return $this->engine()->render($templateName, $controller->Data);
    }

    /**
     * Test provided template
     *
     * @param type $path Path of template file to test.
     * @return boolean true if template loads successfully.
     */
    public function testTemplate($path) {
        $this->init($path, Gdn::controller());

        $return = true;
        try {
            $result = $this->engine()->render($path, []);
            $return = ($result == '' || strpos($result, '<title>Fatal Error</title>') > 0 || strpos($result, '<h1>Something has gone wrong.</h1>') > 0) ? false : true;
        } catch (Exception $ex) {
            $return = false;
        }
        return $return;
    }

}
