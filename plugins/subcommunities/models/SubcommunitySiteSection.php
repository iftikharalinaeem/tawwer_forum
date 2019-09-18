<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Subcommunities\Models;

use Vanilla\Contracts\Site\SiteSectionInterface;

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
     * The site section path should always end with a '/'
     */
    private $siteSectionPath;

    /** @var string */
    private $siteSectionName;

    /** @var string */
    private $locale;

    /** @var string */
    private $sectionGroup;

    /**
     * DI.
     *
     * @param array $subcommunity Subcommunity model record
     * @param ProductModel $productModel
     */
    public function __construct(array $subcommunity) {
        $this->siteSectionName = $subcommunity["Name"];
        $this->locale = $subcommunity['Locale'];
        $this->siteSectionPath = $subcommunity["Folder"].'/';
        if (empty($siteGroup = $subcommunity["ProductID"])) {
            $siteGroup = self::SUBCOMMUNITY_NO_PRODUCT;
        }
        $this->siteSectionID = self::SUBCOMMUNITY_SECTION_PREFIX.$subcommunity["SubcommunityID"];
        $this->sectionGroup = self::SUBCOMMUNITY_GROUP_PREFIX.$siteGroup;
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
