<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Knowledge\Models\KnowledgeUniversalSourceModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Site\TranslationModel;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use LocalesApiController;
use PermissionModel;

/**
 * Endpoint for the knowledge base resource.
 */
class KnowledgeBasesApiController extends AbstractApiController {
    use KnowledgeBasesApiSchemes;
    use KnowledgeNavigationApiSchemes;
    use CheckGlobalPermissionTrait;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var KnowledgeNavigationApiController */
    private $knowledgeNavigationApi;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var KnowledgeUniversalSourceModel */
    private $knowledgeUniversalSourceModel;

    /** @var TranslationProviderInterface $translation */
    private $translation;

    /** @var LocalesApiController $localeApi */
    private $localeApi;

    /** @var PermissionModel $permissionModel */
    private $permissionModel;

    /** @var \RoleModel $roleModel */
    private $roleModel;

    /** @var array $allAllowed All knowledge base id allowed  */
    private $allAllowed = [];

    /**
     * KnowledgeBaseApiController constructor.
     *
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param KnowledgeNavigationApiController $knowledgeNavigationApi
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param SiteSectionModel $siteSectionModel
     * @param TranslationModel $translationModel
     * @param LocalesApiController $localeApi
     * @param PermissionModel $permissionModel
     * @param \RoleModel $roleModel
     * @param KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel
     */
    public function __construct(
        KnowledgeBaseModel $knowledgeBaseModel,
        KnowledgeNavigationApiController $knowledgeNavigationApi,
        KnowledgeCategoryModel $knowledgeCategoryModel,
        SiteSectionModel $siteSectionModel,
        TranslationModel $translationModel,
        LocalesApiController $localeApi,
        PermissionModel $permissionModel,
        \RoleModel $roleModel,
        KnowledgeUniversalSourceModel $knowledgeUniversalSourceModel
    ) {
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->knowledgeNavigationApi = $knowledgeNavigationApi;
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->siteSectionModel = $siteSectionModel;
        $this->translation = $translationModel->getContentTranslationProvider();
        $this->localeApi = $localeApi;
        $this->permissionModel = $permissionModel;
        $this->roleModel = $roleModel;
        $this->knowledgeUniversalSourceModel = $knowledgeUniversalSourceModel;
    }

    /**
     * Get a single knowledge base.
     *
     * @param int $id
     * @param array $query
     *
     * @return array
     */
    public function get(int $id, array $query = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $in = $this->schema($this->idParamSchema(), 'in');
        $query['id'] = $id;
        $query = $in->validate($query);
        $out = $this->schema($this->fullSchema(), "out");

        $expandUniversalTargets = $this->isExpandField("universalTargets", $query["expand"]);
        $expandUniversalSources = $this->isExpandField("universalSources", $query["expand"]);
        unset($query["expand"]);


        $locale = $query['locale'] ?? null;
        $row = $this->knowledgeBaseByID($id);
        $this->knowledgeBaseModel->checkViewPermission($row['knowledgeBaseID']);
        
        if ($locale) {
            $row['locale'] = $locale;
            $rows = $this->translateProperties([$row], $locale);
            $row = reset($rows);
        }

        if ($expandUniversalTargets) {
            $this->knowledgeUniversalSourceModel->expandKnowledgeBase($row, "universalTargets");
            if (($row["universalTargets"] ?? null) && $locale) {
                $row["universalTargets"] = $this->translateContentUniversalKBs($row["universalTargets"], $locale);
            }
        }
        if ($expandUniversalSources) {
            $this->knowledgeUniversalSourceModel->expandKnowledgeBase($row, "universalSources");
            if (($row["universalSources"] ?? null) && $locale) {
                $row["universalSources"] = $this->translateContentUniversalKBs($row["universalSources"], $locale);
            }
        }

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Get a knowledge base by it's URL code.
     *
     * @param array $query
     *
     * @return mixed
     * @throws NotFoundException If the knowledge base could not be found.
     * @throws ValidationException If the input or output was invalid.
     * @throws \Garden\Web\Exception\HttpException If the user has been banned.
     * @throws \Vanilla\Exception\PermissionException If the user did not have proper permission to view the resource.
     */
    public function get_byUrlCode(array $query) {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        // Schema
        $in = $this->schema(Schema::parse([
            'urlCode',
        ])->add($this->fullSchema()), "in")->setDescription('Get a knowledge base, using its urlCode.');
        $out = $this->schema($this->fullSchema(), 'out');
        $query = $in->validate($query);

        // Data fetching
        $urlCode = $query['urlCode'];
        $row = $this->knowledgeBaseModel->get(['urlCode' => $urlCode])[0] ?? null;
        if (!$row) {
            throw new NotFoundException('KnowledgeBase');
        } else {
            $this->knowledgeBaseModel->checkViewPermission($row['knowledgeBaseID']);
        }

        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * List knowledge bases.
     *
     * @param array $query
     * @return array
     */
    public function index(array $query = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $in = $this->schema([
            "status" => [
                "default" => KnowledgeBaseModel::STATUS_PUBLISHED,
            ],
            "sourceLocale?",
            "locale?",
            "siteSectionGroup?",
            "expand?" => \Vanilla\ApiUtils::getExpandDefinition(["siteSections", "universalTargets", "universalSources"]),
        ])->add($this->getKnowledgeBaseSchema())->setDescription("List knowledge bases.");

        $out = $this->schema([":a" => $this->fullSchema()], "out");

        $query = $in->validate($query);

        // Get the expand params and then remove from the query.
        $expandUniversalTargets = $this->isExpandField("universalTargets", $query["expand"]);
        $expandUniversalSources = $this->isExpandField("universalSources", $query["expand"]);
        $expandSiteSections = $this->isExpandField('siteSections', $query['expand']);
        unset($query["expand"]);


        if (array_key_exists("siteSectionGroup", $query) && $query['siteSectionGroup'] === 'all') {
            unset($query['siteSectionGroup']);
        }

        $translateLocale = $query['locale'] ?? null;
        unset($query['locale']);

        $rows = $this->knowledgeBaseModel->get($this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission($query));
        if (isset($translateLocale)) {
            $rows = $this->translateProperties($rows, $translateLocale);
        }


        $rows = array_map(function ($row) use (
            $expandSiteSections,
            $translateLocale,
            $expandUniversalTargets,
            $expandUniversalSources
        ) {
            if ($expandSiteSections) {
                $this->expandSiteSections($row);
            }
            if (isset($translateLocale)) {
                $row['locale'] = $translateLocale;
            }
            if ($expandUniversalTargets) {
                $this->knowledgeUniversalSourceModel->expandKnowledgeBase($row, "universalTargets");
                if (($row["universalTargets"] ?? null) && $translateLocale) {
                    $row["universalTargets"] = $this->translateContentUniversalKBs($row["universalTargets"], $translateLocale);
                }
            }
            if ($expandUniversalSources) {
                $this->knowledgeUniversalSourceModel->expandKnowledgeBase($row, "universalSources");
                if (($row["universalSources"] ?? null) && $translateLocale) {
                    $row["universalSources"] = $this->translateContentUniversalKBs($row["universalSources"], $translateLocale);
                }
            }
            return $this->normalizeOutput($row);
        }, $rows);

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Translate properties (name, description) of knowledge base records
     *
     * @param array $rows Array of knowledgeBase records
     * @param string $locale Locale to translate properties to
     * @return array
     */
    private function translateProperties(array $rows, string $locale): array {
        if (!is_null($this->translation)) {
            $rows = $this->translation->translateProperties(
                $locale,
                'kb',
                KnowledgeBaseModel::RECORD_TYPE,
                KnowledgeBaseModel::RECORD_ID_FIELD,
                $rows,
                ['name', 'description']
            );
        }
        return $rows;
    }

    /**
     * Expand the site sections on a knowledge base record.
     *
     * @param array $row
     */
    public function expandSiteSections(array &$row) {
        $siteSections = $this->siteSectionModel->getForSectionGroup($row['siteSectionGroup']);
        $row['siteSections'] = $siteSections;
    }

    /**
     * POST new Knowledge Base
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array {
        $this->permission("Garden.Settings.Manage");
        $in = $this->schema($this->knowledgeBasePostSchema())
            ->addValidator("siteSectionGroup", [$this->knowledgeBaseModel, "validateSiteSectionGroup"])
            ->addValidator("viewers", [$this, "validateRoleID"])
            ->addValidator("editors", [$this, "validateRoleID"])
            ->setDescription("Create a new knowledge base.")
        ;
        $in = $this->applyUrlCodeValidator($in);
        $this->applySortTypeValidator($in);
        $this->applyIsUniversalSourceValidation($in);
        $out = $this->schema($this->fullSchema(), "out");
        $body = $in->validate($body);

        $body['hasCustomPermission'] = $body['hasCustomPermission'] !== false ? 1 : 0;

        if (array_key_exists('isUniversalSource', $body)) {
            $body['isUniversalSource'] = ($body['isUniversalSource'] === true) ? 1 : 0;
        }
        $knowledgeBaseID = $this->knowledgeBaseModel->insert($body);

        $knowledgeCategoryID = $this->knowledgeCategoryModel->insert([
            'name' => $body['name'],
            'knowledgeBaseID' => $knowledgeBaseID,
            'parentID' => -1,
        ]);
        $update = ['rootCategoryID' => $knowledgeCategoryID];
        if ($body['hasCustomPermission'] ?? false) {
            $update['permissionKnowledgeBaseID'] = $knowledgeBaseID;
        }
        $this->knowledgeBaseModel->update($update, ['knowledgeBaseID' => $knowledgeBaseID]);

        if ($body['hasCustomPermission'] ?? false) {
            $this->saveCustomPermissions($knowledgeBaseID, $body['viewRoleIDs'] ?? [], $body['editRoleIDs'] ?? []);
        }

        $this->knowledgeUniversalSourceModel->setUniversalContent($body, $knowledgeBaseID);

        $row = $this->knowledgeBaseByID($knowledgeBaseID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Validator for 'viewers' and 'editors' field
     *
     * @param array $roleIDs
     * @param ValidationField $validationField
     * @return bool
     */
    public function validateRoleID(array $roleIDs, \Garden\Schema\ValidationField $validationField): bool {
        $roles = array_column($this->roleModel::roles(), 'RoleID');
        $valid = true;
        foreach ($roleIDs as $roleID) {
            if (!in_array($roleID, $roles)) {
                $validationField->getValidation()->addError(
                    $validationField->getName(),
                    "Invalid role id: ".$roleID
                );
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Save custom permissions
     *
     * @param int $knowledgeBaseID
     * @param array $viewers
     * @param array $editors
     */
    public function saveCustomPermissions(int $knowledgeBaseID, array $viewers = [], array $editors = []) {
        $roles = array_merge($viewers, $editors);
        $permissions =  [];
        foreach ($roles as $roleID) {
            $permissions[] = [
                'RoleID' => $roleID,
                'JunctionTable' => 'knowledgeBase',
                'JunctionColumn' => 'permissionKnowledgeBaseID',
                'JunctionID' => $knowledgeBaseID,
                knowledgeBaseModel::VIEW_PERMISSION => array_search($roleID, $viewers) === false ? 0 : 1,
                knowledgeBaseModel::EDIT_PERMISSION => array_search($roleID, $editors) === false ? 0 : 1
            ];
        }
        $this->permissionModel->saveAll($permissions, ['JunctionID' => $knowledgeBaseID, 'JunctionTable' => 'knowledgeBase']);
    }

    /**
     * Invoke universal source validation into schema
     *
     * @param Schema $schema
     * @param integer $recordID
     */
    private function applyIsUniversalSourceValidation(Schema $schema, int $recordID = null) {
        $schema->addValidator(
            "",
            function (array $data, ValidationField $validationField) use ($recordID) {
                return $this->knowledgeBaseModel->validateIsUniversalSource($data, $validationField, $recordID);
            }
        );
    }

    /**
     * Get a knowledge base for editing.
     *
     * @param int $id
     * @return array
     */
    public function get_edit(int $id): array {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema()->setDescription("Get a knowledge base for editing.");
        $out = $this->schema(Schema::parse([
            'knowledgeBaseID',
            'name',
            'siteSectionGroup',
            'description',
            'viewType',
            'icon',
            'bannerImage',
            'sortArticles',
            'sourceLocale',
            'urlCode'
        ])->add($this->fullSchema()), "out");

        $row = $this->knowledgeBaseByID($id);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Proxy GET method for KnowledgeNavigationApiController->tree.
     *
     * @param int $id
     * @param array $query
     * @return array
     */
    public function get_navigationTree(int $id, array $query = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $this->idParamSchema();
        $in = $this->schema($this->defaultSchema(), "in")
            ->setDescription("Get a navigation-friendly category hierarchy tree mode.");
        $query = $in->validate($query);

        //check if kb exists and status is not deleted
        $kb = $this->knowledgeBaseByID($id, false);
        $this->knowledgeBaseModel->checkViewPermission($kb['knowledgeBaseID']);
        $query['knowledgeBaseID'] = $id;
        $out = $this->schema([":a" => $this->schemaWithChildren()], "out");
        $rows = $this->knowledgeNavigationApi->tree($query);
        $result = $out->validate($rows);
        return $result ?? [];
    }

    /**
     * Proxy GET method for KnowledgeNavigationApiController->flat.
     *
     * @param int $id
     * @param array $query
     * @return array
     */
    public function get_navigationFlat(int $id, array $query = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $this->idParamSchema();
        $in = $this->schema($this->defaultSchema(), "in")
            ->setDescription("Get a navigation-friendly category hierarchy flat mode.");
        $query = $in->validate($query);
        //check if kb exists and status is not deleted
        $kb = $this->knowledgeBaseByID($id, false, true);

        $query['knowledgeBaseID'] = $id;
        $out = $this->schema([":a" => $this->categoryNavigationFragment()], "out");
        $rows = $this->knowledgeNavigationApi->flat($query);
        $result = $out->validate($rows);

        return $result;
    }

    /**
     * Proxy PATCH method for KnowledgeNavigationApiController->patchFlat.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch_navigationFlat(int $id, array $body = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);
        $this->idParamSchema();
        $patchSchema = Schema::parse([
            ":a" => Schema::parse([
                "recordType",
                "recordID",
                "parentID",
                "sort",
            ])->add(Schema::parse($this->knowledgeNavigationApi->getFragmentSchema()))
        ]);
        $in = $this->knowledgeNavigationApi->schema($patchSchema, "in")
            ->setDescription("Update the navigation structure of a knowledge base, using the flat format.");
        $out = $this->knowledgeNavigationApi->schema(
            [":a" => $this->knowledgeNavigationApi->categoryNavigationFragment()],
            "out"
        );

        // Prep the input.
        $body = $in->validate($body);

        //check if kb exists and status is not deleted
        $kb = $this->knowledgeBaseByID($id, false);
        $this->knowledgeBaseModel->checkViewPermission($kb['knowledgeBaseID']);

        $navigation = $this->knowledgeNavigationApi->patchFlat($id, $body);

        $result = $out->validate($navigation);
        return $result;
    }

    /**
     * Update an existing knowledge base.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch(int $id, array $body = []): array {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema();
        $in = $this->schema($this->knowledgeBasePostSchema())
            ->addValidator("siteSectionGroup", [$this->knowledgeBaseModel, "validateSiteSectionGroup"])
            ->addValidator("viewers", [$this, "validateRoleID"])
            ->addValidator("editors", [$this, "validateRoleID"])
            ->setDescription("Update an existing knowledge base.")
        ;
        $in = $this->applyUrlCodeValidator($in, $id);
        $this->applySortTypeValidator($in, $id);
        $this->applyIsUniversalSourceValidation($in, $id);

        $out = $this->schema($this->fullSchema(), "out");

        $body = $in->validate($body, true);
        $body['hasCustomPermission'] = ($body['hasCustomPermission'] ?? false) !== false ? 1 : 0;

        $prevState = $this->knowledgeBaseByID($id);

        if ((isset($body['hasCustomPermission']) && $prevState['hasCustomPermission'] !== $body['hasCustomPermission'])
            || (isset($body['hasCustomPermission']) && $body['hasCustomPermission'] && (isset($body['viewers']) || isset($body['editors'])))
        ) {
            if ($body['hasCustomPermission'] === 1) {
                $body['permissionKnowledgeBaseID'] = $id;
                $viewers = $body['viewers'] ?? [];
                $editors = $body['editors'] ?? [];
            } else {
                $body['permissionKnowledgeBaseID'] = -1;
                $viewers = [];
                $editors = [];
            }
            $this->saveCustomPermissions($id, $viewers, $editors);
        }

        $isUniversal =  $body['isUniversalSource'] ?? $prevState['isUniversalSource'] ?? false;

        if (array_key_exists('isUniversalSource', $body)) {
            $body['isUniversalSource'] = ($body['isUniversalSource'] === true) ? 1 : 0;
        }

        $universalTargetIDs = $body['universalTargetIDs'] ?? null;

        $this->knowledgeBaseModel->update($body, ["knowledgeBaseID" => $id]);
        if (isset($body['name']) && $prevState['name'] !== $body['name']) {
            $this->knowledgeCategoryModel->update(
                ['name' => $body['name']],
                ['knowledgeCategoryID' => $prevState['rootCategoryID']]
            );
        }

        if ($isUniversal) {
            $this->knowledgeUniversalSourceModel->setUniversalContent($body, $id);
        } elseif ($prevState['isUniversalSource'] && !$isUniversal) {
            $this->knowledgeUniversalSourceModel->delete(["sourceKnowledgeBaseID" => $id]);
        }

        // Check if KB status changed: deleted vs published
        if (isset($body['status']) && ($body['status'] !== $prevState['status'])
            || (isset($body['siteSectionGroup']) && $prevState['siteSectionGroup'] !== $body['siteSectionGroup'])) {
            // If status or siteSectionGroup changed we need to reset Sphinx counters and reindex
            $this->knowledgeBaseModel->resetSphinxCounters();
        }

        $row = $this->knowledgeBaseByID($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);

        return $result;
    }

    /**
     * Add validator for sorting.
     *
     * @param Schema $schema
     * @param integer $recordID
     */
    private function applySortTypeValidator(Schema $schema, int $recordID = null) {
        $schema->addValidator(
            "",
            function (array $data, ValidationField $validationField) use ($recordID) {
                return $this->knowledgeBaseModel->validateSortArticles($data, $validationField, $recordID);
            }
        );
    }

    /**
     * Apply {@link KnowledgeBasedApiController::validateUniqueUrlCode} to a Schema object.
     *
     * @param Schema $schema The schema to apply to.
     * @param int|null $recordID The existing ID of the current record if applicable.
     *
     * @return Schema
     */
    private function applyUrlCodeValidator(Schema $schema, int $recordID = null) {
        return $schema->addValidator(
            'urlCode',
            function (string $urlCode, ValidationField $validationField) use ($recordID) {
                return $this->validateUniqueUrlCode($urlCode, $validationField, $recordID);
            }
        );
    }

    /**
     * Validate that a url code is unique.
     *
     * @param string $urlCode The code to check.
     * @param ValidationField $validationField The validation field to apply errors to.
     * @param int|null $recordID The existing ID of the current record if applicable.
     *
     * @return bool Whether or not the url code passed validation.
     */
    private function validateUniqueUrlCode(string $urlCode, ValidationField $validationField, int $recordID = null): bool {
        $existingRow = $this->knowledgeBaseModel->get(['urlCode' => $urlCode])[0] ?? null;
        if ($existingRow && $existingRow['knowledgeBaseID'] !== $recordID) {
            $validationField->addError('The specified URL code is already in use by another knowledge base.');

            return false;
        }

        return true;
    }

    /**
     * Delete a knowledge base.
     *
     * @param int $id
     * @throws ValidationException If output validation fails while getting the knowledge base.
     * @throws \Garden\Web\Exception\ClientException If the root knowledge category is not empty.
     */
    public function delete(int $id) {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema()->setDescription("Delete a knowledge base.");
        $this->schema([], "out");

        $row = $this->knowledgeBaseByID($id);

        if ($row["isUniversalSource"]) {
            $this->knowledgeUniversalSourceModel->delete(["sourceKnowledgeBaseID" => $id]);
        } else {
            $this->knowledgeUniversalSourceModel->delete(["targetKnowledgeBaseID" => $id]);
        }

        if ($row["countArticles"] < 1 && $row["countCategories"] <= 1) {
            $this->knowledgeBaseModel->delete(["knowledgeBaseID" => $row["knowledgeBaseID"]]);
        } else {
            throw new \Garden\Web\Exception\ClientException("Knowledge base is not empty.", 409);
        }
    }

    /**
     * Get a single knowledge base by its ID.
     *
     * @param int $knowledgeBaseID
     * @param bool $includeDeleted Include "deleted" knowledgebase. Default: true (include all)
     * @param bool $checkOnly Check only call. If TRUE there is no need to return extra data
     *
     * @return array
     * @throws NotFoundException If the knowledge base could not be found.
     */
    public function knowledgeBaseByID(int $knowledgeBaseID, bool $includeDeleted = true, bool $checkOnly = false): array {
        try {
            if ($includeDeleted) {
                $result = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
            } else {
                $result = $this->knowledgeBaseModel->selectSingle(
                    [
                        "knowledgeBaseID" => $knowledgeBaseID,
                        'status' => KnowledgeBaseModel::STATUS_PUBLISHED
                    ]
                );
            }
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new NotFoundException('Knowledge Base with ID: ' . $knowledgeBaseID . ' not found!');
        }

        if (!$checkOnly && $result['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
            $result['defaultArticleID'] = $this->knowledgeNavigationApi->getDefaultArticleID($knowledgeBaseID);
        } else {
            $result['defaultArticleID'] = null;
        }
        return $result;
    }

    /**
     * Normalize output.
     *
     * @param array $record A single knowledge base record.
     *
     * @return array
     */
    private function normalizeOutput(array $record): array {
        if (!isset($record['defaultArticleID'])) {
            if ($record['viewType'] === KnowledgeBaseModel::TYPE_GUIDE) {
                $record['defaultArticleID'] = $this->knowledgeNavigationApi->getDefaultArticleID($record['knowledgeBaseID']);
            } else {
                $record['defaultArticleID'] = null;
            }
        }

        if (array_key_exists("isUniversalSource", $record)) {
            if ($record["isUniversalSource"]) {
                $idType = "sourceKnowledgeBaseID";
                $targetKBIDs = $this->knowledgeUniversalSourceModel->getUniversalInformation($idType, $record);
                $record["universalTargetIDs"] = $targetKBIDs;
                $record["universalSourceIDs"] = [];
            } elseif (!$record["isUniversalSource"]) {
                $idType = "targetKnowledgeBaseID";
                $sourceKbIDs = $this->knowledgeUniversalSourceModel->getUniversalInformation($idType, $record);
                $record["universalSourceIDs"] = $sourceKbIDs;
                $record["universalTargetIDs"] = [];
            }
        }
        $record['url'] = $this->knowledgeBaseModel->url($record);
        $this->expandSiteSections($record);
        return $record;
    }

    /**
     * Translate universal-content kbs.
     *
     * @param array $rows
     * @param string $locale
     * @return array
     */
    private function translateContentUniversalKBs(array $rows = [], string $locale = 'en'): array {
        $translations = [];
        foreach ($rows as $row) {
            $translation = $this->translateProperties([$row], $locale);
            $translations[] = reset($translation);
        }
        return $translations;
    }
}
