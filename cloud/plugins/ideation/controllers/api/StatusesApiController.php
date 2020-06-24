<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Garden\Schema\Schema;
use Vanilla\ApiUtils;

/**
 * API Controller for the `/statuses` resource.
 */
class StatusesApiController extends AbstractApiController {

    /** @var StatusModel */
    private $statusModel;

    /**
     * StatusesApiController constructor.
     *
     * @param StatusModel $statusModel
     */
    public function __construct(StatusModel $statusModel) {
        $this->statusModel = $statusModel;
    }

    /**
     * Delete an idea status.
     *
     * @param int $id
     * @throws ClientException If the status cannot be deleted.
     */
    public function delete($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->idParamSchema(), 'in')->setDescription('Delete an idea status.');
        $out = $this->schema([], 'out');

        $this->statusByID($id);
        $status = $this->statusModel->getID($id);

        if ($status->IsDefault === 1) {
            throw new ClientException('The default status cannot be deleted.', 400);
        }
        $this->statusModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available fields.
     *
     * @return Schema
     */
    public function fullSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = $this->schema([
                'statusID:i' => 'Unique numeric ID of a status.',
                'name:s' => 'Label for the status.',
                'state:s' => [
                    'description' => 'The open/closed state of an idea.',
                    'enum' => ['Open', 'Closed']
                ],
                'tagID:i' => 'Unique numeric ID of the associated tag.',
                'isDefault:b' => 'Is this the default status?'
            ], 'Status');
        }

        return $schema;
    }

    /**
     * Get a single idea status.
     *
     * @param int $id
     * @return array
     */
    public function get($id) {
        $this->permission('Vanilla.Moderation.Manage');

        $in = $this->schema($this->idParamSchema(), 'in')->setDescription('Get a single idea status.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->statusByID($id);

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get editable fields for an idea status.
     *
     * @param int $id
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->idParamSchema(), 'in')->setDescription('Get editable fields for an idea status.');
        $out = $this->schema(Schema::parse([
            'statusID',
            'name',
            'state',
            'isDefault',
        ])->add($this->fullSchema()), 'out');

        $row = $this->statusByID($id);

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only schema.
     *
     * @return Schema
     */
    public function idParamSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse(['id:i' => 'The idea status ID.']);
        }

        return $schema;
    }

    /**
     * Get a list of idea statuses.
     *
     * @return array
     */
    public function index() {
        $this->permission('Vanilla.Moderation.Manage');

        $in = $this->schema([], 'in')->setDescription('Get a list of idea statuses.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $rows = $this->statusModel->getStatuses();
        // Need an normal indexed array. StatusModel indexes by StatusID.
        $rows = array_values($rows);

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Normalize idea status API request fields for model consumption.
     *
     * @param array $fields
     * @return array
     */
    public function normalizeInput(array $fields) {
        if (array_key_exists('isDefault', $fields)) {
            $fields['isDefault'] = $fields['isDefault'] ? 1 : 0;
        }

        $fields = ApiUtils::convertInputKeys($fields);
        return $fields;
    }

    /**
     * Normalize idea status fields for an API response.
     *
     * @param array $row An idea status row.
     * @return array
     */
    public function normalizeOutput(array $row) {
        return $row;
    }

    /**
     * Update an idea status.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->postSchema(), 'in')->setDescription('Update an idea status.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body, true);
        $body = $this->normalizeInput($body);

        // To use StatusModel::upsert, we need all fields. Populate the missing fields with existing data.
        $status = $this->statusByID($id);
        $body = array_merge($status, $body);

        $this->statusModel->upsert($body['Name'], $body['State'], $body['IsDefault'], $id);
        $this->validateModel($this->statusModel);

        $row = $this->statusByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Create an idea status.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->postSchema(), 'in')->setDescription('Create an idea status.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $body = $this->normalizeInput($body);

        $statusID = $this->statusModel->upsert($body['Name'], $body['State'], $body['IsDefault']);
        $this->validateModel($this->statusModel);
        if (!$statusID) {
            throw new ServerException('Unable to add status.');
        }

        $row = $this->statusByID($statusID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a subset of the full schema for adds and edits.
     *
     * @return Schema
     */
    public function postSchema() {
            static $schema;

            if (!isset($schema)) {
                $schema = Schema::parse([
                    'name',
                    'state',
                    'isDefault'
                ])->add($this->fullSchema());
            }

            return $schema;
    }

    /**
     * Get a status by its unique numeric ID.
     *
     * @param int $id Identifier of the record.
     * @throws NotFoundException if the record does not exists.
     * @return array
     */
    public function statusByID($id) {
        $row = $this->statusModel->getID($id, DATASET_TYPE_ARRAY);
        if (!$row) {
            throw new NotFoundException('Status');
        }
        return $row;
    }
}
