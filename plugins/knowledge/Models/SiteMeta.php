<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Knowledge\Models;

/**
 * A class for gathering particular data about the site.
 */
class SiteMeta implements \JsonSerializable {

    /** @var string */
    private $host;

    /** @var string */
    private $basePath;

    /** @var string */
    private $assetPath;

    /** @var bool */
    private $debugModeEnabled;

    /** @var string */
    private $siteTitle;

    /**
     * SiteMeta constructor.
     *
     * @param \Gdn_Request $request The request to gather data from.
     * @param \Gdn_Configuration $config The configuration object.
     */
    public function __construct(\Gdn_Request $request, \Gdn_Configuration $config) {
        $this->host = $request->domain();

        // We the roots from the request in the form of "" or "/asd" or "/asdf/asdf"
        // But never with a trailing slash.
        $this->basePath = rtrim('/'.trim($request->webRoot(), '/'), '/');
        $this->assetPath = rtrim('/'.trim($request->assetRoot(), '/'), '/');
        $this->debugModeEnabled = $config->get('Debug');

        // Get some ui metadata
        // This title may become knowledge base specific or may come down in a different way in the future.
        // For now it needs to come from some where, so I'm putting it here.
        $this->siteTitle = $config->get('Garden.Title', "");
    }

    /**
     * Return array for json serialization.
     */
    public function jsonSerialize(): array {
        return $this->getMeta();
    }

    /**
     * @return array
     */
    private function getMeta(): array {
        return [
            'context' => [
                'host' => $this->assetPath,
                'basePath' => $this->basePath,
                'assetPath' => $this->assetPath,
                'debug' => $this->debugModeEnabled,
            ],
            'ui' => [
                'siteName' => $this->siteTitle,
            ],
        ];
    }

    /**
     * @return string
     */
    public function getSiteTitle(): string {
        return $this->siteTitle;
    }

    /**
     * @return string
     */
    public function getHost(): string {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getBasePath(): string {
        return $this->basePath;
    }

    /**
     * @return string
     */
    public function getAssetPath(): string {
        return $this->assetPath;
    }

    /**
     * @return bool
     */
    public function getDebugModeEnabled(): bool {
        return $this->debugModeEnabled;
    }
}
