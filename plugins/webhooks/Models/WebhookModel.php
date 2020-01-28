<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Models;

use Garden\Schema\ValidationException;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation;
use Vanilla\Webhooks\Processors\NormalizeDataProcessor;

/**
 * Class WebhookModel
 */
class WebhookModel extends PipelineModel {

    /** Marker used to indicate all events should be matched. */
    public const EVENT_WILDCARD = "*";

    /** Status flag value indicating a webhook should receive events. */
    public const STATUS_ACTIVE = "active";

    /** @var array Active webhooks, grouped by event. */
    private $activeByEvent;

    /**
     * WebhookModel constructor.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session) {
        parent::__construct('webhook');

        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $normalizeProcessor = new NormalizeDataProcessor();
        $normalizeProcessor
            ->addSerializedField('events');
        $this->addPipelineProcessor($normalizeProcessor);

        $userProcessor = new Operation\CurrentUserFieldProcessor($session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Lazy load webhooks, grouped by event type.
     *
     * @return array
     */
    private function getActiveByEvent(): array {
        if (!isset($this->activeByEvent)) {
            $rows = $this->get();

            $result = [];
            foreach ($rows as $row) {
                $events = $row["events"] ?? [];

                if (!is_array($events) || empty($events)) {
                    continue;
                } elseif (in_array(self::EVENT_WILDCARD, $events)) {
                    if (!array_key_exists(self::EVENT_WILDCARD, $result)) {
                        $result[self::EVENT_WILDCARD] = [];
                    }
                    $result[self::EVENT_WILDCARD][] = $row;
                    continue;
                }

                foreach ($events as $type) {
                    if (!is_string($type)) {
                        continue;
                    } elseif (!array_key_exists($type, $result)) {
                        $result[$type] = [];
                    }

                    $result[$type][] = $row;
                }
            }

            $this->activeByEvent = $result;
        }

        return $this->activeByEvent;
    }

    /**
     * Given an event type, return all rows configured to receive it.
     *
     * @param string $event
     * @return array
     */
    public function getByEvent(string $event): array {
        $rows = $this->getActiveByEvent();
        $result = $rows[$event] ?? [];
        $result = array_merge($result, $rows[self::EVENT_WILDCARD] ?? []);
        return $result;
    }

    /**
     * Get a single webhook by ID.
     *
     * @param int $webhookID Unique ID of the webhook.
     * @return array SQL result.
     * @throws ValidationException If the result fails schema validation.
     * @throws NoResultsException If the webhook could not be found.
     */
    public function getID(int $webhookID): array {
        return $this->selectSingle(["webhookID" => $webhookID]);
    }

    /**
     * {@inheritDoc}
     */
    public function insert(array $set) {
        $set = $this->prepareWrite($set);
        return parent::insert($set);
    }

    /**
     * Prepare data to be saved.
     *
     * @param array $set
     * @return array
     */
    private function prepareWrite(array $set): array {
        foreach ($set as $field => $value) {
            $compareField = strtolower($field);
            if ($compareField === "events" && is_array($value) && in_array("*", $value)) {
                $set[$field] = ["*"];
            }
        }
        return $set;
    }

    /**
     * {@inheritDoc}
     */
    public function update(array $set, array $where): bool {
        $set = $this->prepareWrite($set);
        return parent::update($set, $where);
    }
}
