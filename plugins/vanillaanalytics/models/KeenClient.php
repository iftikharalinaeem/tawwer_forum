<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Analytics;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Analytics\ClientInterface;

/**
 * Keen analytics client.
 */
class KeenClient implements ClientInterface {

    /** @var int */
    private $projectID;

    /** @var string */
    private $writeKey;

    /**
     * Configure the instance.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $this->projectID = $config->get("VanillaAnalytics.KeenIO.ProjectID", null);
        $this->writeKey = $config->get("VanillaAnalytics.KeenIO.WriteKey", null);
        $this->tracker = $tracker;
    }

    /**
     * Get configuration details relevant to the analytics service.
     *
     * @param bool $includeDangerous Include sensitive values (i.e. read keys) in the config.
     * @return array
     */
    public function config(bool $includeDangerous = false): array {
        return [
            "projectID" => $this->projectID,
            "writeKey" => $this->writeKey,
        ];
    }

    /**
     * Get an array of default event fields (e.g. user).
     *
     * @return array
     */
    public function eventDefaults(): array {
        $validFields = ["_country", "dateTime", "ip", "keen", "site", "url", "user", "userAgent"];

        /** @var \AnalyticsTracker */
        $tracker = \AnalyticsTracker::getInstance();
        $defaultData = $tracker->getDefaultData(true, true);
        $result = array_intersect_key($defaultData, array_flip($validFields));
        return $result;
    }

    /**
     * Record a single event.
     *
     * @param array $data
     */
    public function recordEvent(array $data) {
        // To be implemented at a later date.
    }
}
