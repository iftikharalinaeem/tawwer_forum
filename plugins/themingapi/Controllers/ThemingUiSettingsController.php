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
class ThemingUiSettingsController extends SettingsController {
    use TwigRenderTrait;
    use StaticCacheTranslationTrait;

    /**
     * ThemingUiSettingsController constructor.
     */
    public function __construct() {
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/themingapi/views';
        parent::__construct();
    }

    /**
     * Render the /knowledge-settings/knowledge-categories page.
     */
    public function themes() {
        $this->render('index');
    }
}
