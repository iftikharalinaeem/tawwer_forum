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
 * Undocumented class
 */
class KnowledgeSettingsController extends SettingsController {
    use \Garden\TwigTrait;

    /** @var \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController */
    private $apiController;

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
     * Undocumented function
     *
     * @return void
     */
    public function knowledgeBases() {
        $pathArgs = $this->RequestArgs;
        $isIndex = count($pathArgs) === 0;
        $isEdit =
            count($pathArgs) === 2 &&
            $id = filter_var($pathArgs[0], FILTER_VALIDATE_INT) &&
            $pathArgs[1] === 'edit';
        $isAdd = count($pathArgs) === 1 && $pathArgs[0] === 'add';

        if ($isIndex) {
            $this->knowledgeBases_index();
        } elseif ($isEdit) {
            $this->knowledgeBases_addedit($id);
        } elseif ($isAdd) {
            $this->knowledgeBases_addedit();
        }
    }

    /**
     * Render the /knowledge/settings/knowledge-categories page.
     */
    private function knowledgeBases_index() {
        $this->permission('Garden.Settings.Manage');
        $knowledgeBases = $this->apiController->index();
        $this->setData('knowledgeBases', $knowledgeBases);
        $this->render('index');
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    private function knowledgeBases_addedit($knowledgeBaseID = null) {
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
            ],
            'urlCode' => [
                'LabelCode' => 'URL Code',
            ],
            'description' => [
                'LabelCode' => 'Description',
                'Control' => 'textbox',
                'Options' => ['MultiLine' => true],
            ],
            'icon' => [
                'LabelCode' => 'Icon',
                'Control' => 'imageuploadpreview',
            ],
            'viewType' => [
                'LabelCode' => 'View Type',
                'Control' => 'dropdown',
                'Items' => [
                    KnowledgeBaseModel::TYPE_GUIDE => t('Guide'),
                    KnowledgeBaseModel::TYPE_HELP => t('Help'),
                ],
            ],
            'sortArticles' => [
                'LabelCode' => 'Sort Articles',
                'Control' => 'dropdown',
                'Items' => [
                    KnowledgeBaseModel::ORDER_DATE_DESC => 'Newest First',
                    KnowledgeBaseModel::ORDER_DATE_ASC => 'Oldest First',
                    KnowledgeBaseModel::ORDER_NAME => 'Alphabetically',
                    // Manual is not an option here. That is determined by the viewType === Guide
                ],
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

        if ($knowledgeBaseID) {
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

    private function handleFormMediaUpload(Vanilla\UploadedFile $file): string {
        $image = $this->mediaApiController->post([
            'file' => $file,
            'type' => MediaApiController::TYPE_IMAGE,
        ]);

        return $image['url'];
    }
}
