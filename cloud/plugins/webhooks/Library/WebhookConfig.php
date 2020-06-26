<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Webhooks\Library;

/**
 * Well-defined representation of a webhook configuration.
 */
class WebhookConfig {

    /** @var int */
    private $webhookID;

    /** @var string */
    private $url;

    /** @var string */
    private $secret;

    /**
     * Setup a webhook config, based on a webhook row.
     *
     * @param array $webhook Webhook row.
     */
    public function __construct(array $webhook) {
        $requiredKeys = ["secret", "url", "webhookID"];
        $missingKeys = [];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $webhook)) {
                $missingKeys[] = $key;
            }
        }
        if (!empty($missingKeys)) {
            throw new \InvalidArgumentException("Invalid webhook row. Missing keys: ".implode(", ", $missingKeys));
        }

        $this
            ->setWebhookID($webhook["webhookID"])
            ->setSecret($webhook["secret"])
            ->setUrl($webhook["url"]);
    }

    /**
     * Get the shared secret.
     *
     * @return string
     */
    public function getSecret(): string {
        return $this->secret;
    }

    /**
     * Get the target URL for this webhook.
     *
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * Get the unique ID associated with the webhook record.
     *
     * @return integer
     */
    public function getWebhookID(): int {
        return $this->webhookID;
    }

    /**
     * Configure the shared secret.
     *
     * @param string $secret
     * @return self
     */
    private function setSecret(string $secret): self {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Assign the target URL for this webhook.
     *
     * @param string $url
     * @return self
     */
    private function setUrl(string $url): self {
        $this->url = $url;
        return $this;
    }

    /**
     * Set the unique ID associated with the webhook record.
     *
     * @param integer $webhookID
     * @return self
     */
    private function setWebhookID(int $webhookID): self {
        $this->webhookID = $webhookID;
        return $this;
    }
}
