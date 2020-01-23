<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Web\TwigRenderTrait;

/**
 * Controller for theme editor.
 */
class ThemeController extends SettingsController {

    use TwigRenderTrait;

    /** @var \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController */
    private $apiController;

    /** @var MediaApiController */
    private $mediaApiController;

    /** @var Gdn_Request */
    private $request;

    /**
     * Constructor for DI.
     *
     * @param ThemesApiController $apiController
     * @param Gdn_Request $request
     */
    public function __construct(
        ThemesApiController $apiController,
        Gdn_Request $request
    ) {
        $this->apiController = $apiController;
        $this->request = $request;
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/themingapi/views';
        parent::__construct();
    }

    public function editor() {
       $this->render('index');
    }

    public function add($themeName) {
        $this->setData($themeName);
        $this->render('index');
    }

    public function theme_edit($id) {
        $this->render('index');
    }
}
