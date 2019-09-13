<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 2019-09-12
 * Time: 13:46
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;


class SubcomunitiesSiteSectionProvider implements SiteSectionProviderInterface {

    /** @var SubcommunitySiteSection */
    private $currentSubcommunity;
    
    /** @var \SubcommunityModel */
    private $subcommunityModel;

    /** @var $subcommunities */
    private $subcommunities;

    /** @var ProductModel */
    private $productModel;

    /**
     * DI.
     *
     * @param  \SubcommunityModel $subcommunityModel
     * @param  ProductModel $productModel
     */
    public function __construct(\SubcommunityModel $subcommunityModel, ProductModel $productModel) {
        $this->subcommunityModel = $subcommunityModel;
        $this->productModel = $productModel;
        $this->currentSubcommunity = new SubcommunitySiteSection($this->subcommunityModel::getCurrent(), $productModel);
        $this->subcommunities = $this->subcommunityModel::all();
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        $allSiteSections =[];
        foreach ($this->subcommunities as $subcommunity) {
            $allSiteSections[] =  new SubcommunitySiteSection($subcommunity, $this->productModel);
        }
        return $allSiteSections;
    }

    /**
     * @inheritdoc
     */
    public function getByID(int $id): ?SiteSectionInterface {
        if ($id === $this->currentSubcommunity->getSectionID()) {
            return $this->currentSubcommunity;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface {
        if ($basePath === $this->currentSubcommunity->getBasePath()) {
            return $this->currentSubcommunity;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {
        if ($localeKey === $this->currentSubcommunity->getContentLocale()) {
            return [$this->currentSubcommunity];
        } else {
            return [];
        }
    }

}
