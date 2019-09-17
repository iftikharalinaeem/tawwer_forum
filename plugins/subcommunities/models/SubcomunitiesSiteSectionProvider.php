<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Site\DefaultSiteSection;


class SubcomunitiesSiteSectionProvider implements SiteSectionProviderInterface {

    /** @var SubcommunitySiteSection */
    private $currentSiteSection;

    private $currentSubcommunity;

    private $defaultSubcommunity;
    
    /** @var \SubcommunityModel */
    private $subcommunityModel;

    /** @var $subcommunities */
    private $subcommunities;

    /** @var ProductModel */
    private $productModel;

    /** @var SiteSectionInterface[] */
    private $allSiteSections;

    /** @var DefaultSiteSection */
    private $defaultSiteSection;

    /**
     * SubcomunitiesSiteSectionProvider constructor.
     *
     * @param \SubcommunityModel $subcommunityModel
     * @param ProductModel $productModel
     * @param DefaultSiteSection $defaultSiteSection
     */
    public function __construct(\SubcommunityModel $subcommunityModel, ProductModel $productModel, DefaultSiteSection $defaultSiteSection) {
        $this->defaultSiteSection = $defaultSiteSection;
        $this->subcommunityModel = $subcommunityModel;
        $this->productModel = $productModel;
        $this->subcommunities = $this->subcommunityModel::all();
        $this->allSiteSections = $this->getAll();
        $this->defaultSubcommunity = $this->subcommunityModel::getDefaultSite();
        $this->currentSubcommunity = $this->subcommunityModel::getCurrent();

        if (!empty($this->currentSubcommunity)) {
            $this->currentSiteSection = new SubcommunitySiteSection($this->currentSubcommunity, $productModel);
        }
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        $allSiteSections =[$this->defaultSiteSection];
        foreach ($this->subcommunities as $subcommunity) {
            $allSiteSections[] =  new SubcommunitySiteSection($subcommunity, $this->productModel);
        }
        return $allSiteSections;
    }

    /**
     * @inheritdoc
     */
    public function getByID(int $id): ?SiteSectionInterface {
        if ($subCommunity = $this->subcommunityModel->getID($id)) {
            return new SubcommunitySiteSection($subCommunity, $this->productModel);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface {
        if ($subCommunity = $this->subcommunityModel->getSite($basePath)) {
            return new SubcommunitySiteSection($subCommunity, $this->productModel);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {
        $siteSections =[];
        $subCommunities = $this->subcommunityModel->getWhere(['locale' => $localeKey]);
        foreach ($subCommunities as $subCommunity) {
            $siteSections[] =  new SubcommunitySiteSection($subCommunity, $this->productModel);
        }
        return $siteSections;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentSiteSection(): SiteSectionInterface {
        return $this->currentSiteSection ?? $this->defaultSiteSection;
    }
}
