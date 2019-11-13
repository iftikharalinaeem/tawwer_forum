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

    /** @var LocalesApiController */
    private $localApiController;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;
    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $apiController
     * @param MediaApiController $mediaApiController
     * @param Gdn_Request $request
     * @param KnowledgeBaseKludgedVars $kludgedVars
     * @param LocalesApiController $localApiController
     * @param  KnowledgeBaseModel $knowledgeBaseModel
     */
    public function __construct(
        KnowledgeBasesApiController $apiController,
        MediaApiController $mediaApiController,
        Gdn_Request $request,
        KnowledgeBaseKludgedVars $kludgedVars,
        LocalesApiController $localApiController,
        KnowledgeBaseModel $knowledgeBaseModel
    ) {
        $this->apiController = $apiController;
        $this->mediaApiController = $mediaApiController;
        $this->request = $request;
        $this->kludgedVars = $kludgedVars;
        $this->localApiController = $localApiController;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
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

        $configurationModule->initialize($configValues);

        $this->setData("ConfigurationModule", $configurationModule);
        $this->render();
    }

    /**
     * Main entry function for all /knowledge-settings/knowledge-bases routes.
     *
     * @param int|null $knowledgeBaseID
     * @param string|null $action
     * @return void
     */
    public function knowledgeBases($knowledgeBaseID = null, $action = null) {
        $action = strtolower($action ?? "");

        if ($knowledgeBaseID !== null) {
            $knowledgeBaseID = filter_var($knowledgeBaseID, FILTER_VALIDATE_INT);
            switch ($action) {
                case "delete":
                    $this->knowledgeBasesDelete($knowledgeBaseID, $this->request->get("purge") === "purge");
                    break;
                default:
                    throw new NotFoundException("Page");
            }
        } else {
            $this->knowledgeBasesIndex($this->request->get("status", KnowledgeBaseModel::STATUS_PUBLISHED));
        }
    }

    /**
     * Render the /knowledge-settings/knowledge-categories page.
     *
     * @param string $status
     */
    private function knowledgeBasesIndex(string $status) {
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

    /**
     * Handle a request to "soft" delete a knoweldge base.
     *
     * @param integer $knowledgeBaseID
     * @param bool $purge Perform a true delete of this knowledge base?
     */
    private function knowledgeBasesDelete(int $knowledgeBaseID, bool $purge = false) {
        $this->deliveryMethod(DELIVERY_METHOD_JSON);

        if ($this->Form->authenticatedPostBack()) {
            if ($purge) {
                $this->apiController->delete($knowledgeBaseID);
                $this->informMessage(sprintf(self::t("%s purged."), self::t("Knowledge Base")));
            } else {
                $this->apiController->patch($knowledgeBaseID, [
                    "status" => KnowledgeBaseModel::STATUS_DELETED,
                ]);
                $this->informMessage(sprintf(self::t("%s deleted."), self::t("Knowledge Base")));
            }
            $this->setRedirectTo("/knowledge-settings/knowledge-bases");
            $this->render("blank", "utility", "dashboard");
        }
    }
}
