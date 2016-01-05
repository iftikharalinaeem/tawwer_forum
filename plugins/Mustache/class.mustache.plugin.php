<?php

/**
 * @copyright 2010-2014 Vanilla Forums Inc
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['Mustache'] = array(
    'Name' => 'Mustache view renderer',
    'Description' => "This plugin adds Mustache rendering ability to Vanilla.",
    'Version' => '1.0a',
    'MobileFriendly' => true,
    'RequiredApplications' => false,
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'HasLocale' => false,
    'RegisterPermissions' => false,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Mustache Renderer
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package internal
 * @subpackage Mustache
 * @since 1.0
 */
class MustachePlugin extends Gdn_Plugin {
    /**
     *
     * @var array   An array of templates
     */
    public $templates = [];

    /**
     * Add mustache view handler
     *
     * @param Gdn_Dispatcher $sender
     */
    public function gdn_dispatcher_appStartup_handler($sender) {
        // Mustache Templating Engine and Handler
        Gdn::factoryInstall('Mustache_Engine', $this->getResource('vendors/mustache/class.mustache_engine.php'));
        Gdn::factoryInstall('ViewHandler.mustache', 'MustacheHandler');
    }

    /**
     * Allow queue of mustache templates for client side
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function gdn_controller_addMustache_create($sender, $args) {

    }

    /**
     * Add the Mustache javascript libraries and passes the templates
     * that need javascript rendering on the client side.
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        $sender->addJsFile('library/mustache/mustache.js', 'plugins/mustache');
        $sender->addJsFile('mustache.js', 'plugins/mustache');

        $sender->addDefinition('Plugin.Mustache.Templates', $this->templates);
    }

    /**
     * Add a partial template that can be rendered by javascript
     * on the client side
     *
     * @param string $viewName  The name of the partial view (without it's extension)
     * @param string $subFolder Optional subfolder under _partials
     */
    public static function addPartial($viewName, $subFolder = null) {
        $view = combinePaths(['_partials', $subFolder, $viewName]);

        $viewLocation = Gdn::controller()->fetchViewLocation($view, false, false, false);
        if (!$viewLocation) {
            // The partial view was not found in the current controller's view folder
            return false;
        }

        // Build a relative uri from an absolute file path
        $templateRelativeUri = str_replace(
           array(PATH_ROOT, DS),
           array('', '/'),
           $viewLocation
        );

        $controllerName = StringEndsWith(Gdn::controller()->ControllerName, 'controller', true, true);

        $template = [
            'name' => combinePaths([$controllerName, $view]),
            'url' => Gdn::Request()->Url($templateRelativeUri, '//'),
            'type' => 'defer'
        ];

        self::instance()->templates[] = $template;
        return true;
    }

}
