<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Garden\Web\Exception\ClientException;
use Vanilla\Exception\Database\NoResultsException;

/**
 * A model for managing knowledge-bases universal content.
 */
class KnowledgeUniversalSourceModel extends \Vanilla\Models\PipelineModel {

    /** @var KnowledgeBaseModel */
    private $knowledgeBaseModel;

    /**
     * KnowledgeUniversalSourceModel constructor.
     *
     * @param KnowledgeBaseModel $knowledgeBaseModel
     */
    public function __construct(
        KnowledgeBaseModel $knowledgeBaseModel
    ) {
        parent::__construct("knowledgeUniversalSource");
        $this->knowledgeBaseModel = $knowledgeBaseModel;
    }

    /**
     * Set the source and target knowledge-bases.
     *
     * @param array $body
     * @param int $id
     */
    public function setUniversalContent(array $body, int $id) {
        if ($body["universalTargetIDs"] ?? null) {
            foreach ($body["universalTargetIDs"] as $universalTargetID) {
                $this->validateTargetKBUniversalSource($universalTargetID);
                $where = [
                    "sourceKnowledgeBaseID" => $id,
                    "targetKnowledgeBaseID" => $universalTargetID
                ];
                $universalKBPairExists = $this->get($where);
                if (!$universalKBPairExists) {
                    $this->insert($where);
                }
            }
        }
    }

    /**
     * Validate that universal target kb exists and is not a universal source.
     *
     * @param int $id
     * @throws ClientException If universalSource status is mis-configured.
     * @throws NoResultsException  If no knowledge-base is found.
     */
    protected function validateTargetKBUniversalSource(int $id) {
        try {
            $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $id , "status" => KnowledgeBaseModel::STATUS_PUBLISHED]);
            if ($knowledgeBase["isUniversalSource"] ?? null) {
                throw new ClientException("Invalid universalTargetID, one or more target ID's are invalid.");
            }
        } catch (NoResultsException $e) {
            throw new NoResultsException("Invalid universalTargetID, one or more target ID's are invalid.");
        }
    }

    /**
     * Expand knowledge-base fragments for source and target knowledge-bases.
     *
     * @param array $rows
     * @param string $name
     */
    public function expandKnowledgeBase(array &$rows, string $name = "knowledgeBase") {
        if (count($rows) === 0) {
            // Nothing to do here.
            return;
        }
        reset($rows);
        $single = is_string(key($rows));

        $populate = function (array &$row, $name) {
            if ($row["knowledgeBaseID"] ?? null) {
                $ids = [];
                if ($name === "universalTargets") {
                    $ids = $this->getUniversalInformation("sourceKnowledgeBaseID", $row);
                } elseif ($name === "universalSources") {
                    $ids = $this->getUniversalInformation("targetKnowledgeBaseID", $row);
                }

                foreach ($ids as $id) {
                    try {
                        $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $id]);
                        if ($knowledgeBase) {
                            $row[$name][] = [
                                "knowledgeBaseID" => $knowledgeBase['knowledgeBaseID'] ?? null,
                                "name" => $knowledgeBase['name'] ?? null,
                                "description" => $knowledgeBase['description'] ?? null,
                                "icon" => $knowledgeBase['icon'] ?? null,
                                "sortArticles" => $knowledgeBase["sortArticles"] ?? null,
                                "viewType" => $knowledgeBase["viewType"] ?? null,
                                "url" => $this->knowledgeBaseModel->url($knowledgeBase),
                                "siteSectionGroup" => $knowledgeBase["siteSectionGroup"] ?? null,
                            ];
                        }
                    } catch (NoResultsException $e) {
                        logException($e);
                    }
                }
            }
        };

        if ($single) {
            $populate($rows, $name);
        } else {
            foreach ($rows as &$row) {
                $populate($row, $name);
            }
        }
    }

    /**
     * Get the universal content data based on idType.
     *
     * @param string $idType
     * @param array $record
     * @return array
     */
    public function getUniversalInformation(string $idType, array $record): array {
        $universalKBs = $this->get([$idType => $record["knowledgeBaseID"]]);
        $ids = [];
        foreach ($universalKBs as $universalKB) {
            $column = ($idType === "sourceKnowledgeBaseID") ? "targetKnowledgeBaseID" : "sourceKnowledgeBaseID";
            $ids[] = $universalKB[$column];
        }
        return $ids;
    }
}
