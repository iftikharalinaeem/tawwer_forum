<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\StaticCacheTranslationTrait;
use Vanilla\Web\TwigRenderTrait;

/**
 * Controller for serving the /theming-ui-settings pages.
 */
class ThemeController extends SettingsController {
    use TwigRenderTrait;
    use StaticCacheTranslationTrait;

    public function __construct() {
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/themingapi/views';
        parent::__construct();
    }

    /**
     * Render the /knowledge-settings/knowledge-categories page.
     *
     * @param int|null $knowledgeBaseID
     * @param string|null $action
     * @return void
     */
    public function editor() {
        $this->render('index');
    }
}
