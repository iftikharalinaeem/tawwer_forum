<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class KnowledgeStylesPlugin
 */
class KnowledgeStylesPlugin extends Gdn_Plugin {
    public function gdn_smarty_init($sender) {
        $sender->addPluginsDir($this->getAddon()->path('/SmartyPlugins'));
    }
}
