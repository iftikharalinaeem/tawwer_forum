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
     * Add a partial template that can be rendered by javascript on the client side.
     * It first looks for a view under the controller partial view folder "views/{ControllerName}/_partials" and
     * cascade to the application partial view folder "views/_partials" if not found.
     *
     * @param string $viewName  The name of the partial view relative to a _partial folder (without it's extension)
     * @return boolean          Whether the partial template was found and added or not
     */
    public static function addPartial($viewName) {
        // Build a partial view path
        $view = combinePaths(['_partials', $viewName]);

        // Lookup under the controller view folder such as in under /views/{ControllerName}/_partials/
        $viewLocation = Gdn::controller()->fetchViewLocation($view, false, false, false);
        if (!$viewLocation) {
            // The partial view was not found in the current controller's view folder
            // Lookup under the global partials such as in under /views/_partials
            $viewLocation = Gdn::controller()->fetchViewLocation($view, '', false, false);
            if (! $viewLocation) {
                return false;
            }
        }

        // Build a relative uri from the absolute file path found
        $templateRelativeUri = str_replace(
           array(PATH_ROOT, DS),
           array('', '/'),
           $viewLocation
        );

        $template = [
            'name' => $viewName,
            'url' => Gdn::Request()->Url($templateRelativeUri, '//'),
            'type' => 'defer'
        ];

        self::instance()->templates[] = $template;
        return true;
    }

}
