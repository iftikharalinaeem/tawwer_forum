<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

use Vanilla\Exception\Database\NoResultsException;

/**
 * A model for managing knowledge bases.
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
     * @param array $body
     * @param int $id
     */
    public function setUniversalContent(array $body, int $id) {
        if ($body["universalTargetIDs"] ?? null) {
            foreach ($body["universalTargetIDs"] as $universalTargetID) {
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
                    $allSourceTargets = $this->get(["sourceKnowledgeBaseID" => $row["knowledgeBaseID"]]);
                    foreach ($allSourceTargets as $sourceTarget) {
                        $ids = $sourceTarget["targetKnowledgeBaseID"];
                    }
                } elseif ($name === "universalSources") {
                    $allSourceTargets = $this->get(["targetKnowledgeBaseID" => $row["knowledgeBaseID"]]);
                    foreach ($allSourceTargets as $sourceTarget) {
                        $ids[] = $sourceTarget["sourceKnowledgeBaseID"];
                    }
                }

                foreach ($ids as $id) {
                    try {
                        $knowledgeBase = $this->knowledgeBaseModel->selectSingle(["knowledgeBaseID" => $id]);
                        if ($knowledgeBase) {
                            $row[$name][] = [
                                "knowledgeBaseID" => $knowledgeBase['knowledgeBaseID'],
                                "name" => $knowledgeBase['name'],
                                "rootCategoryID" => $knowledgeBase["rootCategoryID"]
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

}
