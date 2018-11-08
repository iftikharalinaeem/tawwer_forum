<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use DateTimeImmutable;
use Garden\Schema\Schema;
use Gdn_Session;

/**
 * A model for managing knowledge categories.
 */
class KnowledgeCategoryModel extends \Vanilla\Models\PipelineModel {

    /** @var int Root-level category ID. */
    const ROOT_ID = -1;

    /** @var Gdn_Session */
    private $session;

    /**
     * KnowledgeCategoryModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("knowledgeCategory");
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
     * Delete knowledge categories.
     *
     * @param array $where
     * @param int $limit
     */
    public function delete(array $where, int $limit = 1) {
        $this->sql()->delete(
            $this->getTable(),
            $where,
            $limit
        );
    }

    /**
     * Get the full knowledge category tree containing the target category.
     *
     * @param int $knowledgeCategoryID
     * @return array
     * @throws \Garden\Schema\ValidationException If a queried row fails to validate against its output schema.
     */
    public function sectionTree(int $knowledgeCategoryID): array {
        // Search upward to get the container section.
        do {
            $result = $this->selectSingle(["knowledgeCategoryID" => $knowledgeCategoryID]);
            $knowledgeCategoryID = $result["parentID"];
        } while (!$result["isSection"]);

        // Fetch all child categories in this section.
        $result["children"] = $this->sectionChildren($result["knowledgeCategoryID"]);
        return $result;
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
}
