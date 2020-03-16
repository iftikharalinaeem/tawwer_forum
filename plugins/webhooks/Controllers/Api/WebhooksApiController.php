<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks;

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Webhooks\Events\PingEvent;
use Vanilla\Webhooks\Library\EventScheduler;
use Vanilla\Webhooks\Library\WebhookConfig;
use Vanilla\Webhooks\Models\WebhookDeliveryModel;
use Vanilla\Webhooks\Models\WebhookModel;

/**
 * WebhooksApiController for the `/webhooks` resource.
 */
class WebhooksApiController extends \AbstractApiController {

    /** @var WebhookDeliveryModel */
    private $deliveryModel;

    /** @var EventScheduler */
    private $scheduler;

    /** @var Schema */
    private $idParamSchema;

    /** @var WebhookModel */
    private $webhookModel;

    /** @var Schema */
    private $webhookSchema;

    /** @var Schema */
    private $webhookPostSchema;

    /**
     * WebhooksApiController constructor.
     *
     * @param WebhookModel $webhookModel
     * @param EventScheduler $scheduler
     * @param WebhookDeliveryModel $deliveryModel
     */
    public function __construct(WebhookModel $webhookModel, EventScheduler $scheduler, WebhookDeliveryModel $deliveryModel) {
        $this->webhookModel = $webhookModel;
        $this->scheduler = $scheduler;
        $this->deliveryModel = $deliveryModel;
    }

    /**
     * Get a list of all webhooks.
     *
     * @return  array
     */
    public function index() {
        $this->permission('Garden.Settings.Manage');
        $in = $this->schema([], 'in');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');
        $rows = $this->webhookModel->get([], ['limit' => 100]);
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a webhook.
     *
     * @param int $id The ID of the webhook.
     * @throws NotFoundException If the webhook could not be found.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.Settings.Manage');
        $this->idParamSchema();
        $in = $this->schema([], ['WebhookGet', 'in']);
        $out = $this->schema($this->webhookSchema(), 'out');
        $webhook = $this->webhookByID($id);
        $result = $out->validate($webhook);

        return $result;
    }

    /**
     * Handle request for a specific webhook delivery.
     *
     * @param integer $webhookID
     * @param string $webhookDeliveryID
     * @return void
     */
    private function getDelivery(int $webhookID, string $webhookDeliveryID) {
        $this->idParamSchema();
        $out = $this->schema(
            $this->webhookDeliverySchema(),
            "out"
        );

        try {
            $delivery = $this->deliveryModel->selectSingle([
                "webhookDeliveryID" => $webhookDeliveryID,
                "webhookID" => $webhookID,
            ]);
        } catch (NoResultsException $e) {
            throw new NotFoundException("Webhook Delivery");
        }

        $result = $out->validate($delivery);
        return $result;
    }

    /**
     * Handle request for a list of recent webhook deliveries.
     *
     * @param integer $webhookID
     * @return void
     */
    private function getDeliveryIndex(int $webhookID, array $query = []) {
        $this->idParamSchema();

        $in = $this->schema([
            "limit" => [
                "default" => WebhookDeliveryModel::LIMIT_DEFAULT,
                "minimum" => 1,
                "maximum" => 100,
                "type" => "integer",
            ],
            "page:i?" => [
                "default" => 1,
                "minimum" => 1,
                "maximum" => 100,
            ]
        ], 'in');
        $out = $this->schema(
            [
                ":a" => $this->schema(
                    Schema::parse([
                        "webhookDeliveryID",
                        "webhookID",
                        "requestDuration",
                        "responseCode",
                        "dateInserted",
                    ])->add($this->webhookDeliverySchema())
                )
            ],
            "out"
        );

        $query = $in->validate($query);
        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        $deliveries = $this->deliveryModel->get(
            ["webhookID" => $webhookID],
            [
                "limit" => $limit,
                "offset" => $offset,
                "orderFields" => "dateInserted",
                "orderDirection" => "desc",
            ]
        );
        $result = $out->validate($deliveries);
        return $result;
    }

    /**
     * Get delivery attempts to a webhook.
     *
     * @param int $id The ID of the webhook.
     * @param string $webhookDeliveryID
     * @throws NotFoundException If the webhook could not be found.
     * @return array
     */
    public function get_deliveries($id, $webhookDeliveryID = null, array $query = []) {
        $this->permission('Garden.Settings.Manage');

        if (is_string($webhookDeliveryID)) {
            $result = $this->getDelivery($id, $webhookDeliveryID);
        } else {
            $result = $this->getDeliveryIndex($id, $query);
        }

        return $result;
    }

    /**
     * Get a webhook for editing.
     *
     * @param int $id The ID of the webhook.
     * @throws NotFoundException If the webhook could not be found.
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.Settings.Manage');

        $this->idParamSchema();
        $in = $this->schema([], ['WebhookGetEdit', 'in']);
        $out = $this->schema(Schema::parse([
            "webhookID",
            "status",
            "name",
            "events",
            "url",
            "secret",
        ])->add($this->webhookSchema()), "out");

        $webhook = $this->webhookByID($id);
        $result = $out->validate($webhook);

        return $result;
    }

    /**
     * Add a webhook.
     *
     * @param array $body The request body.
     * @throws ServerException If the webhook could not be created.
     * @return array|\Exception If an error is encountered while performing the query.
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');
        $in = $this->webhookPostSchema('in');
        $out = $this->webhookSchema('out');

        $body = $in->validate($body);

        $id = $this->webhookModel->insert($body);
        if (!$id) {
            throw new ServerException('Unable to insert webhook.', 500);
        }
        $row = $this->webhookByID($id);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Delete a webhook.
     *
     * @param int $id The ID of the webhook.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function delete($id) {
        $this->permission('Garden.Settings.Manage');
        $in = $this->idParamSchema();
        $out = $this->schema([], 'out');
        $this->webhookModel->delete(['webhookID' => $id], ['limit' => 1]);
    }

    /**
     * Update a webhook.
     *
     * @param int $id The ID of the webhook.
     * @param array $body The request body.
     * @throws \Exception If an error is encountered while performing the query.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $this->idParamSchema('in');
        $in = $this->webhookPostSchema('in');
        $out = $this->webhookSchema('out');
        $webhookData = $in->validate($body, true);
        $this->webhookModel->update($webhookData, ['webhookID' => $id]);
        $row = $this->webhookByID($id);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a webhook by its numeric ID.
     *
     * @param int $id The webhook ID.
     * @throws NotFoundException If the webhook could not be found.
     * @return array
     */
    private function webhookByID($id) {
        try {
            $row = $this->webhookModel->getID($id);
        } catch (\Exception $e) {
            throw new NotFoundException("Webhook not found.");
        }
        return $row;
    }

    /**
     * Ping a webhook.
     *
     * @param int $id The webhook ID.
     * @throws NotFoundException If the webhook could not be found.
     * @return array
     */
    public function post_pings(int $id): array {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema([]);
        $out = $this->schema([
            "webhookID" => ["type" => "integer"],
            "dateInserted" => [
                "type" => "string",
                "format" => "date-time",
            ]
        ])->add($this->webhookSchema());

        $row = $this->webhookModel->getID($id);
        $webhookConfig = new WebhookConfig($row);

        $payload = [
            "webhookID" => $id,
            "dateInserted" => date("c"),
        ];
        $pingEvent = new PingEvent(PingEvent::ACTION_PING, $payload);
        $this->scheduler->addDispatchEventJob($pingEvent, $webhookConfig);
 
        $result = $out->validate($payload);
        return $result;
    }

    /**
     * Get a schema representing a single webhook delivery row.
     *
     * @return Schema
     */
    private function webhookDeliverySchema(): Schema {
        $schema = Schema::parse([
            "webhookDeliveryID" => [
                "type" => "string",
            ],
            "webhookID" => [
                "type" => "integer",
            ],
            "requestBody" => [
                "type" => "string",
            ],
            "requestDuration" => [
                "allowNull" => true,
                "type" => "integer",
            ],
            "requestHeaders" => [
                "type" => "string",
            ],
            "responseBody" => [
                "allowNull" => true,
                "type" => "string",
            ],
            "responseCode" => [
                "allowNull" => true,
                "type" => "integer",
            ],
            "responseHeaders" => [
                "type" => "string",
            ],
            "dateInserted" => [
                "type" => "datetime",
            ],
        ]);
        return $schema;
    }

    /**
     * Get a webhook schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function webhookPostSchema($type = '') {
        if ($this->webhookPostSchema === null) {
            $this->webhookPostSchema = $this->schema(
                Schema::parse([
                    'status?',
                    'events',
                    'name',
                    'url',
                    'secret' => [
                        'minLength' => '20'
                    ]
                ])->add($this->fullSchema()),
                'WebhookPost'
            );
        }
        return $this->schema($this->webhookPostSchema, $type);
    }

    /**
     * Get the full webhook schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function webhookSchema($type = '') {
        if ($this->webhookSchema === null) {
            $this->webhookSchema = $this->schema($this->fullSchema(), 'Webhook');
        }
        return $this->schema($this->webhookSchema, $type);
    }

    /**
     * Get a schema instance comprised of all available webhook fields.
     *
     * @return Schema Returns a schema object.
     */
    private function fullSchema() {
        return Schema::parse([
            'webhookID:i' => 'The webhook identifier.',
            'status' => [
                'type' => 'string',
                'enum' => ['active', 'disabled'],
            ],
            'name:s' => 'User-friendly name.',
            'events' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => ['*', 'comment', 'discussion', 'user'],
                ]
            ],
            'url:s' => 'The target URL of the webhook.',
            'secret' => [
                'type' => 'string'
            ],
            'dateInserted:dt?' => 'The date/time that the webhook was created.',
            'insertUserID:i?' => 'The user that created the webhook.',
            'dateUpdated:dt|n?' => 'The date/time that the webhook was created.',
            'updateUserID:i?' => 'The user that created the webhook.'
        ]);
    }

    /**
     * Get an ID-only webhook record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The webhook ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }
}
