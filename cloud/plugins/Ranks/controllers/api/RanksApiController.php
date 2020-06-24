<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ClientException;
use Vanilla\ApiUtils;
use Vanilla\PermissionsTranslationTrait;
use Vanilla\Utility\CamelCaseScheme;
use Vanilla\Utility\DelimitedScheme;

/**
 * API Controller for the `/ranks` resource.
 */
class RanksApiController extends AbstractApiController {

    use PermissionsTranslationTrait;

    /** @var array A map of renamed ability fields. */
    private $abilitiesMap = [
        'ActivityLinks' => 'linksActivity',
        'Avatars' => 'avatar',
        'CommentLinks' => 'linksPosts',
        'ConversationLinks' => 'linksConversations',
        'EditContentTimeout' => 'editTimeout',
        'Locations' => 'location',
        'MeAction' => 'meActions',
        'PermissionRole' => 'roleID',
        'Signatures' => 'signature',
        'SignatureMaxNumberImages' => 'signatureImages',
        'SignatureMaxLength' => 'signatureLength',
        'Titles' => 'title'
    ];

    /** @var array A map of database-to-schema field names. */
    private $fieldMap = [
        'Body' => 'notificationBody',
        'Label' => 'userTitle'
    ];

    /** @var DelimitedScheme */
    private $nameScheme;

    /** @var PermissionModel */
    private $permissionModel;

    /** @var RankModel */
    private $rankModel;

    /** @var RoleModel */
    private $roleModel;

    /**
     * ReactionsApiController constructor.
     *
     * @param CamelCaseScheme
     * @param PermissionModel $permissionModel
     * @param RankModel $rankModel
     * @param RoleModel $roleModel
     */
    public function __construct(CamelCaseScheme $camelCaseScheme, PermissionModel $permissionModel, RankModel $rankModel, RoleModel $roleModel) {
        $this->permissionModel = $permissionModel;
        $this->rankModel = $rankModel;
        $this->roleModel = $roleModel;
        $this->nameScheme = new DelimitedScheme('.', $camelCaseScheme);
    }

    /**
     * Delete a rank.
     *
     * @param $id
     */
    public function delete($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema('in')->setDescription('Delete a rank.');
        $out = $this->schema([], 'out');

        // Make sure this rank even exists, before attempting to delete it.
        $this->rankByID($id);

        $this->rankModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available rank fields.
     *
     * @return Schema Returns a schema object.
     */
    private function fullSchema() {
        static $schema;

        if (!isset($schema)) {
            $schema = Schema::parse([
                'rankID' => 'Rank ID.',
                'name:s' => 'Name of the rank.',
                'userTitle:s' => 'Label that will display beside the user.',
                'level:i' => 'Level of the rank. Determines the sort order.',
                'notificationBody:s|n' => [
                    'minLength' => 0,
                    'description' => 'Message for the users when they earn this rank.'
                ],
                'cssClass:s|n?' => 'Custom CSS class for users of this rank.',
                'criteria:o?' => [
                    'points:i?' => 'User points.',
                    'time:s?' => 'Age of user account (e.g. 1 day, 3 weeks, 1 month).',
                    'posts:i?' => 'Total posts created by the user.',
                    'roleID:i?' => 'ID of a role required for this rank.',
                    'permission:s?' => [
                        'description' => 'Permission slug.',
                        'enum' => ['site.manage', 'community.moderate']
                    ],
                    'manual:b?' => 'Allow manually granting this rank.'
                ],
                'abilities:o?' => [
                    'discussionsAdd:b?' => [
                        'enum' => [false],
                        'description' => 'Allow starting discussions.'
                    ],
                    'commentsAdd:b?' => [
                        'enum' => [false],
                        'description' => 'Allow adding comments.'
                    ],
                    'conversationsAdd:b?' => 'Allow starting conversations.',
                    'verified:b?' => 'Verified status. true is verified. false requires verification.',
                    'format:s?' => [
                        'description' => 'Formatting restrictions.',
                        'enum' => ['Text', 'TextEx']
                    ],
                    'linksActivity:b?' => [
                        'enum' => [false],
                        'description' => 'Allow links in activity feed.'
                    ],
                    'linksConversations:b?' => [
                        'enum' => [false],
                        'description' => 'Allow links in conversations.'
                    ],
                    'linksPosts:b?' => [
                        'enum' => [false],
                        'description' => 'Allow links in posts.'
                    ],
                    'title:b?' => 'Allow user to have a title.',
                    'location:b?' => 'Allow user to have a location.',
                    'avatar:b?' => 'Allow user to have a custom avatar.',
                    'signature:b?' => 'Allow user to have a signature.',
                    'signatureImages:i?' => 'Maximum number of images in a signature. -1 for no limit.',
                    'signatureLength:i?' => 'Maximum length of a signature.',
                    'polls:b?' => 'Allow creation of polls.',
                    'meActions:b?' => 'Allow usage of "me actions".',
                    'curation:b?' => 'Allow content curation.',
                    'editTimeout:i?' => 'Length of time, in seconds, a user can edit their post. -1 for no limit.',
                    'roleID:i?' => 'Permissions of this role are applied to users with the rank.'
                ]
            ]);
        }

        return $schema;
    }

    /**
     * Get a single rank.
     *
     * @param $id
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema('in')->setDescription('Get a single rank.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->rankByID($id);

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a rank for editing.
     *
     * @param string $id
     * @return array
     */
    public function get_edit($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema('in')->setDescription('Get a rank for editing.');
        $out = $this->schema($this->fullSchema(), 'out');

        $row = $this->rankByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get an ID-only rank record schema.
     *
     * @param string $type
     * @return Schema
     */
    private function idParamSchema($type) {
        static $schema;

        if ($schema === null) {
            $schema = Schema::parse(['id:i' => 'The rank ID.']);
        }

        return $this->schema($schema, $type);
    }

    /**
     * Get a list of all ranks.
     *
     * @return  array
     */
    public function index() {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema([], 'in')->setDescription('Get a list of all ranks.');
        $out = $this->schema([':a' => $this->fullSchema()], 'out');

        $rows = RankModel::ranks();
        $rows = array_values($rows);

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
        }
        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get the legacy form of a permission.
     *
     * @param string $slug
     * @return bool
     */
    private function legacyPermission($permission) {
        $result = false;

        $permissions = $this->permissionModel->permissionColumns();
        unset($permissions['PermissionID']);
        $permissions = array_keys($permissions);

        foreach ($permissions as $slug) {
            $renamed = $this->renamePermission($slug);
            if ($permission === $renamed) {
                $result = $slug;
                break;
            }
        }

        if (!$result) {
            throw new ClientException('Invalid permission.', 400, ['permission' => $permission]);
        }

        return $result;
    }

    /**
     * Merge new data from input and existing rank row.
     *
     * @param array $input
     * @param array $row
     * @return array
     */
    private function mergeAttributes(array $input, array $row) {
        $row = ApiUtils::convertOutputKeys($row);

        /**
         * Ranks stores several fields (e.g. criteria, abilities) in its Attributes column. This method is intended to
         * be used for updating this column without destroying the rest of the fields it might contain.
         */
        $attributes = ['cssClass', 'criteria', 'abilities'];
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $input) && $input[$attribute] === null) {
                unset($input[$attribute]);
            } elseif (!array_key_exists($attribute, $input) && array_key_exists($attribute, $row)) {
                $input[$attribute] = $row[$attribute];
            }
        }

        return $input;
    }

    /**
     * Normalize a request to match database schema.
     *
     * @param array $input
     * @return array
     */
    public function normalizeInput(array $input) {
        // Rename fields.
        $fieldMap = array_flip($this->fieldMap);
        $input = $this->renameFields($input, $fieldMap);

        // Filter out empty values.
        $filterFields = ['criteria', 'abilities'];
        foreach ($filterFields as $filterField) {
            if (array_key_exists($filterField, $input)) {
                $input[$filterField] = array_filter($input[$filterField], function($value) {
                    $result = true;
                    if ($value === null) {
                        $result = false;
                    } elseif (is_string($value) && trim($value) === '') {
                        $result = false;
                    }
                    return $result;
                });
            }
        }

        if (array_key_exists('abilities', $input) && is_array($input['abilities'])) {
            $abilitiesMap = array_flip($this->abilitiesMap);
            $input['abilities'] = $this->renameFields($input['abilities'], $abilitiesMap);
            foreach ($input['abilities'] as $ability => &$abilityVal) {
                if ($abilityVal === false) {
                    $abilityVal = 'no';
                } elseif ($abilityVal === true) {
                    $abilityVal = 'yes';
                }
            }
        }

        if (!empty($input['criteria'])) {
            if ($permission = valr('criteria.permission', $input)) {
                $permissionParts = explode('.', $permission);
                // Only attempt to rename if this is a two-tiered permission slug.
                if (count($permissionParts) === 2) {
                    $permission = $this->legacyPermission($permission);
                    setvalr('criteria.permission', $input, $permission);
                }
            }
            if ($countPosts = valr('criteria.posts', $input)) {
                unset($input['criteria']['posts']);
                setvalr('criteria.countPosts', $input, $countPosts);
            }
            if ($roleID = valr('criteria.roleID', $input)) {
                $role = $this->roleByID($roleID);
                unset($input['criteria']['roleID']);
                setvalr('criteria.role', $input, $role['Name']);
            }
        }

        $result = ApiUtils::convertInputKeys($input);
        return $result;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $row Database record.
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $row) {
        // Rename fields.
        $row = $this->renameFields($row, $this->fieldMap);

        if (!array_key_exists('CssClass', $row)) {
            $row['CssClass'] = null;
        }
        if (!array_key_exists('Criteria', $row)) {
            $row['Criteria'] = [];
        }
        if (!array_key_exists('Abilities', $row)) {
            $row['Abilities'] = [];
        }

        if (!empty($row['Criteria'])) {
            if ($permission = valr('Criteria.Permission', $row)) {
                $permission = $this->renamePermission($permission);
                setvalr('Criteria.Permission', $row, $permission);
            }
            if ($posts = valr('Criteria.CountPosts', $row)) {
                unset($row['Criteria']['CountPosts']);
                setvalr('Criteria.Posts', $row, $posts);
            }
            if ($roleName = valr('Criteria.Role', $row)) {
                $role = $this->roleByName($roleName);
                setvalr('Criteria.RoleID', $row, $role['RoleID']);
            }
        }

        if (!empty($row['Abilities'])) {
            if ($signatureImages = valr('Abilities.SignatureMaxNumberImages', $row)) {
                if ($signatureImages === 'None') {
                    setvalr('Abilities.SignatureMaxNumberImages', $row, 0);
                } elseif ($signatureImages === 'Unlimited') {
                    setvalr('Abilities.SignatureMaxNumberImages', $row, -1);
                }
            }
            $row['Abilities'] = $this->renameFields($row['Abilities'], $this->abilitiesMap);
        }

        $result = ApiUtils::convertOutputKeys($row);
        return $result;
    }

    /**
     * Edit a rank.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch($id, array $body) {
        $this->permission(['Garden.Settings.Manage', 'Garden.Users.Edit']);

        $in = $this->schema($this->postSchema(), 'in')
            ->setDescription('Edit a rank.')
            ->addValidator('criteria', [$this, 'validateCriteria']);
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body, true);
        $rank = $this->rankByID($id);
        $body['rankID'] = $id;
        $body = $this->mergeAttributes($body, $rank);
        $body = $this->normalizeInput($body);

        $rankID = $this->rankModel->save($body);
        $this->validateModel($this->rankModel);

        $rank = $this->rankByID($rankID);
        $rank = $this->normalizeOutput($rank);
        $result = $out->validate($rank);
        return $result;
    }

    /**
     * Add a new rank.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->postSchema(), 'in')
            ->setDescription('Add a new rank.')
            ->addValidator('criteria', [$this, 'validateCriteria']);
        $out = $this->schema($this->fullSchema(), 'out');

        $body = $in->validate($body);
        $body = $this->normalizeInput($body);

        $rankID = $this->rankModel->save($body);
        $this->validateModel($this->rankModel);

        $rank = $this->rankByID($rankID);
        $rank = $this->normalizeOutput($rank);
        $result = $out->validate($rank);
        return $result;
    }

    /**
     * Get a schema for rank write operations.
     *
     * @return Schema
     */
    private function postSchema() {
        static $schema;

        if ($schema === null) {
            $schema = $this->schema(Schema::parse([
                'name', 'userTitle', 'level', 'notificationBody?', 'cssClass?', 'criteria?', 'abilities?'
            ])->add($this->fullSchema()), 'RankPost');
        }

        return $schema;
    }

    /**
     * Get a rank by its numeric ID.
     *
     * @param int $id The ID of a rank.
     * @throws NotFoundException If the rank could not be found.
     * @return array
     */
    public function rankByID($id) {
        $row = $this->rankModel->getID($id);
        if (!$row) {
            throw new NotFoundException('Rank');
        }
        return $row;
    }

    /**
     * Rename fields.
     *
     * @param array $row
     * @param array $map
     * @return array
     */
    private function renameFields(array $row, array $map) {
        foreach ($map as $oldField => $newField) {
            if (array_key_exists($oldField, $row)) {
                $row[$newField] = $row[$oldField];
                unset($row[$oldField]);
            }
        }
        return $row;
    }

    /**
     * Get a role by its ID.
     *
     * @param $name
     * @return array
     */
    private function roleByID($roleID) {
        $result = $this->roleModel->getID($roleID, DATASET_TYPE_ARRAY);
        if (empty($result)) {
            throw new NotFoundException('Role');
        }
        return $result;
    }

    /**
     * Get a role by its name.
     *
     * @param $name
     * @return array
     */
    private function roleByName($name) {
        $role = RoleModel::getByName($name);
        if (empty($role)) {
            throw new NotFoundException('Role');
        }
        $result = reset($role);
        return $result;
    }

    /**
     * Validate rank criteria.
     *
     * @param array $data
     * @param ValidationField $field
     */
    public function validateCriteria(array $criteria, ValidationField $field) {
        $result = true;

        // Ensure rank types (automatic and manual) aren't being mixed.
        if (array_key_exists('manual', $criteria) && $criteria['manual']) {
            $criteriaCopy = $criteria;
            unset($criteriaCopy['manual']);
            if (count($criteriaCopy)) {
                $field->getValidation()->addError(
                    'manual',
                    'A manually-assignable rank cannot include additional criteria.',
                    ['path' => $field->getName()]
                );
            }
        } else {
            if (array_key_exists('time', $criteria)) {
                $time = strtotime($criteria['time'], 0);
                if ($time === false) {
                    $field->getValidation()->addError(
                        'time',
                        'Invalid time.',
                        ['path' => $field->getName()]
                    );
                }
            }
            if (array_key_exists('roleID', $criteria)) {
                $role = $this->roleModel->getID($criteria['roleID']);
                if ($role === false) {
                    $field->getValidation()->addError(
                        'roleID',
                        'Invalid role',
                        ['path' => $field->getName()]
                    );
                }
            }
        }

        return $result;
    }
}
