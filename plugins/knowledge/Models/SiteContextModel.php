<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

    /**
     * SiteContextModel constructor.
     *
     * @param \Gdn_Request $request The request to gather data from.
     */
    public function __construct(\Gdn_Request $request) {
        $this->host = $request->domain();
        $this->basePath = rtrim('/'.trim($request->webRoot(), '/'), '/');
        $this->assetPath = rtrim('/'.trim($request->assetRoot(), '/'), '/');
    }

    public function getContext(): array {
        return [
            'host' => $this->assetPath,
            'basePath' => $this->basePath,
            'assetPath' => $this->assetPath,
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
}
