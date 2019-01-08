<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

use \Vanilla\Knowledge\Models\KnowledgeBaseModel;
use \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController;

 /**
  * Undocumented class
  */
class KnowledgeSettingsController extends SettingsController {
    use \Garden\TwigTrait;

    /** @var \Vanilla\Knowledge\Controllers\Api\KnowledgeBasesApiController */
    private $apiController;

    /** @var Gdn_Form */
    private $form;

    /**
     * Constructor for DI.
     *
     * @param KnowledgeBasesApiController $apiController
     */
    public function __construct(KnowledgeBasesApiController $apiController) {
        $this->apiController = $apiController;
        self::$twigDefaultFolder = PATH_ROOT . '/plugins/knowledge/views';
        $this->form = new Gdn_Form('', 'bootstrap');
        parent::__construct();
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    public function addedit($knowledgeBaseID = null) {
        $this->permission('Garden.Settings.Manage');
        if ($knowledgeBaseID) {
            $record = $this->apiController->get($knowledgeBaseID);
            $this->form->setData($record);
        }

        if ($this->form->authenticatedPostBack()) {
            $this->addEditPostBack($knowledgeBaseID);
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
            'form' => $this->form,
            'formErrors' => [],
            'title' => 'Hello World'
        ]);
        $this->render('addedit');
    }

    private function addEditPostBack($knowledgeBaseID = null) {
        $values = $this->form->formValues();
        try {
            if ($knowledgeBaseID) {
                $this->apiController->patch($knowledgeBaseID, $values);
            } else {
                $this->apiController->post($values);
            }

            $type = $this->deliveryType();

            if ($this->deliveryType() === DELIVERY_TYPE_VIEW) {
                $this->jsonTarget('', '', 'Refresh');
                $this->render();
            } elseif ($this->deliveryType() === DELIVERY_TYPE_ALL) {
                redirectTo('/knowledge/settings');
                $this->render();
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function index() {
        $this->permission('Garden.Settings.Manage');
        $knowledgeBases = $this->apiController->index();
        $this->setData('knowledgeBases', $knowledgeBases);
        $this->render();
    }
}
