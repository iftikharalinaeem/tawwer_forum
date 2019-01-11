<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use DateTimeImmutable;
use Garden\Schema\Schema;
use Gdn_Session;
use Vanilla\Exception\Database\NoResultsException;

/**
 * A model for managing knowledge categories.
 */
class KnowledgeCategoryModel extends \Vanilla\Models\PipelineModel {

    /** Maximum articles allowed in a guide KB root-level category. */
    const ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE = 1000;

    /** Maximum child categories allowed in a KB root-level category. */
    const ROOT_LIMIT_CATEGORIES_RECURSIVE = 500;

    /** @var int Root-level category ID. */
    const ROOT_ID = -1;

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /** @var Gdn_Session */
    private $session;

    /**
     * KnowledgeCategoryModel constructor.
     *
     * @param Gdn_Session $session
     * @param KnowledgeBaseModel $knowledgeBaseModel
     */
    public function __construct(Gdn_Session $session, KnowledgeBaseModel $knowledgeBaseModel) {
        parent::__construct("knowledgeCategory");

        $this->knowledgeBaseModel = $knowledgeBaseModel;
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
     * Configure a Garden Schema instance for write operations by the model.
     *
     * @param Schema $schema Schema representing the resource's database table.
     * @return Schema Currently configured write schema.
     */
    protected function configureWriteSchema(Schema $schema): Schema {
        $schema = parent::configureWriteSchema($schema);
        $schema->addValidator("parentID", [$this, "validateParentID"]);
        return $schema;
    }

    /**
     * Given a knowledge base ID, get all categories therein. Does not include the root category.
     *
     * @param int $knowledgeBaseID
     * @return int
     */
    private function getTotalInKnowledgeBase(int $knowledgeBaseID): int {
        $result = $this->sql()
            ->select("knowledgeCategoryID", "count", "total")
            ->from($this->getTable())
            ->where([
                "knowledgeBaseID" => $knowledgeBaseID,
                "parentID <>" => self::ROOT_ID, // Don't include the knowledge base root as part of the categories in a knowledge base.
            ])
            ->get()
            ->firstRow(DATASET_TYPE_ARRAY);
        return $result["total"] ?? 0;
    }

    /**
     * Given a category ID, get its root-level parent in the knowledge base.
     *
     * @param int $knowledgeCategoryID
     * @return array
     */
    private function selectRootCategory(int $knowledgeCategoryID): array {
        $category = $this->selectSingle(["knowledgeCategoryID" => $knowledgeCategoryID]);

        if ($category["parentID"] !== self::ROOT_ID) {
            try {
                $category = $this->selectSingle([
                    "knowledgeBaseID" => $category["knowledgeBaseID"],
                    "parentID" => self::ROOT_ID,
                ]);
            } catch (\Vanilla\Exception\Database\NoResultsException $e) {
                throw new \Vanilla\Exception\Database\NoResultsException("Root category not found.");
            }
        }

        return $category;
    }

    /**
     * Given a category ID, get the row and the rows of all its ancestors in order.
     *
     * @param int $categoryID
     * @return array
     * @throws \Garden\Schema\ValidationException If a queried row fails to validate against its output schema.
     * @throws \Vanilla\Exception\Database\NoResultsException If the target category or its ancestors cannot be found.
     */
    public function selectWithAncestors(int $categoryID): array {
        $result = [];

        do {
            $row = $this->selectSingle(["knowledgeCategoryID" => $categoryID]);
            array_unshift($result, $row);
            $categoryID = $row["parentID"];
        } while ($categoryID > 0);

        return $result;
    }

    /**
     * Update existing knowledge categories.
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @throws \Exception If an error is encountered while performing the query.
     * @return bool True.
     */
    public function update(array $set, array $where): bool {
        if (array_key_exists("parentID", $set) && array_key_exists("knowledgeCategoryID", $where)) {
            if ($set["parentID"] === $where["knowledgeCategoryID"]) {
                throw new \Garden\Web\Exception\ClientException("Cannot set the parent of a knowledge category to itself.");
            }
        }

        return parent::update($set, $where);
    }

    /**
     * Given a category ID, verify its knowledge base has not met or exceeded its limit of articles, based on the root-level category.
     *
     * @param int $knowledgeCategoryID
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public function validateKBArticlesLimit(int $knowledgeCategoryID, \Garden\Schema\ValidationField $validationField): bool {
        try {
            $category = $this->selectRootCategory($knowledgeCategoryID);
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["rootCategoryID" => $category["knowledgeCategoryID"]]);
        } catch (NoResultsException $e) {
            // Couldn't find the KB or root category. Maybe bad data. Unable to gather enough relevant data to perform validation.
            return true;
        }

        // Guides are currently the only KB type with a limit, due to the desire to keep the size of the navigation tree low.
        // Use the root category's recursive article count as a hint for the total number of articles in its knowledge base.
        if ($knowledgeBase["viewType"] === KnowledgeBaseModel::TYPE_GUIDE
            && $category["articleCountRecursive"] >= self::ROOT_LIMIT_GUIDE_ARTICLES_RECURSIVE
        ) {
            $validationField->getValidation()->addError(
                $validationField->getName(),
                "The article maximum has been reached for this knowledge base."
            );
            return false;
        }

        return true;
    }

    /**
     * Given a category ID, verify its knowledge base has not met or exceeded its limit of categories.
     *
     * @param int $knowledgeCategoryID
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public function validateKBCategoriesLimit(int $knowledgeCategoryID, \Garden\Schema\ValidationField $validationField): bool {
        try {
            $category = $this->selectSingle([
                "knowledgeCategoryID" => $knowledgeCategoryID,
            ]);
        } catch (NoResultsException $e) {
            // Couldn't find the category. Maybe bad data. Unable to gather enough relevant data to perform validation.
            return true;
        }

        $total = $this->getTotalInKnowledgeBase($category["knowledgeBaseID"]);

        if ($total >= self::ROOT_LIMIT_CATEGORIES_RECURSIVE) {
            $validationField->getValidation()->addError(
                $validationField->getName(),
                "The category maximum has been reached for this knowledge base."
            );
            return false;
        }

        return true;
    }

    /**
     * Validate the value of parentID when updating a row. Compatible with \Garden\Schema\Schema.
     *
     * @param int $parentID
     * @param \Garden\Schema\ValidationField $validationField
     * @return bool
     * @throws \Garden\Schema\ValidationException If the selected row fails output validation.
     */
    public function validateParentID(int $parentID, \Garden\Schema\ValidationField $validationField): bool {
        if ($parentID !== self::ROOT_ID) {
            try {
                $this->selectSingle(["knowledgeCategoryID" => $parentID]);
            } catch (\Vanilla\Exception\Database\NoResultsException $e) {
                $validationField->getValidation()->addError(
                    $validationField->getName(),
                    "Parent category does not exist."
                );
                return false;
            }
        }
        return true;
    }

    /**
     * Generate a URL to the provided knowledge category.
     *
     * @param array $knowledgeCategory
     * @param bool $withDomain
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $knowledgeCategory, bool $withDomain = true): string {
        $name = $knowledgeCategory["name"] ?? null;
        $knowledgeCategoryID = $knowledgeCategory["knowledgeCategoryID"] ?? null;

        if (!$name || !$knowledgeCategoryID) {
            throw new \Exception("Invalid knowledge category row.");
        }

        $slug = \Gdn_Format::url("{$knowledgeCategoryID}-{$name}");
        $result = \Gdn::request()->url("/kb/categories/".$slug, $withDomain);
        return $result;
    }

    /**
     * Build bredcrumbs array for particular knowledge category
     *
     * @param int $knowledgeCategoryID
     * @return array
     */
    public function buildBreadcrumbs(int $knowledgeCategoryID) {
        $result = [];
        if ($knowledgeCategoryID) {
            $categories = $this->selectWithAncestors($knowledgeCategoryID);
            $index = 1;
            foreach ($categories as $category) {
                $result[$index++] = new Breadcrumb(
                    $category["name"],
                    $this->url($category)
                );
            }
        }
        return $result;
    }

    /**
     * Recalculate and update articleCount, articleCountRecursive and childCategoryCount columns
     *
     * @param int $knowledgeCategoryID Category id to recalculate
     * @param bool $updateParents Flag for recursive or non-recursive mode to update all parents
     *
     * @return bool Return tru when record updated succesfully
     */
    public function updateCounts(int $knowledgeCategoryID, bool $updateParents = true): bool {
        $countCategories = $this->sql()
            ->select('c.knowledgeCategoryID')
            ->select('DISTINCT children.knowledgeCategoryID', 'COUNT', 'childrenCount')
            ->select('children.articleCountRecursive', 'SUM', 'countRecursive')
            ->from('knowledgeCategory c')
            ->leftJoin('knowledgeCategory children', 'children.parentID = c.knowledgeCategoryID')
            ->where('c.knowledgeCategoryID', $knowledgeCategoryID)
            ->groupBy('c.knowledgeCategoryID')
            ->get()->nextRow(DATASET_TYPE_ARRAY);
        $countArticles = $this->sql()
            ->select('c.knowledgeCategoryID')
            ->select('DISTINCT a.articleID', 'COUNT', 'articleCount')
            ->from('knowledgeCategory c')
            ->leftJoin('article a', 'a.knowledgeCategoryID = c.knowledgeCategoryID')
            ->where('c.knowledgeCategoryID', $knowledgeCategoryID)
            ->groupBy('c.knowledgeCategoryID')
            ->get()->nextRow(DATASET_TYPE_ARRAY);

        if (is_array($countCategories)  && is_array($countArticles)) {
            $res = $this->update(
                [
                    'articleCount' => $countArticles['articleCount'],
                    'articleCountRecursive' => ($countArticles['articleCount'] + $countCategories['countRecursive']),
                    'childCategoryCount' => $countCategories['childrenCount'],
                ],
                [
                    'knowledgeCategoryID' => $knowledgeCategoryID
                ]
            );
            if ($res && $updateParents) {
                $categories = $this->selectWithAncestors($knowledgeCategoryID);
                $ids = array_column($categories, 'knowledgeCategoryID');
                $categories = array_combine($ids, $categories);
                $cat = $categories[$knowledgeCategoryID];
                while ($parent = ($categories[$cat['parentID']] ?? false)) {
                    $res = $this->updateCounts($parent['knowledgeCategoryID'], false);
                    $cat = $parent;
                }
                return $res;
            } else {
                return $res;
            }
        } else {
            return false;
        }
    }

    /**
     * Recalculate all counts fields for all categories of knowledgeBase
     *
     * @param int $knowledgeBaseID Knowledge Base id to recalculate
     *
     * @return bool Return tru when record updated successfully
     */
    public function resetAllCounts(int $knowledgeBaseID): bool {
        $notParent = $this->sql()
            ->select('c.knowledgeCategoryID')
            ->from('knowledgeCategory c')
            ->leftJoin('knowledgeCategory children', 'children.parentID = c.knowledgeCategoryID')
            ->where('c.knowledgeBaseID', $knowledgeBaseID)
            ->where('children.knowledgeCategoryID', null)
            ->get()->resultArray();
        if (is_array($notParent)) {
            foreach ($notParent as $cat) {
                $this->updateCounts($cat['knowledgeCategoryID']);
            }
            return true;
        } else {
            return false;
        }
    }
}
