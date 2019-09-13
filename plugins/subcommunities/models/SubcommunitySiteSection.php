<?php

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;

class SubcommunitySiteSection implements SiteSectionInterface {

    /** @var ProductModel */
    private $productModel;

    /** @var int */
    private $siteSectionID;

    /** @var string */
    private $siteSectionPath;

    /** @var string */
    private $siteSectionName;

    /** @var string */
    private $locale;

    /** @var string */
    private $sectionGroup;

    /** @var string */
    private $siteSectionUrl;

    /**
     * DI.
     *
     * @param array $subcommunity
     * @param ProductModel $productModel
     */
    public function __construct(array $subcommunity, ProductModel $productModel){
        $this->productModel = $productModel;
        $this->siteSectionName = $subcommunity["Name"];
        $this->locale = $subcommunity['Locale'];
        $this->siteSectionPath = $subcommunity["Folder"];
        $this->siteSectionUrl =$subcommunity["Url"];
        $product = $this->productModel->selectSingle(["productID" => $subcommunity["ProductID"]]);
        $this->siteSectionID = $product["productID"];
        $this->sectionGroup = $product["productID"].'_'.$product["name"];
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
        return $this->siteSectionID;
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string {
        return $this->sectionGroup;
    }


}
