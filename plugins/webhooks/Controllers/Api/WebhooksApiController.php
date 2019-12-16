<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks;

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;

/**
 * WebhooksApiController for the `/webhooks` resource.
 */
class WebhooksApiController extends \AbstractApiController {

    /** @var WebhookModel */
    private $webhookModel;

    /** @var Schema */
    private $webhookSchema;

    /** @var Schema */
    private $webhookPostSchema;

    /** @var Schema */
    private $idParamSchema;

    /**
     * WebhooksApiController constructor.
     *
     * @param WebhookModel $webhookModel
     */
    public function __construct(WebhookModel $webhookModel) {
        $this->webhookModel = $webhookModel;
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
     * Add a webhook.
     *
     * @param array $body The request body.
     * @throws ServerException If the webhook could not be created.
     * @return array|Exception If an error is encountered while performing the query.
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
     * Get a webhook schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    private function webhookPostSchema($type = '') {
        if ($this->webhookPostSchema === null) {
            $this->webhookPostSchema = $this->schema(
                Schema::parse([
                    'active?',
                    'events',
                    'name',
                    'url',
                    'secret',
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
            'active:b' => 'Whether or not this webhook will send events.',
            'name:s' => 'User-friendly name.',
            'events' => [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => ['*', 'comment', 'discussion', 'user'],
                ]
            ],
            'url:s' => 'The target URL of the webhook.',
            'secret:s' => 'The secret used to sign events associated with this webhook.',
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
