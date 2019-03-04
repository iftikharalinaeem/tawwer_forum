<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use \Vanilla\Knowledge\Models\KnowledgeBaseModel;
use \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;
use Garden\Schema\ValidationException;
use Vanilla\Utility\ModelUtils;

/**
 * Controller for serving the /knowledge-settings pages.
 */
class KnowledgeSettingsController extends SettingsController {
    use \Garden\TwigTrait;

    /** @var \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController */
    private $apiController;

    /** @var MediaApiController */
    private $mediaApiController;

    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $apiController
     * @param MediaApiController $mediaApiController
     */
    public function __construct(
        KnowledgeBasesApiController $apiController,
        MediaApiController $mediaApiController
    ) {
        $this->apiController = $apiController;
        $this->mediaApiController = $mediaApiController;
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/knowledge/views';
        parent::__construct();
    }

    /**
     * General Appearance
     */
    public function generalAppearance() {
        $this->permission("Garden.Settings.Manage");

        $configurationModule = new ConfigurationModule($this);
        $configurationModule->initialize([
            "Knowledge.DefaultBannerImage" => [
                "Control" => "imageupload",
                "LabelCode" => "Banner Image",
                "Options" => [
                    "RemoveConfirmText" => sprintf(t("Are you sure you want to delete your %s?"), t("banner image"))
                ],
            ],
            "Knowledge.ChooserTitle" => [
                "LabelCode" => "Knowledge Base Chooser Title",
                "Control" => "textbox",
            ],
        ]);

        $this->setData("ConfigurationModule", $configurationModule);
        $this->render();
    }

    /**
     * Main entry function for all /knowledge-settings/knowledge-bases routes.
     *
     * @return void
     */
    public function knowledgeBases() {
        $pathArgs = $this->RequestArgs;
        $isIndex = count($pathArgs) === 0;
        $isEdit =
            count($pathArgs) === 2 &&
            ($id = filter_var($pathArgs[0], FILTER_VALIDATE_INT)) &&
            $pathArgs[1] === 'edit';
        $isAdd = count($pathArgs) === 1 && $pathArgs[0] === 'add';

        if ($isIndex) {
            $this->knowledgeBasesIndex();
        } elseif ($isEdit) {
            $this->knowledgeBasesAddEdit($id);
        } elseif ($isAdd) {
            $this->knowledgeBasesAddEdit();
        }
    }

    /**
     * Render the /knowledge-settings/knowledge-categories page.
     */
    private function knowledgeBasesIndex() {
        $this->permission('Garden.Settings.Manage');
        $knowledgeBases = $this->apiController->index();
        $this->setData('knowledgeBases', $knowledgeBases);
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

        // Set the form elements on the add/edit form.
        $formData = [
            'name' => [
                'LabelCode' => 'Name',
                "Description" => "Title of the knowledge base.",
            ],
            'urlCode' => [
                'LabelCode' => 'URL Code',
                "Description" => "A customized version of the category name as it should appear in URLs.",
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
        ];

        $this->setData([
            'formData' => $formData,
            'form' => $this->Form
        ]);
        $this->render('addedit');
    }

    /**
     * Post method for the add/edit pages.
     *
     * @param string|null $knowledgeBaseID The ID of the edit page or null for an add page.
     */
    private function post_addEdit($knowledgeBaseID = null) {
        $values = $this->Form->formValues();
        if ($values['icon_New']) {
            $values['icon'] = $this->handleFormMediaUpload($values['icon_New']);
        }
        if ($values["bannerImage_New"]) {
            $values["bannerImage"] = $this->handleFormMediaUpload($values["bannerImage_New"]);
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
                "label" => t("Guide"),
            ],
            KnowledgeBaseModel::TYPE_HELP => [
                "info" => "Help centers are for making free-form help articles that are organized into categories.",
                "label" => t('Help Center'),
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
     *
     * @return string
     */
    private function handleFormMediaUpload(Vanilla\UploadedFile $file): string {
        $image = $this->mediaApiController->post([
            'file' => $file,
            'type' => MediaApiController::TYPE_IMAGE,
        ]);

        return $image['url'];
    }
}
