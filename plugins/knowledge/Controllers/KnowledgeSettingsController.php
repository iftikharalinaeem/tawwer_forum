<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Knowledge\Models\KnowledgeBaseKludgedVars;
use \Vanilla\Knowledge\Models\KnowledgeBaseModel;
use \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Garden\Schema\ValidationException;
use Garden\StaticCacheTranslationTrait;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\TwigRenderTrait;
use Garden\Schema\Validation;

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

    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $apiController
     * @param MediaApiController $mediaApiController
     * @param Gdn_Request $request
     * @param KnowledgeBaseKludgedVars $kludgedVars
     */
    public function __construct(
        KnowledgeBasesApiController $apiController,
        MediaApiController $mediaApiController,
        Gdn_Request $request,
        KnowledgeBaseKludgedVars $kludgedVars
    ) {
        $this->apiController = $apiController;
        $this->mediaApiController = $mediaApiController;
        $this->request = $request;
        $this->kludgedVars = $kludgedVars;
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
            $title = self::t("Deleted Knoweldge Bases");
            $url = "knowledge-settings/knowledge-bases?status=" . KnowledgeBaseModel::STATUS_DELETED;
        } else {
            $title = self::t("Knoweldge Bases");
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

        if ($knowledgeBaseID === "add") {
            $this->knowledgeBasesAddEdit();
        } elseif ($knowledgeBaseID !== null) {
            $knowledgeBaseID = filter_var($knowledgeBaseID, FILTER_VALIDATE_INT);
            switch ($action) {
                case "delete":
                    $this->knowledgeBasesDelete($knowledgeBaseID, $this->request->get("purge") === "purge");
                    break;
                case "edit":
                    $this->knowledgeBasesAddEdit($knowledgeBaseID);
                    break;
                case "publish":
                    $this->knowledgeBasesPublish($knowledgeBaseID);
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
        $this->permission('Garden.Settings.Manage');

        $knowledgeBases = $this->apiController->index(["status" => $status, 'siteSectionGroup' => 'all']);
        $this->setData('knowledgeBases', $knowledgeBases);
        $this->setData("status", $status);
        $this->addIndexNavigation($status);

        $this->render('index');
    }

    /**
     * Render the add & edit pages for the knowledge base.
     *
     * - /knowledge-settings/knowledge-bases/add
     * - /knowledge-settings/knowledge-bases/:id/edit
     *
     * @param string|int|null $knowledgeBaseID The ID of the KB being edited.
     *
     * @return void
     */
    private function knowledgeBasesAddEdit($knowledgeBaseID = null) {
        $this->permission('Garden.Settings.Manage');

        if ($knowledgeBaseID) {
            $record = $this->apiController->get($knowledgeBaseID);
            $this->Form->setData($record);
        }

        if ($this->Form->authenticatedPostBack()) {
            try {
                $this->post_addEdit($knowledgeBaseID);
            } catch (ValidationException $e) {
                $validation = ModelUtils::validationExceptionToValidationResult($e);
                $this->Form->setValidationResults($validation->results());
            }
        }

        $localModel = new LocaleModel();
        $availableLocales =  $localModel->enabledLocalePacks();

        // Set the form elements on the add/edit form.
        $formData = [
            'name' => [
                'LabelCode' => 'Name',
                "Description" => "Title of the knowledge base.",
            ],
            'urlCode' => [
                'LabelCode' => 'URL Code',
                "Description" => "A customized version of the knowledge base name as it should appear in URLs.",
                "Options" => [
                    "data-react-input" => true,
                    "data-validation-filter" => "slug",
                ],
            ],
            'siteSectionGroup' => [
                'Control' => 'react',
                'Component' => 'site-section-group-selector-form-group',
            ],
            'description' => [
                'LabelCode' => 'Description',
                'Control' => 'textbox',
                'Options' => ['MultiLine' => true],
                "Description" => "A description of the knowledge base. Displayed in the knowledge base picker.",
            ],
            'icon' => [
                'LabelCode' => 'Icon',
                'Control' => 'imageuploadpreview',
                "Description" => "A small image used to represent the knowledge base. Displayed in the knowledge base picker.",
            ],
            "bannerImage" => [
                "Control" => "imageuploadpreview",
                "LabelCode" => "Banner Image",
                "Description" => "Homepage banner image for this knowledge base."
            ],
            'viewType' => [
                "Description" => "Determines how the categories and articles within it will display",
                'LabelCode' => 'View Type',
                'Control' => 'callback',
                'Callback' => function (Gdn_Form $form, array $inputRow): string {
                    return $this->renderViewTypePicker($form, $inputRow);
                },
            ],
            'sortArticles' => [
                "Description" => "Sorting method for articles.",
                'LabelCode' => 'Sort Articles',
                'Control' => 'dropdown',
                'Items' => [
                    KnowledgeBaseModel::ORDER_DATE_DESC => 'Newest First',
                    KnowledgeBaseModel::ORDER_DATE_ASC => 'Oldest First',
                    KnowledgeBaseModel::ORDER_NAME => 'Alphabetically',
                    // Manual is not an option here. That is determined by the viewType === Guide
                ],
                'ItemWrap' => [
                    '<li class="form-group js-sortArticlesGroup">',
                    '</li>'
                ]
            ],
            'sourceLocale' => [
                "Description" => "The source locale of the knowledge base" ,
                'LabelCode' => 'Source Locale',
                'Control' => 'dropdown',
                'Items' => [

                ],
            ],
        ];

        $this->setData('formData', $formData);
        $this->render('addedit');
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

    /**
     * Handle a request to flag a knowledge base as published.
     *
     * @param integer $knowledgeBaseID
     */
    private function knowledgeBasesPublish(int $knowledgeBaseID) {
        $this->deliveryMethod(DELIVERY_METHOD_JSON);

        if ($this->Form->authenticatedPostBack()) {
            $this->apiController->patch($knowledgeBaseID, [
                "status" => KnowledgeBaseModel::STATUS_PUBLISHED,
            ]);
            $this->informMessage(sprintf(self::t("%s published."), self::t("Knowledge Base")));
            $this->setRedirectTo("/knowledge-settings/knowledge-bases");
            $this->render("blank", "utility", "dashboard");
        }
    }

    /**
     * Post method for the add/edit pages.
     *
     * @param string|null $knowledgeBaseID The ID of the edit page or null for an add page.
     */
    private function post_addEdit($knowledgeBaseID = null) {
        $values = $this->Form->formValues();
        if ($values['icon_New']) {
            $values['icon'] = $this->handleFormMediaUpload($values['icon_New'], "icon");
        }
        if ($values["bannerImage_New"]) {
            $values["bannerImage"] = $this->handleFormMediaUpload($values["bannerImage_New"], "bannerImage");
        }

        // A guide must be sorted manually.
        if ($values["viewType"] === KnowledgeBaseModel::TYPE_GUIDE) {
            $values["sortArticles"] = KnowledgeBaseModel::ORDER_MANUAL;
        } elseif ($values["sortArticles"] === KnowledgeBaseModel::ORDER_MANUAL) {
            // If it isn't a guide, it can't be sorted manually.
            $values["sortArticles"] = KnowledgeBaseModel::ORDER_DATE_DESC;
        }

        if ($knowledgeBaseID) {
            $knowledgeBaseID = (int)$knowledgeBaseID;
            $this->apiController->patch($knowledgeBaseID, $values);
        } else {
            $this->apiController->post($values);
        }

        if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
            $this->jsonTarget('', '', 'Refresh');
            $this->render('blank', 'utility', 'dashboard');
        } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            $this->setRedirectTo('/vanilla/settings/categories');
            $this->render('blank', 'utility', 'dashboard');
        }
    }

    /**
     * Generate HTML for view type picker form inputs.
     *
     * @param Gdn_Form $form
     * @param array $inputRow
     * @return string
     */
    private function renderViewTypePicker(Gdn_Form $form, array $inputRow): string {
        $descriptionHtml = $inputRow["DescriptionHtml"] ?? "";

        $label = '<div class="label-wrap">' . $form->label(
            $inputRow["LabelCode"] ?? "",
            "viewType",
            $inputRow
        ) . $descriptionHtml . '</div>';

        $options = [
            KnowledgeBaseModel::TYPE_GUIDE => [
                "info" => "Guides are for making howto guides, documentation, or any \"book\" like content that should be read in order.",
                "label" => self::t("Guide"),
            ],
            KnowledgeBaseModel::TYPE_HELP => [
                "info" => "Help centers are for making free-form help articles that are organized into categories.",
                "label" => self::t('Help Center'),
            ],
        ];

        $controls = "<ul>";
        foreach ($options as $value => $option) {
            $info = '<div class="info">' .$option["info"] . '</div>';
            $control = $form->radio(
                "viewType",
                $option["label"],
                ["class" => "js-viewType", "value" => $value]
            );
            $controls .= '<li class="' . $form->getStyle("radio-container") . '">' . $control . $info . '</li>';
        }
        $controls .= "</ul>";

        return '<li class="' . $form->getStyle("form-group").'">' . $label . '<div class="input-wrap">' . $controls . '</div></li>';
    }

    /**
     * Pass along an UploadFile to the MediaApiController and return it's URL.
     *
     * @param Vanilla\UploadedFile $file
     * @param string $field
     *
     * @return string
     */
    private function handleFormMediaUpload(Vanilla\UploadedFile $file, string $field): string {
        try {
            $image = $this->mediaApiController->post([
                'file' => $file,
                'type' => MediaApiController::TYPE_IMAGE,
            ]);
        } catch (ValidationException $e) {
            // Migrate error messages to ensure the ability to associate errors with the correct form field.
            $validation = $e->getValidation();
            foreach ($validation->getErrors() as $error) {
                $validation->addError($field, $error["message"], $error);
            }
            throw $e;
        }

        return $image['url'];
    }
}
