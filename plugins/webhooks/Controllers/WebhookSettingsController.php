<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Webhooks\Models\WebhookModel;
use Vanilla\Webhooks\Controllers\Api\WebhooksApiController;

/**
 * Controller for webhook settings.
 */
class WebhookSettingsController extends SettingsController {

    /** @var WebhooksApiController */
    private $apiController;

    /** @var Gdn_Request */
    private $request;

    public function __construct(
        WebhooksApiController $apiController,
        Gdn_Request $request
    ) {
        parent::__construct();
        $this->apiController = $apiController;
        $this->request = $request;
    }

    /**
     * Serve all paths.
     *
     * @param string $path Any path.
     */
    public function index(string $path = null) {
        $this->permission('Garden.Settings.Manage');
        $this->setHighlightRoute('webhook-settings');
        $this->title(t("Webhooks"));
        $webhooks = $this->apiController->index();
        $this->setData('webhooks', $webhooks);
        $this->render();
    }
}
