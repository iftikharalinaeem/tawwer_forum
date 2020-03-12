<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Controllers\Api;

use AbstractApiController;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Vanilla\Knowledge\Models\ArticleModel;
use Vanilla\Knowledge\Models\KbCategoryRecordType;
use Vanilla\Knowledge\Models\KnowledgeBaseModel;
use Vanilla\Knowledge\Models\KnowledgeCategoryModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Site\TranslationModel;
use Vanilla\Contracts\Site\TranslationProviderInterface;
use LocalesApiController;

/**
 * Endpoint for the knowledge category resource.
 */
class KnowledgeCategoriesApiController extends AbstractApiController {
    use KnowledgeCategoriesApiSchemes;
    use CheckGlobalPermissionTrait;

    /** @var Schema */
    private $idParamSchema;

    /** @var KnowledgeCategoryModel */
    private $knowledgeCategoryModel;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var ArticleModel */
    private $articleModel;

    /** @var Schema */
    private $knowledgeCategoryPostSchema;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var TranslationProviderInterface */
    private $translation;

    /** @var LocalesApiController $localeApi */
    private $localeApi;

    /**
     * KnowledgeCategoriesApiController constructor.
     *
     * @param KnowledgeCategoryModel $knowledgeCategoryModel
     * @param KnowledgeBaseModel $knowledgeBaseModel
     * @param ArticleModel $articleModel
     * @param BreadcrumbModel $breadcrumModel
     * @param TranslationModel $translationModel
     * @param LocalesApiController $localeApi
     */
    public function __construct(
        KnowledgeCategoryModel $knowledgeCategoryModel,
        KnowledgeBaseModel $knowledgeBaseModel,
        ArticleModel $articleModel,
        BreadcrumbModel $breadcrumModel,
        TranslationModel $translationModel,
        LocalesApiController $localeApi
    ) {
        $this->knowledgeCategoryModel = $knowledgeCategoryModel;
        $this->knowledgeBaseModel = $knowledgeBaseModel;
        $this->articleModel = $articleModel;
        $this->breadcrumbModel = $breadcrumModel;
        $this->translation = $translationModel->getContentTranslationProvider();
        $this->localeApi = $localeApi;
    }

    /**
     * Delete a knowledge category.
     *
     * @param int $id
     * @throws \Garden\Web\Exception\ClientException If the target knowledge category is not empty.
     */
    public function delete(int $id) {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema()->setDescription("Delete a knowledge category.");
        $this->schema([], "out");

        $row = $this->knowledgeCategoryByID($id);

        $this->knowledgeBaseModel->checkEditPermission($row['knowledgeBaseID']);
        $this->knowledgeBaseModel->checkKnowledgeBasePublished($row['knowledgeBaseID']);

        if (!$this->knowledgeBaseModel->isRootCategory($id)) {
            if ($row["articleCount"] < 1 && $row["childCategoryCount"] < 1) {
                $this->knowledgeCategoryModel->delete(["knowledgeCategoryID" => $row["knowledgeCategoryID"]]);
                $this->articleModel->delete(["knowledgeCategoryID" => $row["knowledgeCategoryID"]]);

                if (!empty($row['parentID']) && ($row['parentID'] !== -1)) {
                    $this->knowledgeCategoryModel->updateCounts($row['parentID']);
                }
            } else {
                throw new \Garden\Web\Exception\ClientException("Knowledge category is not empty.", 409);
            }
        } else {
            throw new \Garden\Web\Exception\ClientException("You can not delete root category.", 409);
        }
    }

    /**
     * Get a single knowledge category.
     *
     * @param int $id
     * @param array $query Query parameters array. Ex: ["locale": "en"]
     * @return array
     */
    public function get(int $id, array $query = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        /** @var Schema $in */
        $in = $this->idParamSchema()->addValidator('locale', [$this->localeApi, 'validateLocale']);
        $query['id'] = $id;
        $query = $in->validate($query);

        $out = $this->schema($this->fullSchema(), "out");

        $row = $this->knowledgeCategoryByID($id);
        $this->knowledgeBaseModel->checkViewPermission($row['knowledgeBaseID']);

        $row = $this->translateProperties([$row], $query['locale'])[0];
        $crumbs = $this->breadcrumbModel->getForRecord(new KbCategoryRecordType($row['knowledgeCategoryID']), $query['locale']);
        $row['breadcrumbs'] = $crumbs;
        $row['locale'] = $query['locale'];
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Translate properties (name, description) of knowledgeCategory records
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
                KnowledgeCategoryModel::RECORD_TYPE,
                KnowledgeCategoryModel::RECORD_ID_FIELD,
                $rows,
                ['name']
            );
        }
        return $rows;
    }

    /**
     * Get a knowledge category for editing.
     *
     * @param int $id
     * @return array
     */
    public function get_edit(int $id): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);
        $this->idParamSchema();

        $out = $this->schema(Schema::parse([
            "knowledgeCategoryID",
            "name",
            "parentID",
            "sort",
            "sortChildren",
            "foreignID?"
        ])->add($this->fullSchema()), "out");

        $row = $this->knowledgeCategoryByID($id);
        $this->knowledgeBaseModel->checkEditPermission($row['knowledgeBaseID']);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * List knowledge categories.
     *
     * @param array $query Request query params
     * @return array
     */
    public function index(array $query = []): array {
        $this->checkPermission(KnowledgeBaseModel::VIEW_PERMISSION);

        $in = $this->schema(["locale?"], 'in')
            ->addValidator('locale', [$this->localeApi, 'validateLocale']);
        $query = $in->validate($query);
        $out = $this->schema([":a" => $this->fullSchema()], "out");

        $publishedKnowledgeBases = array_column(
            $this->knowledgeBaseModel->get(
                $this->knowledgeBaseModel->updateKnowledgeIDsWithCustomPermission(['status' => KnowledgeBaseModel::STATUS_PUBLISHED])
            ),
            'knowledgeBaseID'
        );

        $rows = $this->knowledgeCategoryModel->get(['knowledgeBaseID' => $publishedKnowledgeBases]);
        if ($query['locale'] ?? false) {
            $rows = $this->translateProperties($rows, $query['locale']);
        }
        foreach ($rows as &$row) {
            if ($query['locale'] ?? false) {
                $row['locale'] = $query['locale'];
            }
            $row = $this->normalizeOutput($row);
        }

        $result = $out->validate($rows);
        return $result;
    }

    /**
     * Get a single knowledge category by its ID.
     *
     * @param int $knowledgeCategoryID
     * @param bool $includeDeleted Include "deleted" knowledge base. Default: false (exclude "deleted")
     *
     * @return array
     * @throws \Garden\Web\Exception\NotFoundException If the knowledge category could not be found.
     * @throws ValidationException If the knowledge category row fails validating against the model's output schema.
     */
    public function knowledgeCategoryByID(int $knowledgeCategoryID, bool $includeDeleted = false): array {
        try {
            $result = $this->knowledgeCategoryModel->selectSingle(["knowledgeCategoryID" => $knowledgeCategoryID]);
            if (!$includeDeleted) {
                $this->knowledgeBaseModel->checkKnowledgeBasePublished($result['knowledgeBaseID']);
            }
        } catch (\Vanilla\Exception\Database\NoResultsException $e) {
            throw new \Garden\Web\Exception\NotFoundException("Knowledge-Category");
        }
        return $result;
    }

    /**
     * Massage knowledge category row data for useful API output.
     *
     * @param array $row
     * @return array
     * @throws \Exception If $row is not a valid knowledge category.
     */
    public function normalizeOutput(array $row): array {
        $row["url"] = $this->knowledgeCategoryModel->url($row, false);
        return $row;
    }

    /**
     * Update an existing knowledge category.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch(int $id, array $body = []): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema($this->knowledgeCategoryPostSchema())
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateKBCategoriesLimit"])
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateParentID"])
            ->setDescription("Update an existing knowledge category.");

        $out = $this->schema($this->fullSchema(), "out");

        if (!$this->knowledgeBaseModel->isRootCategory($id)) {
            $body = $in->validate($body, true);

            $previousState = $this->knowledgeCategoryByID($id);
            $this->knowledgeBaseModel->checkEditPermission($previousState['knowledgeBaseID']);
            $knowledgeBase = $this->knowledgeBaseModel->checkKnowledgeBasePublished($previousState['knowledgeBaseID']);

            $moveToAnotherParent = (is_int($body['parentID'] ?? false) && ($body['parentID'] != $previousState['parentID']));

            if (!isset($body['sort'])) {
                if ($moveToAnotherParent || !is_int($previousState['sort'])) {
                    $sortInfo = $this->knowledgeCategoryModel->getMaxSortIdx($body['parentID'] ?? $previousState['parentID']);
                    $maxSortIndex = $sortInfo['maxSort'];
                    $body['sort'] = $maxSortIndex + 1;
                    $updateSorts = false;
                } else {
                    // if we don't change the parentID and there is no $fields['sort']
                    // then we don't need to update sorting
                    $body['parentID'] = $body['parentID'] ?? $previousState['parentID'];
                    $updateSorts = false;
                }
            } else {
                //update sorts for other records only if 'sort' changed
                $body['parentID'] = $body['parentID'] ?? $previousState['parentID'];
                $updateSorts = ($body['sort'] != $previousState['sort']);
            }

            if (isset($body['sort'])
                && isset($previousState['parentID'])
                && isset($previousState['sort'])
                && $body['sort'] !== $previousState['sort']) {
                //shift sorts down for source category when move one article to another category
                $this->knowledgeCategoryModel->shiftSorts(
                    $previousState['parentID'],
                    $previousState['sort'],
                    $previousState['knowledgeCategoryID'],
                    KnowledgeCategoryModel::SORT_TYPE_CATEGORY,
                    KnowledgeCategoryModel::SORT_DECREMENT
                );
            }

            $this->knowledgeCategoryModel->update($body, ["knowledgeCategoryID" => $id]);
            if ($moveToAnotherParent) {
                $this->knowledgeCategoryModel->updateCounts($previousState['parentID']);
                $this->knowledgeCategoryModel->updateCounts($id);
            }



            if ($updateSorts) {
                $this->knowledgeCategoryModel->shiftSorts(
                    $body['parentID'],
                    $body['sort'],
                    $id,
                    KnowledgeCategoryModel::SORT_TYPE_CATEGORY
                );
            }

            $row = $this->knowledgeCategoryByID($id);
            $row = $this->normalizeOutput($row);
            $result = $out->validate($row);
        } else {
            throw new \Garden\Web\Exception\ClientException("You can not patch root category.", 409);
        }
        return $result;
    }

    /**
     * Update an existing knowledge category.
     *
     * @param int $id
     * @param array $body
     * @return array
     */
    public function patch_root(int $id, array $body = []): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $this->idParamSchema();
        $in = $this->schema($this->knowledgeCategoryPostSchema())
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateKBCategoriesLimit"])
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateParentID"])
            ->setDescription("Update an existing knowledge category.");

        $out = $this->schema($this->fullSchema(), "out");
        $body = $in->validate($body, true);
        if ($this->knowledgeBaseModel->isRootCategory($id)) {
            $previousState = $this->knowledgeCategoryByID($id);
            $this->knowledgeBaseModel->checkEditPermission($previousState['knowledgeBaseID']);
            $this->knowledgeCategoryModel->update($body, ["knowledgeCategoryID" => $id]);

            $row = $this->knowledgeCategoryByID($id);
            $row = $this->normalizeOutput($row);
            $result = $out->validate($row);
        } else {
            throw new \Garden\Web\Exception\ClientException("You can patch root category only.", 409);
        }
        return $result;
    }

    /**
     * Create a new knowledge category.
     *
     * @param array $body
     * @return array
     */
    public function post(array $body = []): array {
        $this->checkPermission(KnowledgeBaseModel::EDIT_PERMISSION);

        $in = $this->schema($this->knowledgeCategoryPostSchema())
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateKBCategoriesLimit"])
            ->addValidator("parentID", [$this->knowledgeCategoryModel, "validateParentID"])
            ->setDescription("Create a new knowledge category.");
        $out = $this->schema($this->fullSchema(), "out");

        if ($body['parentID'] !== -1) {
            $parentCategory = $this->knowledgeCategoryByID($body['parentID']);
            $body['knowledgeBaseID'] = $parentCategory['knowledgeBaseID'];
        }
        $body = $in->validate($body);
        $this->knowledgeBaseModel->checkEditPermission($body['knowledgeBaseID']);
        $this->knowledgeBaseModel->checkKnowledgeBasePublished($body['knowledgeBaseID']);

        $sortInfo = $this->knowledgeCategoryModel->getMaxSortIdx($body['parentID']);
        $maxSortIndex = $sortInfo['maxSort'];
        if (!is_int($body['sort'] ?? false)) {
            $body['sort'] = $maxSortIndex + 1;
            $updateSorts = false;
        } else {
            $updateSorts = ($body['sort'] <= $maxSortIndex);
        }

        $knowledgeCategoryID = $this->knowledgeCategoryModel->insert($body);
        if ($updateSorts) {
            $this->knowledgeCategoryModel->shiftSorts(
                $body['parentID'],
                $body['sort'],
                $knowledgeCategoryID,
                KnowledgeCategoryModel::SORT_TYPE_CATEGORY
            );
        }
        if (!empty($body['parentID']) && $body['parentID'] != -1) {
            $this->knowledgeCategoryModel->updateCounts($body['parentID']);
        }
        $row = $this->knowledgeCategoryByID($knowledgeCategoryID);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }
}
