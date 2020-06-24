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
use Vanilla\Theme\ThemeFeatures;
use Vanilla\Web\JsInterpop\ReduxAction;

/**
 * Controller for serving the /knowledge-settings pages.
 */
class KnowledgeSettingsController extends SettingsController {

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

    /** @var ThemeFeatures */
    private $themeFeatures;

    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $apiController
     * @param Gdn_Request $request
     * @param KnowledgeBaseKludgedVars $kludgedVars
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param ThemeFeatures $themeFeatures
     */
    public function __construct(
        KnowledgeBasesApiController $apiController,
        Gdn_Request $request,
        KnowledgeBaseKludgedVars $kludgedVars,
        KnowledgeBaseModel $knowledgeBaseModel,
        ThemeFeatures $themeFeatures
    ) {
        $this->apiController = $apiController;
        $this->request = $request;
        $this->kludgedVars = $kludgedVars;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->themeFeatures = $themeFeatures;
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/knowledge/views';
        parent::__construct();
    }

    /**
     * Add index-page navigation into the page's Help panel.
     *
     * @param string $currentStatus
     */
    private function addIndexNavigation(string $currentStatus = null) {
        if ($currentStatus === null || $currentStatus === KnowledgeBaseModel::STATUS_PUBLISHED) {
            $title = self::t("Deleted Knowledge Bases");
            $url = "knowledge-settings/knowledge-bases?status=" . KnowledgeBaseModel::STATUS_DELETED;
        } else {
            $title = self::t("Knowledge Bases");
            $url = "knowledge-settings/knowledge-bases";
        }

        $this->addHelpWidget(self::t("Navigation"), "<a href=\"{$this->request->url($url)}\">$title</a>");
    }

    /**
     * A local version of the helpAsset function. Generates an aside element in the Help panel.
     *
     * @param string $title
     * @param string $content
     */
    private function addHelpWidget(string $title, string $content) {
        $widget = $this->renderTwig("knowledgesettings/helpasset.twig", [
            "content" => $content,
            "title" => $title,
        ]);
        $this->addAsset("Help", $widget);
    }

    /**
     * General Appearance
     */
    public function generalAppearance() {
        $this->permission("Garden.Settings.Manage");

        $configurationModule = new ConfigurationModule($this);
        $configValues = [];
        $configValues += $this->kludgedVars->prepareAsFormValues($this->kludgedVars->getBannerVariables());

        if (!$this->themeFeatures->disableKludgedVars()) {
            $configValues += [
                "GlobalColorsTitle" => [
                    "Control" => "title",
                    "Title" => "Global Site Colors",
                ],
            ];
            $configValues += $this->kludgedVars->prepareAsFormValues($this->kludgedVars->getGlobalColors());
            $configValues += [
                "HeaderVarstitle" => [
                    "Control" => "title",
                    "Title" => "Title Bar Options",
                ],
            ];
            $configValues += $this->kludgedVars->prepareAsFormValues($this->kludgedVars->getHeaderVars());
            $configValues += [
                "SizingVarsTitle" => [
                    "Control" => "title",
                    "Title" => "Sizing Options",
                ],
            ];
            $configValues += $this->kludgedVars->prepareAsFormValues($this->kludgedVars->getSizingVariables());
        }

        $configurationModule->initialize($configValues);

        $this->setData("ConfigurationModule", $configurationModule);
        $this->render();
    }

    /**
     * Render the /knowledge-settings/knowledge-categories page.
     *
     * @return void
     */
    public function knowledgeBases() {
        $status = $this->request->get("status", KnowledgeBaseModel::STATUS_PUBLISHED);
        if ($status === KnowledgeBaseModel::STATUS_DELETED) {
            $this->title(t('Deleted Knowledge Bases'));
        } else {
            $this->title(t("Knowledge Bases"));
        }

        $this->permission('Garden.Settings.Manage');

        $knowledgeBases = $this->apiController->index(["status" => $status, 'siteSectionGroup' => 'all', "expand" => "all"]);
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ALL_KBS,
            Data::box($knowledgeBases),
            ["status" => $status]
        ));

        $this->setData('knowledgeBases', $knowledgeBases);
        $this->setData("status", $status);
        $this->addIndexNavigation($status);

        $this->render('index');
    }
}
