<?php
/**
 * @copyright 2008-2019 Vanilla Forums, Inc.
 * @license Proprietary
 */

/**
 * Groups Application - Group Search Module
 *
 * Shows a group search box.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 */
class GroupSearchModule extends Gdn_Module {

    /** @var string */
    private $buttonContents;

    /** @var string */
    private $cssClass;

    /**
     * Group Search Module Constructor
     *
     * @param Gdn_Controller $sender
     */
    public function __construct($sender) {
        parent::__construct($sender, 'groups');
    }

    /**
     * Get Button Contents. By default, the icon is set with a background image, so we add the title in for screen readers
     */
    public function getButtonContents() {
        if (is_null($this->buttonContents)) {
            return '<span class="sr-only">'.$this->getTitle().'</span>';
        } else {
            return $this->buttonContents;
        }
    }

    /**
     * Get Custom Group Search CSS Class. By default, we add .SiteSearch for compatibility, but if you want to style it yourself, we remove it so you don't need to "fight" against the default styles.
     */
    public function getCssClass() {
        $baseClass = 'groupSearch ';

        if (is_null($this->cssClass)) {
            return $baseClass.'SiteSearch';
        } else {
            return $baseClass.trim($this->cssClass);
        }
    }

    /**
     * Get Title
     *
     */
    public function getTitle() {
        return t('Search Groups');
    }

    /**
     * Set Button Contents
     *
     * @param string $buttonContents
     */
    public function setButtonContents($buttonContents) {
        $this->buttonContents = $buttonContents;
    }

    /**
     * Set Custom Group Search CSS Class
     * Note that this will remove the 'SiteSearch' class, making it easier to make a custom button
     *
     * @param string $cssClass
     */
    public function setCssClass($cssClass) {
        $this->cssClass = $cssClass;
    }

    /**
     * {@inheritdoc}
     */
    public function toString() {
        return parent::toString();
    }
}
