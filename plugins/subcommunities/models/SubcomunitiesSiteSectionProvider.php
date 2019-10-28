<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;

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


    /**
     * SubcomunitiesSiteSectionProvider constructor.
     *
     * @param \SubcommunityModel $subcommunityModel
     */
    public function __construct(\SubcommunityModel $subcommunityModel) {
        $this->subcommunityModel = $subcommunityModel;
        $this->subcommunities = $this->subcommunityModel::all();
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        if (is_null($this->siteSections)) {
            $this->siteSections = [];
            foreach ($this->subcommunities as $subcommunity) {
                $this->siteSections[] =  new SubcommunitySiteSection($subcommunity);
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
            $siteSection = new SubcommunitySiteSection($currentSubcommunity);
        }
        return $siteSection ?? null;
    }
}
