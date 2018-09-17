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
class SiteContextModel {

    /** @var string */
    private $host;

    /** @var string */
    private $basePath;

    /** @var string */
    private $assetPath;

    /** @var bool */
    private $debugModeEnabled;

    /**
     * SiteContextModel constructor.
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
    }

    /**
     * @return array
     */
    public function getContext(): array {
        return [
            'host' => $this->assetPath,
            'basePath' => $this->basePath,
            'assetPath' => $this->assetPath,
            'debug' => $this->debugModeEnabled,
        ];
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
