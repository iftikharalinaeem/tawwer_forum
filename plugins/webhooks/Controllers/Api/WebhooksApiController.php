<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;

/**
 * WebhooksApiController for the `/webhooks` resource.
 */
class WebhooksApiController extends AbstractApiController {

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
        $this->permission();
        $in = $this->schema([], 'in')->setDescription('Get a list of all the webhooks.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');
        $rows = WebhookModel::webhooks();
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a webhook.
     *
     * @param int $id The ID of the webhook.
     * @param array $query The request query.
     * @return array
     */
    public function get($id, array $query) {
        $this->permission();

        $this->idParamSchema();
        $in = $this->schema([], ['WebhookGet', 'in'])->setDescription('Get a webhook.');
        $out = $this->schema($this->webhookSchema(), 'out');
        $query = $in->validate($query);
        $webhook = $this->webhookByID($id);
        $result = $out->validate($webhook);

        return $result;
    }

    /**
     * Add a webhook.
     *
     * @param array $body The request body.
     * @throws ServerException If the webhook could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.SignIn.Allow');
        $in = $this->webhookPostSchema('in')->setDescription('Add a webhook.');
        $out = $this->webhookSchema('out');

        $body = $in->validate($body);
        $id = $this->webhookModel->save($body);
        $this->validateModel($this->webhookModel);
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
     */
    public function delete($id) {
        $this->permission('Garden.Moderation.Manage');
        $in = $this->idParamSchema()->setDescription('Delete a webhook.');
        $out = $this->schema([], 'out');
        $this->webhookModel->deleteID($id);
    }

    /**
     * Update a webhook.
     *
     * @param int $id The ID of the webhook.
     * @param array $body The request body.
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $this->idParamSchema('in');
        $in = $this->webhookPostSchema('in')->setDescription('Update a webhook.');
        $out = $this->webhookSchema('out');

        $webhookData = $in->validate($body, true);
        $webhookData['webhookID'] = $id;
        $row = $this->webhookByID($id);
        if ($row['insertUserID'] !== $this->getSession()->UserID) {
            $this->permission('Garden.Moderation.Manage');
        }
        $this->webhookModel->save($webhookData);
        $this->validateModel($this->webhookModel);
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
    public function webhookByID($id) {
        $row = $this->webhookModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Webhook');
        }
        return $row;
    }

    /**
     * Get a webhook schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function webhookPostSchema($type = '') {
        if ($this->webhookPostSchema === null) {
            $this->webhookPostSchema = $this->schema(
                Schema::parse([
                    'active',
                    'events',
                    'name:s' => 'The name of the webhook',
                    'url:s' => 'The target URL of the webhook.',
                    'secret:s' => 'The secret used to sign events associated with this webhook.',
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
    public function webhookSchema($type = '') {
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
    protected function fullSchema() {
        return Schema::parse([
            'webhookID:i' => 'The webhook identifier.',
            'active:i' => 'Whether or not this webhook will send events.',
            'name:s' => 'User-friendly name.',
            'events:s|n' => [
                'description' => 'Events to be forwarded to this webhook.',
                'minLength' => 0
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
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse(['id:i' => 'The webhook ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }
}
