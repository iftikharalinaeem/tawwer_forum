<?php
/**
 * API controller for the `/rules` resource.
 *
 * @author Patrick Desjardins <patrick.d@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 */

use Vanilla\ApiUtils;
use Garden\Web\Exception\ServerException;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;

/**
 * Class RuleApiController
 */
class RulesApiController extends AbstractApiController {

    /** @var RuleModel */
    private $ruleModel;

    /** @var UserModel */
    private $userModel;

    /** @var Schema */
    private $ruleSchema;

    /**
     * RulesApiController constructor.
     *
     * @param RuleModel $ruleModel
     * @param UserModel $userModel
     */
    public function __construct(RuleModel $ruleModel, UserModel $userModel) {
        $this->ruleModel = $ruleModel;
        $this->userModel = $userModel;
    }

    /**
     * Delete a rule.
     *
     * @param int $id
     */
    public function delete($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema(['id:i' => 'The rule ID.'], 'in')->setDescription('Delete a rule.');
        $in->validate(['id' => $id]);

        $this->ruleModel->delete(['RuleID' => $id]);
    }

    /**
     * Get a rule by id.
     *
     * @param mixed $ruleID
     * @return array
     */
    public function get($ruleID) {
        $this->permission();

        $this->idParamRuleSchema()->validate(['id' => $ruleID]);
        $out = $this->schema($this->fullSchema(), 'out');
        $rule = $this->getRuleByID($ruleID);

        $this->userModel->expandUsers($rule, ['insertUser' => 'InsertUserID', 'updateUser' => 'UpdateUserID']);

        return $out->validate($this->normalizeOutput($rule));
    }

    /**
     * Get the ID input schema.
     *
     * @return Schema
     */
    private function idParamRuleSchema() {
        return $this->schema(['id:i' => 'The rule ID.'], 'in');
    }

    /**
     * Get a list of Rules.
     *
     * @param array $query
     * @return mixed
     */
    public function index(array $query) {
        $this->permission();

        $in = $this->schema(['expand?' => ApiUtils::getExpandDefinition(['insertUser', 'updateUser'])], 'in');
        $out = $this->schema([':a' => $this->ruleSchema()], 'out');

        $query = $in->validate($query);

        $rows = $this->ruleModel->get();

        $this->userModel->expandUsers(
            $rows,
            $this->resolveExpandFields($query, ['insertUser' => 'InsertUserID', 'updateUser' => 'UpdateUserID'])
        );

        // Normalize output of every records.
        $rows = array_map([$this, 'normalizeOutput'], $rows);

        return $out->validate($rows);
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @return array Return a database record.
     */
    public function normalizeInput(array $schemaRecord): array {
        return ApiUtils::convertInputKeys($schemaRecord);
    }

    /**
     * Update a rule.
     *
     * @param int $id The ID of the rule to update.
     * @param array $body The request body.
     * @return array
     * @throws ServerException If the rule could not be updated.
     */
    public function patch($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->postRuleSchema()->setDescription('Update a rule.');
        $out = $this->schema($this->fullSchema(), 'out');

        $this->idParamRuleSchema()->validate(['id' => $id], true);
        $this->getRuleByID($id);

        $body = $this->normalizeInput($in->validate($body, true));

        try {
            $this->ruleModel->update($body, ['RuleID' => $id]);
        } catch (Exception $exception) {
            throw new ServerException('Unable to update rule.', 500);
        }

        $this->validateModel($this->ruleModel);
        $rule = $this->ruleModel->getID($id);

        return $out->validate($this->normalizeOutput($rule));
    }

    /**
     * Create a rule.
     *
     * @param array $body The request body.
     * @return array
     * @throws ServerException If the rule could not be created.
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->postRuleSchema()->setDescription('Create a rule.');
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $rule = $this->normalizeInput($body);

        try {
            $id = $this->ruleModel->insert($rule);
        } catch (Exception $exception) {
            throw new ServerException('Unable to create rule.', 500);
        }

        $this->validateModel($this->ruleModel);
        $rule = $this->ruleModel->getID($id);

        return $out->validate($this->normalizeOutput($rule));
    }

    /**
     * Get a rule schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function postRuleSchema() {
        $schema = $this->schema(
            Schema::parse([
                'name' => [
                    'maxLength' => 255,
                ],
                'description' => [
                    'maxLength' => 500,
                ],
            ])->add($this->fullSchema()),
            'RulePost'
        );
        return $this->schema($schema, 'in');
    }

    /**
     * Get the full rule schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function ruleSchema($type = '') {
        if ($this->ruleSchema === null) {
            $this->ruleSchema = $this->schema($this->fullSchema(), 'Rule');
        }
        return $this->schema($this->ruleSchema, $type);
    }

    /**
     * Get a schema instance comprised of all available rule fields.
     *
     * @return Schema Returns a schema object.
     */
    private function fullSchema() {
        return Schema::parse([
            'ruleID:i' => 'The ID of the rule.',
            'name:s' => 'The name of the rule.',
            'description:s|n' => [
                'description' => 'The description of the rule.',
                'minLength' => 0,
            ],
            'dateInserted:dt' => 'When the rule was created.',
            'DateUpdated:dt|n' => 'When the rule was last updated.',
            'insertUserID:i' => 'The user that created the rule.',
            'insertUser?' => $this->getUserFragmentSchema(),
            'updateUserID:i|n' => 'The user that last edited the rule.',
            'updateUser?' => $this->getUserFragmentSchema()
        ]);
    }

    /**
     * Get a rule by ID.
     *
     * @param int $id
     * @return array
     * @throws NotFoundException If the rule was not found.
     */
    private function getRuleByID(int $id): array {
        try {
            return $this->ruleModel->getID($id);
        } catch (Exception $exception) {
            throw new NotFoundException('Rule');
        }
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|false $expand
     * @return array Return a Schema record.
     */
    protected function normalizeOutput(array $dbRecord, array $expand = []): array {
        return ApiUtils::convertOutputKeys($dbRecord);
    }
}
