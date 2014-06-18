<?php if (!defined('APPLICATION')) exit;

/**
 * Slidey Theme Hooks
 *
 * @author    Rebecca Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Rebecca Van Bussel
 * @license   Proprietary
 * @package   Slidey
 * @since     1.0.0
 */
class SlideyThemeHooks implements Gdn_IPlugin {
    /**
     * This will run when you "Enable" the theme
     *
     * @since  1.0.0
     * @access public
     * @return bool
     */
    public function setup() {
        return true;
    }
}
