<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Site\SiteSectionSchema;
use Vanilla\Contracts\ConfigurationInterface;
use Gdn_Router;

/**
 * Site section implementation for Subcommunities.
 */
class SubcommunitySiteSection implements SiteSectionInterface {

    /** @const string Site section prefix */
    const SUBCOMMUNITY_SECTION_PREFIX = 'subcommunities-section-';

    /** @const string Site section group prefix */
    const SUBCOMMUNITY_GROUP_PREFIX = 'subcommunities-group-';

    /** @const string Site section group prefix */
    const SUBCOMMUNITY_NO_PRODUCT = 'no-product';

    /** @var int */
    private $siteSectionID;

    /**
     * @var string
     *
     * The site section path should always start with a '/'
     */
    private $siteSectionPath;

    /** @var string */
    private $siteSectionName;

    /** @var string */
    private $locale;

    /** @var string */
    private $sectionGroup;

    /** @var ConfigurationInterface $config */
    private $config;

    /** @var Gdn_Router $router */
    private $router;

    /** @var array $defaultRoute */
    private $defaultRoute;

    /**
     * DI.
     *
     * @param array $subcommunity Subcommunity model record
     * @param ConfigurationInterface $config
     * @param Gdn_Router $router
     */
    public function __construct(
        array $subcommunity,
        ConfigurationInterface $config,
        Gdn_Router $router
    ) {
        $this->siteSectionName = $subcommunity["Name"];
        $this->locale = $subcommunity['Locale'];
        $this->siteSectionPath = '/'.$subcommunity["Folder"];
        $this->sectionGroup = ProductModel::makeSiteSectionGroupKey($subcommunity['ProductID'] ?? null);
        $this->siteSectionID = self::SUBCOMMUNITY_SECTION_PREFIX.$subcommunity["SubcommunityID"];
        $configDefaultController = !empty($subcommunity["defaultController"])
            ? $subcommunity["defaultController"]
            : $config->get('Routes.DefaultController');
        $this->defaultRoute = $router->parseRoute($configDefaultController);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return SiteSectionSchema::toArray($this);
    }

    /**
     * @inheritdoc
     */
    public function getBasePath(): string {
        return $this->siteSectionPath;
    }

    /**
     * @inheritdoc
     */
    public function getContentLocale(): string {
        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string {
        return $this->siteSectionName;
    }

    /**
     * @inheritdoc
     */
    public function getSectionID(): string {
        return $this->siteSectionID;
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string {
        return $this->sectionGroup;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultRoute(): array {
        return $this->defaultRoute;
    }
}
