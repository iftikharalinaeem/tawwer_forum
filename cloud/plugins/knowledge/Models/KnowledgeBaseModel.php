<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Gdn_Session;
use Vanilla\Database\Operation;
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Permissions;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Site\TranslationModel;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use Vanilla\Exception\PermissionException;
use UserModel;

/**
 * A model for managing knowledge bases.
 */
class KnowledgeBaseModel extends FullRecordCacheModel {
    // Record type for knowledge categories.
    const RECORD_TYPE = "knowledgeBase";
    const RECORD_ID_FIELD = "knowledgeBaseID";

    const VIEW = 'view';
    const EDIT = 'edit';

    const VIEW_PERMISSION = 'knowledge.kb.view';
    const EDIT_PERMISSION = 'knowledge.articles.add';

    const TYPE_GUIDE = 'guide';
    const TYPE_HELP = 'help';
    const SPHINX_ARTICLES_COUNTER_ID = 5;
    const SPHINX_ARTICLES_DELETED_COUNTER_ID = 6;

    const STATUS_PUBLISHED = 'published';
    const STATUS_DELETED = 'deleted';

    const ORDER_MANUAL = 'manual';
    const ORDER_NAME = 'name';
    const ORDER_DATE_ASC = 'dateInserted';
    const ORDER_DATE_DESC = 'dateInsertedDesc';

    const SORT_CONFIGS = [
        self::ORDER_MANUAL => ["sort", "asc"],
        self::ORDER_NAME => ["name", "asc"],
        self::ORDER_DATE_ASC => ["dateInserted", "asc"],
        self::ORDER_DATE_DESC => ["dateInserted", "desc"],
    ];

    /** @var Gdn_Session */
    private $session;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var Permissions */
    private $permissions;

    /** @var UserModel $userModel */
    private $userModel;

    /** @var TranslationProviderInterface */
    private $translation;

    /** @var array $allAllowed All knowledge base id allowed  */
    private $allAllowed;

    /** @var array $editAllowed Knowledge base id allowed to edit */
    private $editAllowed;

    /**
     * KnowledgeBaseModel constructor.
     *
     * @param Gdn_Session $session
     * @param SiteSectionModel $siteSectionModel
     * @param TranslationModel $translationModel
     * @param UserModel $userModel
     * @param \Gdn_Cache $cache
     */
    public function __construct(
        Gdn_Session $session,
        SiteSectionModel $siteSectionModel,
        TranslationModel $translationModel,
        UserModel $userModel,
        \Gdn_Cache $cache
    ) {
        parent::__construct("knowledgeBase", $cache);
        $this->session = $session;
        $this->siteSectionModel = $siteSectionModel;
        $this->translation = $translationModel->getContentTranslationProvider();
        $this->permissions = $session->getPermissions();
        $this->userModel = $userModel;

        $dateProcessor = new \Vanilla\Database\Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new \Vanilla\Database\Operation\CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * @inheritdoc
     */
    protected function configureWriteSchema(Schema $schema): Schema {
        $writeSchema = parent::configureWriteSchema($schema);
        $writeSchema->addValidator('urlCode', [$this, 'urlCodeValidator']);
        return $writeSchema;
    }

    /**
     * Update where clause with only allowed kb id list
     *
     * @param array $where
     * @param string $prefix
     * @return array
     */
    public function updateKnowledgeIDsWithCustomPermission(array $where, string $prefix = ''): array {
        if (!$this->session->checkPermission('Garden.Settings.Manage')) {
            $kbsView = $this->getAllowedKnowledgeBases(self::VIEW);
            $kbsEdit = $this->getAllowedKnowledgeBases(self::EDIT);
            $allowedKBs = array_unique(array_merge($kbsView, $kbsEdit));
            if (array_key_exists($prefix.'knowledgeBaseID', $where)) {
                $where[$prefix.'knowledgeBaseID'] = array_intersect((array)$where[$prefix.'knowledgeBaseID'], $allowedKBs);
            } else {
                $where[$prefix.'knowledgeBaseID'] = $allowedKBs;
            }
        }
        return $where;
    }

    /**
     * Check if current user has EDIT permission for KB
     *
     * @param int $knowledgeBaseID
     * @throws NotFoundException If user has no permission to edit content simulate 'not found'.
     */
    public function checkEditPermission(int $knowledgeBaseID) {
        if (!$this->session->checkPermission('Garden.Settings.Manage')) {
            if (!in_array($knowledgeBaseID, $this->getAllowedKnowledgeBases(self::EDIT))) {
                throw new PermissionException(self::EDIT_PERMISSION);
            }
        }
    }

    /**
     * Check if current user has VIEW permission for KB
     *
     * @param int $knowledgeBaseID
     * @throws NotFoundException If user has no permission to view content simulate 'not found'.
     */
    public function checkViewPermission(int $knowledgeBaseID) {
        if (!$this->session->checkPermission('Garden.Settings.Manage')) {
            if (!in_array($knowledgeBaseID, $this->getAllowedKnowledgeBases(self::VIEW))) {
                if (!in_array($knowledgeBaseID, $this->getAllowedKnowledgeBases(self::EDIT))) {
                    throw new PermissionException(self::VIEW_PERMISSION);
                }
            }
        }
    }

    /**
     * Check global only permission for knowledge base. There are a couple of changes to our core permission.
     *
     * This has slightly different behaviour than by default.
     *
     * @param string $permission
     * @throws PermissionException If user hasno permission throw an PermissionException.
     */
    public function checkGlobalPermission(string $permission) {
        if (!$this->permissions->hasAny(
            ['Garden.Settings.Manage', 'knowledge.articles.manage', $permission],
            null,
            Permissions::CHECK_MODE_GLOBAL_OR_RESOURCE
        )) {
            throw new PermissionException($permission);
        }
    }

    /**
     * Extension for permission model
     *
     * @param string $junctionTable
     * @param int $foreignID
     * @param string $permission
     * @return array
     */
    public function getAllowedRoles(string $junctionTable, int $foreignID, string $permission): array {
        // Generic part of query
        $sql = $this->createSql();
        $sql->from('Permission p')
            ->select('PermissionID', 'COUNT')
            ->select('r.RoleID')
            ->select('r.Name')
            ->join('Role r', 'p.RoleID = r.RoleID')
            ->where('JunctionTable', $junctionTable)
            ->where('JunctionID', $foreignID)
            ->where('`'.$permission.'` >', 0, false)
            ->groupBy(['p.RoleID']);
        return $sql->get()->resultArray();
    }

    /**
     * Generate a URL to the provided knowledge base row.
     *
     * @param array $knowledgeBase An knowledge base row.
     * @param bool $withDomain
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $knowledgeBase, bool $withDomain = true): string {
        $urlCode = $knowledgeBase["urlCode"] ?? null;

        if (!$urlCode) {
            throw new \Exception('Invalid knowledge-base row.');
        }

        $slug = \Gdn_Format::url($urlCode);

        $knowledgeBaseSourceLocale = $knowledgeBase['sourceLocale'] ?? null;
        $locale = $knowledgeBase['locale'] ?? $knowledgeBaseSourceLocale;

        // If the kb's source locale is different from the queried locale, check if there's a matching site-section.
        // If there isn't build the url off of the sourceLocale.
        if ($locale && ($knowledgeBaseSourceLocale !== $locale)) {
            $siteSections = $this->siteSectionModel->getForSectionGroup($knowledgeBase['siteSectionGroup']);
            if ($siteSections) {
                $siteSectionsLocales = [];
                foreach ($siteSections as $siteSection) {
                    $siteSectionsLocales[] = $siteSection->getContentLocale();
                }
                $localeAvailable = in_array($locale, $siteSectionsLocales);
                if (!$localeAvailable) {
                    $locale = $knowledgeBaseSourceLocale;
                }
            } else {
                // no site-sections use the sourceLocale.
                $locale = $knowledgeBaseSourceLocale;
            }
        }

        // if for some reason it doesn't exist fall back to the current site-section locale.
        if (!$locale) {
            $locale = $this->siteSectionModel->getCurrentSiteSection()->getContentLocale();
        }

        $siteSectionSlug = $this->getSiteSectionSlug($knowledgeBase['knowledgeBaseID'], $locale);
        $result = \Gdn::request()->getSimpleUrl($siteSectionSlug . "/kb/" . $slug);
        return $result;
    }

    /**
     * Validate the URL code of a knowledge base record.
     *
     * Currently this needs to take the
     *
     * - Must be unique.
     * - Must be made up of only
     *
     * @param string $urlCode The value of the urlcode.
     * @param ValidationField $field The field being validated.
     *
     * @return bool
     */
    public function urlCodeValidator(string $urlCode, ValidationField $field): bool {
        $regex = '/^[a-z0-9\-_]+$/';
        $reservedSlugs = [
            'articles',
            'categories',
            'drafts',
            'search',
        ];
        $validation = $field->getValidation();

        if (!preg_match($regex, $urlCode)) {
            $validation->addError('urlCode', <<<MESSAGE
URL code can only be made of the following characters: a-z, 0-9, _, -
MESSAGE
            );
        }

        if (in_array($urlCode, $reservedSlugs)) {
            $readableReservedWords = implode(", ", $reservedSlugs);
            $validation->addError('urlCode', <<<MESSAGE
URL code cannot be any of the following: $readableReservedWords.
MESSAGE
            );
        }

        return $validation->isValid();
    }

    /**
     * Get the total count of published knowledge bases.
     *
     * @return int
     */
    public function selectActiveKBCount(): int {
        return $this->modelCache->getCachedOrHydrate(['totalCount' => true], function () {
            $result = $this->createSql()
                ->select('DISTINCT knowledgeBaseID', 'COUNT', 'count')
                ->from('knowledgeBase')
                ->where('status', self::STATUS_PUBLISHED)
                ->get()->firstRow(DATASET_TYPE_ARRAY)
            ;
            return $result['count'];
        });
    }

    /**
     * Select a KnowledgeBaseFragment for a given id.
     *
     * @param int $knowledgeBaseID Conditions for the select query.
     * @param string $locale
     *
     * @return KnowledgeBaseFragment
     *
     * @throws ValidationException If the data from the DB was corrupted.
     * @throws NoResultsException If no record was found for the given ID.
     */
    public function selectSingleFragment(int $knowledgeBaseID, string $locale = null): KnowledgeBaseFragment {
        $rows = $this->sql()
            ->select('knowledgeBaseID, rootCategoryID, name, urlCode, viewType, status, sourceLocale')
            ->getWhere($this->getTable(), ['knowledgeBaseID' => $knowledgeBaseID], null, null, 1)
            ->resultArray()
        ;

        if (empty($rows)) {
            throw new NoResultsException("Could not find knowledge base fragment for knowledgeBaseID $knowledgeBaseID.");
        }
        $result = reset($rows);
        if ($this->translation && !empty($locale)) {
            $result = $this->translation->translateProperties(
                $locale,
                'kb',
                KnowledgeBaseModel::RECORD_TYPE,
                KnowledgeBaseModel::RECORD_ID_FIELD,
                [$result],
                ['name', 'description']
            )[0];
        }
        $result['locale'] = $locale ?? $result['sourceLocale'];

        // Normalize the fragment.
        $url = $this->url($result);
        $result['url'] = $url;

        return new KnowledgeBaseFragment($result);
    }

    /**
     * Select a KnowledgeBaseFragment from it's root category ID.
     *
     * @param int $categoryID Conditions for the select query.
     * @param string $locale Locale to represent content in.
     *
     * @return KnowledgeBaseFragment
     *
     * @throws ValidationException If the data from the DB was corrupted.
     * @throws NoResultsException If no record was found for the given ID.
     */
    public function selectFragmentForCategoryID(int $categoryID, string $locale = null) {
        $rows = $this->sql()
            ->select(
                'kb.knowledgeBaseID, 
                kb.rootCategoryID, 
                kb.name, 
                kb.urlCode, 
                kb.viewType, 
                kb.status, 
                kb.sourceLocale, 
                kb.siteSectionGroup'
            )
            ->from('knowledgeCategory kc')
            ->leftJoin('knowledgeBase kb', 'kb.knowledgeBaseID = kc.knowledgeBaseID')
            ->where('kc.knowledgeCategoryID', $categoryID)
            ->limit(1)
            ->get()
            ->resultArray()
        ;

        if (empty($rows)) {
            throw new NoResultsException("Could not find knowledge base fragment for rootCategoryID $categoryID.");
        }
        $result = reset($rows);
        if ($this->translation && !empty($locale)) {
            $result = $this->translation->translateProperties(
                $locale,
                'kb',
                KnowledgeBaseModel::RECORD_TYPE,
                KnowledgeBaseModel::RECORD_ID_FIELD,
                [$result],
                ['name', 'description']
            )[0];
        }
        $result['locale'] = $locale ?? $result['sourceLocale'];

        // Normalize the fragment.
        $url = $this->url($result);
        $result['url'] = $url;

        return new KnowledgeBaseFragment($result);
    }

    /**
     * Recalculate and update countArticles and countCategories columns.
     *
     * @param int $knowledgeBaseID Knowledge Base id to update
     *
     * @return bool Return truu when record updated succesfully
     */
    public function updateCounts(int $knowledgeBaseID): bool {
        $counts = $this->sql()
            ->select('DISTINCT a.articleID', 'COUNT', 'articleCount')
            ->select('DISTINCT c.knowledgeCategoryID', 'COUNT', 'categoryCount')
            ->from('knowledgeCategory c')
            ->leftJoin('article a', 'c.knowledgeCategoryID = a.knowledgeCategoryID AND a.status = \''.ArticleModel::STATUS_PUBLISHED.'\'')
            ->where('c.knowledgeBaseID', $knowledgeBaseID)
            ->groupBy('c.knowledgeBaseID')
            ->get()->nextRow(DATASET_TYPE_ARRAY);

        $res = $this->update(
            [
                'countArticles' => $counts['articleCount'],
                'countCategories' => $counts['categoryCount']
            ],
            [
                'knowledgeBaseID' => $knowledgeBaseID
            ]
        );

        return $res;
    }

    /**
     * Check if Knowledge Category is a root category of any Knowledge Base
     *
     * @param int $knowledgeCategoryID
     * @return bool
     */
    public function isRootCategory(int $knowledgeCategoryID): bool {
        $kb = $this->get(['rootCategoryID' => $knowledgeCategoryID]);
        return !empty($kb);
    }
    /**
     * Get list of all knowledge base types
     *
     * @return array
     */
    public static function getAllTypes(): array {
        return [
            self::TYPE_GUIDE,
            self::TYPE_HELP
        ];
    }

    /**
     * Gat list of all knowledge base options for article order
     *
     * @return array
     */
    public static function getAllSorts(): array {
        return [
            self::ORDER_MANUAL,
            self::ORDER_NAME,
            self::ORDER_DATE_ASC,
            self::ORDER_DATE_DESC,
        ];
    }

    /**
     * Given a valid article sort slug, return the relevant sort field and direction.
     *
     * @param string $sortArticles
     * @return array
     */
    public function articleSortConfig(string $sortArticles): array {
        if (!array_key_exists($sortArticles, self::SORT_CONFIGS)) {
            throw new \Exception("Invalid sortArticles value: $sortArticles");
        }

        return self::SORT_CONFIGS[$sortArticles];
    }

    /**
     * Get all locales supported by knowledge base (based on siteSectionGroup supported).
     *
     * @param string $siteSectionGroup Knowledge base site section group.
     * @return array
     */
    public function getLocales(string $siteSectionGroup): array {
        $locales = [];
        foreach ($this->siteSectionModel->getAll() as $siteSection) {
            if ($siteSection->getSectionGroup() === $siteSectionGroup) {
                $locales[] = ['locale' => $siteSection->getContentLocale(), 'slug' => $siteSection->getBasePath()];
            }
        }
        return $locales;
    }

    /**
     * Return list of statuses for knowledge Base model
     *
     * @return array
     */
    public static function getAllStatuses(): array {
        return [
            self::STATUS_DELETED,
            self::STATUS_PUBLISHED,
        ];
    }

    /**
     * Add a knowledge base.
     *
     * @inheritdoc
     */
    public function insert(array $set, $options = []) {

        // Enforce restrictions on KB article sorting.
        $this->validateSortArticlesInternal($set);

        return parent::insert($set, $options);
    }

    /**
     * Update existing knowledge bases.
     *
     * @inheritdoc
     */
    public function update(array $set, array $where, $options = []): bool {
        $isSingle = array_key_exists("knowledgeBaseID", $where) && !is_array($where["knowledgeBaseID"]);

        // Enforce restrictions on sorting.
        if ($isSingle) {
            $this->validateSortArticlesInternal($set, $where["knowledgeBaseID"]);
        }

        return parent::update($set, $where, $options);
    }

    /**
     * Reset both sphinx indexes: KnowledgeArticle and KnowledgeArticleDeleted to 0.
     * This will trigger complete reindex for these two indexes when run next Sphinx reindex.
     *
     * @return bool
     */
    public function resetSphinxCounters() {
        /** @var \Gdn_MySQLDriver $sql */
        $sql = $this->sql();
        $query = $sql->getUpdate(
            $sql->mapAliases('SphinxCounter'),
            ['MaxID' => 1],
            [
                'CounterID in ('.implode(',', [
                    self::SPHINX_ARTICLES_COUNTER_ID,
                    self::SPHINX_ARTICLES_DELETED_COUNTER_ID
                ]).')',
            ]
        );
        $sql->query($query, 'update');
        $this->reindexSphinx();
    }

    /**
     * Try to reindex sphinx when localhost
     * Note: this method should be refactored to implement some infrastructure call to reindex sphinx
     *     Developers should add this to their local config file
     *     $Configuration['Plugins']['Sphinx']['Debug'] = true;
     *     That will allow dynamic sphinx reindexing
     */
    private function reindexSphinx() {
        if (c('Plugins.Sphinx.Debug')) {
            $sphinxHost = c('Plugins.Sphinx.Server');
            exec('curl '.$sphinxHost.':9399', $dockerResponse);
        }
    }
    /**
     * Validate sort value of fields to be written to a new or existing knowledge base row.
     *
     * @param array $set
     * @param integer $knowledgeBaseID
     */
    private function validateSortArticlesInternal(array $set, int $knowledgeBaseID = null) {
        if ($knowledgeBaseID) {
            try {
                $row = $this->selectSingle(["knowledgeBaseID" => $knowledgeBaseID]);
            } catch (NoResultsException $e) {
                $row = [];
            }
        } else {
            $row = [];
        }

        $sort = $set["sortArticles"] ?? $row["sortArticles"] ?? null;
        $type = $set["viewType"] ?? $row["viewType"] ?? null;

        if ($sort === KnowledgeBaseModel::ORDER_MANUAL && $type !== KnowledgeBaseModel::TYPE_GUIDE) {
            throw new \InvalidArgumentException("A knowledge base must be a guide to use manual sorting.");
        } elseif ($type === KnowledgeBaseModel::TYPE_GUIDE && $sort !== KnowledgeBaseModel::ORDER_MANUAL) {
            throw new \InvalidArgumentException("A guide must be manually sorted.");
        }
    }
    /**
     * Validate potential sortArticle value for a KB.
     * This method is intended to be applied as a custom validator on a {@see \Garden\Schema\Schema} instance.
     *
     * @param array $data Full array of data to be written.
     * @param ValidationField $validation
     * @param integer $recordID
     */
    public function validateSortArticles(array $data, ValidationField $validation, int $recordID = null) {

        if (!array_key_exists("sortArticles", $data) && !array_key_exists("viewType", $data)) {
            // Avoid additional validation if neither relevant field is detected.
            return true;
        }

        try {
            $this->validateSortArticlesInternal($data, $recordID);
        } catch (\InvalidArgumentException $e) {
            $field = array_key_exists("sortArticles", $data) ? "sortArticles" : "viewType";
            $validation->getValidation()->addError($field, $e->getMessage());
        }

        return true;
    }

    /**
     * Validate if isUniversal status is set correctly.
     *
     * @param array $data
     * @param ValidationField $validation
     * @param int|null $recordID
     */
    public function validateIsUniversalSource(array $data, ValidationField $validation, int $recordID = null) {
        if (($data["universalTargetIDs"] ?? null) && !$recordID) {
            if (array_key_exists("isUniversalSource", $data) && !$data["isUniversalSource"]) {
                $validation->getValidation()->addError(
                    $validation->getName(),
                    "Invalid universal source status, isUniversalSource parameter must be set to true if target KB's are passed."
                );
            }
        } elseif (($data["universalTargetIDs"] ?? null) && $recordID) {
            if (array_key_exists("isUniversalSource", $data)) {
                $isUniversalKB = $data["isUniversalSource"];
            } else {
                $knowledgeBase = $this->selectSingle(["knowledgeBaseID" => $recordID]);
                $isUniversalKB = $knowledgeBase["isUniversalSource"];
            }

            if (!$isUniversalKB) {
                $validation->getValidation()->addError(
                    $validation->getName(),
                    "Invalid universal source status, isUniversalSource parameter must be set to true if target KB's are passed."
                );
            }
        }
    }

    /**
     * Check knowledge base exist and not "deleted".
     *
     * @param int $knowledgeBaseID
     * @return array
     * @throws NotFoundException Fired if kb does not exist or "deleted".
     */
    public function checkKnowledgeBasePublished(int $knowledgeBaseID): array {
        try {
            $kb = $this->selectSingle(
                [
                        "knowledgeBaseID" => $knowledgeBaseID,
                        'status' => KnowledgeBaseModel::STATUS_PUBLISHED
                ]
            );
            return $kb;
        } catch (NoResultsException $e) {
            throw new NotFoundException('Knowledge Base with ID: ' . $knowledgeBaseID . ' not found!');
        }
    }

    /**
     * Get all the supported locales of a Knowledge-base by ID.
     *
     * @param array $knowledgeBase
     * @return array
     */
    public function getSupportedLocalesByKnowledgeBase(array $knowledgeBase): array {
        $supportedLocales = $this->getLocales($knowledgeBase["siteSectionGroup"]);
        $locales = array_column($supportedLocales, "locale");
        return $locales;
    }

    /**
     * Get siteSection slug by locale.
     *
     * @param int $knowledgeBaseID
     * @param string $locale
     * @return string
     */
    public function getSiteSectionSlug(int $knowledgeBaseID, string $locale = null): string {
        $slug = '';
        $knowledgeBase = $this->selectSingle(['knowledgeBaseID' => $knowledgeBaseID]);
        $siteSections = $this->siteSectionModel->getForSectionGroup($knowledgeBase['siteSectionGroup']);
        foreach ($siteSections as $siteSection) {
            if ($siteSection->getContentLocale() === $locale
                || (is_null($locale) && $siteSection->getContentLocale() === $knowledgeBase['sourceLocale'])) {
                $slug = $siteSection->getBasePath();
                break;
            }
        }
        return $slug;
    }

    /**
     * Get list of all kb user has permission to view
     *
     * @param string $mode Permission to check. Enum: 'view', 'edit'
     *        'view' -> 'knowledge.kb.view', 'edit' -> 'knowledge.articles.add'
     * @return array
     */
    public function getAllowedKnowledgeBases(string $mode = self::VIEW): array {
        $dataKey = ($mode === self::VIEW) ? 'allAllowed' : 'editAllowed';
        $permissionKey = ($mode === self::VIEW) ? self::VIEW_PERMISSION : self::EDIT_PERMISSION;
        // Purely global permissions exist as values with integer keys.
        // Per-resource permissions exist as sting keys (of the permission) with object values.
        $hasPureGlobalPermission = $this->permissions->has($permissionKey, null, Permissions::CHECK_MODE_GLOBAL_ONLY);
        if (is_null($this->{$dataKey})) {
            $res = $hasPureGlobalPermission
                ? array_column($this->get(['hasCustomPermission' => 0], ['select' => ['knowledgeBaseID']]), 'knowledgeBaseID')
                : [];
            $restricted = $this->get(['hasCustomPermission' => 1], ['select' => ['knowledgeBaseID', 'permissionKnowledgeBaseID']]);
            foreach ($restricted as $row) {
                if ($this->permissions->has($permissionKey, $row['permissionKnowledgeBaseID'], Permissions::CHECK_MODE_RESOURCE_ONLY)) {
                    $res[] = $row['knowledgeBaseID'];
                }
            }
            $this->{$dataKey} = $res;
        }
        return $this->{$dataKey};
    }

    /**
     * Validator for siteSectionGroup field
     *
     * @param string $siteSectionGroup
     * @param ValidationField $validationField
     * @return bool
     */
    public function validateSiteSectionGroup(string $siteSectionGroup, \Garden\Schema\ValidationField $validationField): bool {
        $siteSections = $this->siteSectionModel->getForSectionGroup($siteSectionGroup);
        $valid = !empty($siteSections);
        if (!$valid) {
            $validationField->getValidation()->addError(
                $validationField->getName(),
                "Invalid site section group ".$siteSectionGroup.'. Or group has no sections yet. '
            );
        }
        return $valid;
    }
}
