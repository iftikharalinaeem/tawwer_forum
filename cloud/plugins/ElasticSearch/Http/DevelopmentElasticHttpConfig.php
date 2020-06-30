<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cloud\ElasticSearch\Http;

use Garden\Web\Exception\ServerException;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Development version of the elastic http config.
 */
class DevElasticHttpConfig extends AbstractElasticHttpConfig {

    const CONFIG_ACCOUNT_ID = 'ElasticDev.AccountID';
    const CONFIG_SITE_ID = 'ElasticDev.SiteID';
    const CONFIG_API_SECRET = 'ElasticDev.Secret';

    /** @var int */
    private $accountID;

    /** @var int */
    private $siteID;

    /** @var int */
    private $secret;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config) {
        $secret = $config->get(self::CONFIG_API_SECRET, null);
        if ($secret === null) {
            throw new ServerException(
                'Unable to run the dev elastic instance without configuring `ElasticDev.Secret`. You can find this value in 1password.'
            );
        }

        $accountID = $config->get(self::CONFIG_ACCOUNT_ID, null);
        if ($accountID === null) {
            // Generate a new accountID.
            $accountID = random_int(50000000, 59999999);
            $config->saveToConfig(self::CONFIG_ACCOUNT_ID, $accountID);
        }

        $siteID = $config->get(self::CONFIG_SITE_ID, null);
        if ($siteID === null) {
            // Generate a new accountID.
            $siteID = random_int(50000000, 59999999);
            $config->saveToConfig(self::CONFIG_SITE_ID, $siteID);
        }

        $this->accountID = $accountID;
        $this->siteID = $siteID;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string {
        return 'https://ms-vanilla-search-api-dev.v-fabric.net/api/v1.0/';
    }

    /**
     * @return array
     */
    protected function getTokenPayload(): array {
        return [
            // Set whatever int you want here. Check the cluster forehand to not use the same as another dev.
            'accountId' => $this->accountID,
            'siteId' => $this->siteID,
        ];
    }

    /**
     * @return string
     */
    protected function getSecret(): string {
        return '{{DEV_SEARCH_API_SECRET}}';
    }
}
