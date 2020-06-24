<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Site section provider for subcommunities.
 */
class SubcomunitiesSiteSectionProvider implements SiteSectionProviderInterface {
    /** @var \SubcommunityModel */
    private $subcommunityModel;

    /** @var $subcommunities */
    private $subcommunities;

    /** @var SiteSectionInterface[] */
    private $siteSections;

    /** @var ConfigurationInterface $config */
    private $config;

    /** @var \Gdn_Router $router */
    private $router;

    /**
     * SubcomunitiesSiteSectionProvider constructor.
     *
     * @param \SubcommunityModel $subcommunityModel
     * @param ConfigurationInterface $config
     * @param \Gdn_Router $router
     */
    public function __construct(
        \SubcommunityModel $subcommunityModel,
        ConfigurationInterface $config,
        \Gdn_Router $router
    ) {
        $this->subcommunityModel = $subcommunityModel;
        $this->subcommunities = $this->subcommunityModel::all();
        $this->router = $router;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        if (is_null($this->siteSections)) {
            $this->siteSections = [];
            foreach ($this->subcommunities as $subcommunity) {
                $this->siteSections[] =  new SubcommunitySiteSection($subcommunity, $this->config, $this->router);
            }
        }
        return $this->siteSections;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentSiteSection(): ?SiteSectionInterface {
        $currentSubcommunity = $this->subcommunityModel::getCurrent();
        if (!empty($currentSubcommunity)) {
            $siteSection = new SubcommunitySiteSection($currentSubcommunity, $this->config, $this->router);
        }
        return $siteSection ?? null;
    }
}
