<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Garden\Web\Data;
use Vanilla\Webhooks\Controllers\Api\ActionConstants;
use Vanilla\Web\JsInterpop\ReduxAction;
use Vanilla\Webhooks\Controllers\Api\WebhooksApiController;

/**
 * Controller for webhook settings.
 */
class WebhookSettingsController extends SettingsController {

    /** @var WebhooksApiController */
    private $apiController;

    /** @var Gdn_Request */
    private $request;

    /**
     * WebhookSettingsController constructor.
     *
     * @param WebhooksApiController $apiController
     * @param Gdn_Request $request
     */
    public function __construct(
        WebhooksApiController $webhooksApiController,
        Gdn_Request $request
    ) {
        parent::__construct();
        $this->webhooksApiController = $webhooksApiController;
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
        $webhooks = $this->webhooksApiController->index();
        $this->addReduxAction(new ReduxAction(
            ActionConstants::GET_ALL_WEBHOOKS,
            Data::box($webhooks),
            []
        ));

        $this->render();
    }
}
