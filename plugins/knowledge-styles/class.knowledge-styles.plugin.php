<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class KeywordBlocker
 */
class KnowledgeStyles extends Gdn_Plugin {

    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('knowledge-styles.css', 'plugins/knowledge-styles');
    }

}
