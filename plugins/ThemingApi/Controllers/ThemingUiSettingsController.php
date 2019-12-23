<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Knowledge\Models\KnowledgeBaseKludgedVars;
use \Vanilla\Knowledge\Models\KnowledgeBaseModel;
use \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Garden\StaticCacheTranslationTrait;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Web\TwigRenderTrait;
use Garden\Web\Data;
use Vanilla\Knowledge\Controllers\Api\ActionConstants;
use Vanilla\Web\JsInterpop\ReduxAction;

/**
 * Controller for serving the /theming-ui-settings pages.
 */
class ThemingUiSettingsController extends SettingsController {

    use TwigRenderTrait;

    use StaticCacheTranslationTrait;

    /** @var \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController */
    private $apiController;

    /** @var MediaApiController */
    private $mediaApiController;

    /** @var Gdn_Request */
    private $request;

    /** @var KnowledgeBaseKludgedVars */
    private $kludgedVars;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;
    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $apiController
     * @param Gdn_Request $request
     * @param KnowledgeBaseKludgedVars $kludgedVars
     * @param  KnowledgeBaseModel $knowledgeBaseModel
     */
//    public function __construct(
//        KnowledgeBasesApiController $apiController,
//        Gdn_Request $request,
//        KnowledgeBaseKludgedVars $kludgedVars,
//        KnowledgeBaseModel $knowledgeBaseModel
//    ) {
//        $this->apiController = $apiController;
//        $this->request = $request;
//        $this->kludgedVars = $kludgedVars;
//        $this->knowledgeBaseModel = $knowledgeBaseModel;
//        //self::$twigDefaultFolder = PATH_ROOT . '/plugins/knowledge/views';
//        parent::__construct();
//    }



    /**
     * Render the /knowledge-settings/knowledge-categories page.
     *
     * @param int|null $knowledgeBaseID
     * @param string|null $action
     * @return void
     */
    public function themes() {
        //die(__CLASS__.':'. __LINE__);
//        $status = $this->request->get("status", KnowledgeBaseModel::STATUS_PUBLISHED);
//        if ($status === KnowledgeBaseModel::STATUS_DELETED) {
//            $this->title(t('Deleted Knowledge Bases'));
//        } else {
//            $this->title(t("Knowledge Bases"));
//        }

//        $this->permission('Garden.Settings.Manage');
//
//        $knowledgeBases = $this->apiController->index(["status" => $status, 'siteSectionGroup' => 'all', "expand" => "all"]);
//        $this->addReduxAction(new ReduxAction(
//            ActionConstants::GET_ALL_KBS,
//            Data::box($knowledgeBases),
//            ["status" => $status]
//        ));
//
//        $this->setData('knowledgeBases', $knowledgeBases);
//        $this->setData("status", $status);
//        $this->addIndexNavigation($status);

        $this->render('index2');
    }
}
