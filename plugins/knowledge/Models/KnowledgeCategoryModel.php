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
     * Add a knowledge category.
     *
     * @param array $set Field values to set.
     * @return mixed ID of the inserted row.
     * @throws \Exception If an error is encountered while performing the query.
     */
    public function insert(array $set) {
        $set["insertUserID"] = $set["updateUserID"] = $this->session->UserID;
        $set["dateInserted"] = $set["dateUpdated"] = new DateTimeImmutable("now");

        $result = parent::insert($set);
        return $result;
    }

    /**
     * Get child categories in a section, starting at a specific category.
     *
     * @param int $knowledgeCategoryID
     * @param bool $recursive
     * @return array
     */
    public function sectionChildren(int $knowledgeCategoryID, bool $recursive = true): array {
        $result = $this->get(["parentID" => $knowledgeCategoryID]);
        foreach ($result as &$row) {
            if ($recursive === false || $row["isSection"]) {
                $row["children"] = [];
                continue;
            }
            $row["children"] = $this->sectionChildren($row["knowledgeCategoryID"]);
        }
        return $result;
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

        $set["updateUserID"] = $this->session->UserID;
        $set["dateUpdated"] = new DateTimeImmutable("now");

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
     * @return string
     * @throws \Exception If the row does not contain a valid ID or name.
     */
    public function url(array $knowledgeCategory): string {
        $name = $knowledgeCategory["name"] ?? null;
        $knowledgeCategoryID = $knowledgeCategory["knowledgeCategoryID"] ?? null;

        if (!$name || !$knowledgeCategoryID) {
            throw new \Exception("Invalid knowledge category row.");
        }

        $slug = \Gdn_Format::url("{$knowledgeCategoryID}-{$name}");
        $result = \Gdn::request()->url("/kb/categories/".$slug, true);
        return $result;
    }
}
