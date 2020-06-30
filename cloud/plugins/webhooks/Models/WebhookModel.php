<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Webhooks\Models;

use Garden\Schema\ValidationException;
use Gdn_Cache as CacheInterface;
use Gdn_Session as SessionInterface;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Models\PipelineModel;
use Vanilla\Database\Operation;
use Vanilla\Webhooks\Processors\NormalizeDataProcessor;

/**
 * Class WebhookModel
 */
class WebhookModel extends PipelineModel {

    /** Cache key for stashing and retrieving active webhook rows. */
    private const ACTIVE_CACHE_KEY = "ActiveWebhooks";

    /** Marker used to indicate all events should be matched. */
    public const EVENT_WILDCARD = "*";

    /** Status flag value indicating a webhook should receive events. */
    public const STATUS_ACTIVE = "active";

    /** @var CacheInterface */
    private $cache;

    /**
     * WebhookModel constructor.
     *
     * @param SessionInterface $session
     * @param CacheInterface $cache
     */
    public function __construct(SessionInterface $session, CacheInterface $cache) {
        parent::__construct('webhook');

        $this->cache = $cache;

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
     * {@inheritDoc}
     */
    public function delete(array $where, array $options = []): bool {
        $result = parent::delete($where, $options);
        $this->cache->remove(self::ACTIVE_CACHE_KEY);
        return $result;
    }

    /**
     * Get all currently-active webhooks.
     *
     * @param bool $useCache
     * @return array
     */
    public function getActive(bool $useCache = true): array {
        if ($useCache) {
            $result = $this->cache->get(self::ACTIVE_CACHE_KEY);
            if ($result === CacheInterface::CACHEOP_FAILURE || !is_array($result)) {
                $result = $this->get(["status" => WebhookModel::STATUS_ACTIVE]);
                $this->cache->store(self::ACTIVE_CACHE_KEY, $result);
            }
        } else {
            $result = $this->get(["status" => WebhookModel::STATUS_ACTIVE]);
        }

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
    public function insert(array $set, $options = []) {
        $set = $this->prepareWrite($set);
        $result = parent::insert($set, $options);
        $this->cache->remove(self::ACTIVE_CACHE_KEY);
        return $result;
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
    public function update(array $set, array $where, string $mode = Operation::MODE_DEFAULT): bool {
        $set = $this->prepareWrite($set);
        $result = parent::update($set, $where);
        $this->cache->remove(self::ACTIVE_CACHE_KEY);
        return $result;
    }
}
