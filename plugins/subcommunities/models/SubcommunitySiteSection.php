<?php

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Subcommunities\Models\ProductModel;


class SubcommunitySiteSection implements SiteSectionInterface {

    private $siteSectionID;

    private $siteSectionPath;

    private $siteSectionGroup;

    /** @var string */
    private $siteSectionName;

    /** @var string */
    private $locale;

    /** @var \SubcommunityModel */
    private $subcommunityModel;

    /** @var ProductModel */
    private $productModel;

    private $currentSubcommunity;

    private $sectionGroup;


    /**
     * DI.
     *
     * @param array $subcommunity
     * @param ProductModel $productModel
     */
    public function __construct(array $subcommunity, ProductModel $productModel){
        $this->productModel = $productModel;

        $this->siteSectionName = $subcommunity["Name"];
        $this->locale = $subcommunity['locale'];
        $this->sectionGroup = $this->setSectionGroup($subcommunity["ProductID"]);

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
    public function getSectionID(): int {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string {

        return $this->sectionGroup;
    }

    public function setSectionGroup(int $id): string {

        $product = $this->productModel->get(["productID" => $id]);
        $sectionGroup = $product["name"] ."". $product["ProductID"];
        return $sectionGroup;
    }

}
