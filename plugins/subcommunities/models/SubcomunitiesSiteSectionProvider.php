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
use Vanilla\Subcommunities\Models\ProductModel;


class SubcomunitiesSiteSectionProvider implements SiteSectionProviderInterface {

    /** @var SubcommunitySiteSection */
    private $currnetSubcommunity;
    
    /** @var \SubcommunityModel */
    private $subcommunityModel;

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
        $this->currnetSubcommunity = new SubcommunitySiteSection($this->subcommunityModel::getCurrent(), $this->productModel);
    }

    /**
     * @inheritdoc
     */
    public function getAll(): array {
        return [$this->currnetSubcommunity];
    }

    /**
     * @inheritdoc
     */
    public function getByID(int $id): ?SiteSectionInterface {
        if ($id === $this->currnetSubcommunity->getSectionID()) {
            return $this->currnetSubcommunity;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface {
        if ($basePath === $this->currnetSubcommunity->getBasePath()) {
            return $this->currnetSubcommunity;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {
        if ($localeKey === $this->currnetSubcommunity->getContentLocale()) {
            return [$this->currnetSubcommunity];
        } else {
            return [];
        }
    }

}
