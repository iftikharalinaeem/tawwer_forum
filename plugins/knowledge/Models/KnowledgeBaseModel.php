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
use Vanilla\Exception\Database\NoResultsException;
use Garden\Web\Exception\NotFoundException;
use Garden\Schema\Validation;

/**
 * A model for managing knowledge bases.
 */
class KnowledgeBaseModel extends \Vanilla\Models\PipelineModel {
    const TYPE_GUIDE = 'guide';
    const TYPE_HELP = 'help';

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

    /**
     * KnowledgeBaseModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("knowledgeBase");
        $this->session = $session;

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
        $result = \Gdn::request()->url("/kb/" . $slug, $withDomain);
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
        $result = $this->sql()
            ->select('DISTINCT knowledgeBaseID', 'COUNT', 'count')
            ->from('knowledgeBase')
            ->where('status', self::STATUS_PUBLISHED)
            ->get()->nextRow(DATASET_TYPE_ARRAY)
        ;

        return $result['count'];
    }

    /**
     * Select a KnowledgeBaseFragment for a given id.
     *
     * @param int $knowledgeBaseID Conditions for the select query.
     *
     * @return KnowledgeBaseFragment
     *
     * @throws ValidationException If the data from the DB was corrupted.
     * @throws NoResultsException If no record was found for the given ID.
     */
    public function selectSingleFragment(int $knowledgeBaseID): KnowledgeBaseFragment {
        $rows = $this->sql()
            ->select('knowledgeBaseID, rootCategoryID, name, urlCode, viewType, status')
            ->getWhere($this->getTable(), ['knowledgeBaseID' => $knowledgeBaseID], null, null, 1)
            ->resultArray()
        ;

        if (empty($rows)) {
            throw new NoResultsException("Could not find knowledge base fragment for knowledgeBaseID $knowledgeBaseID.");
        }
        $result = reset($rows);

        // Normalize the fragment.
        $url = $this->url($result);
        $result['url'] = $url;

        return new KnowledgeBaseFragment($result);
    }

    /**
     * Select a KnowledgeBaseFragment from it's root category ID.
     *
     * @param int $categoryID Conditions for the select query.
     *
     * @return KnowledgeBaseFragment
     *
     * @throws ValidationException If the data from the DB was corrupted.
     * @throws NoResultsException If no record was found for the given ID.
     */
    public function selectFragmentForCategoryID(int $categoryID) {
        $rows = $this->sql()
            ->select('kb.knowledgeBaseID, kb.rootCategoryID, kb.name, kb.urlCode, kb.viewType, kb.status')
            ->from('knowledgeCategory kc')
            ->leftJoin('knowledgeBase kb', 'kb.knowledgeBaseID = kc.knowledgeBaseID')
            ->where('kc.knowledgeCategoryID', $categoryID)
            ->limit(1)
            ->get()
            ->resultArray()
        ;

        if (empty($rows)) {
            throw new NoResultsException("Could not find knowledge base fragment for rootCategoryID $rootCategoryID.");
        }
        $result = reset($rows);

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
     * @param array $set Field values to set.
     * @return mixed ID of the inserted row.
     * @throws Exception If an error is encountered while performing the query.
     */
    public function insert(array $set) {
        // Enforce restrictions on KB article sorting.
        $this->validateSortArticlesInternal($set);

        return parent::insert($set);
    }

    /**
     * Update existing knowledge bases.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @throws \Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where): bool {
        $isSingle = array_key_exists("knowledgeBaseID", $where) && !is_array($where["knowledgeBaseID"]);

        // Enforce restrictions on sorting.
        if ($isSingle) {
            $this->validateSortArticlesInternal($set, $where["knowledgeBaseID"]);
        }

        return parent::update($set, $where);
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

    public function getSiteSectionGroup() {

        return "13_test";
    }
}
