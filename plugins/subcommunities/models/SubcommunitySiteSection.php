<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;

class SubcommunitySiteSection implements SiteSectionInterface {
    const GROUP_PREFIX = 'subcommunitySSG-';

    /** @var ProductModel */
    private $productModel;

    /** @var int */
    private $siteSectionID;

    /**
     * @var string
     *
     * The site section path should always end with a '/'
     */
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
        $this->siteSectionPath = $subcommunity["Folder"].'/';
        $this->siteSectionUrl =$subcommunity["Url"];
        $product = $this->productModel->selectSingle(["productID" => $subcommunity["ProductID"]]);
        $this->siteSectionID = $subcommunity["SubcommunityID"];
        $this->sectionGroup = self::GROUP_PREFIX.$product["productID"];
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
