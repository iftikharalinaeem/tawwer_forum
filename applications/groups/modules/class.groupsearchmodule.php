<?php
/**
 * @copyright 2008-2016 Vanilla Forums, Inc.
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

    private $buttonContents;
    private $cssClass;

    /**
     * Group Search Module Constructor
     * @param Gdn_Controller $sender
     * @throws Exception
     */
    public function __construct($sender) {
        parent::__construct($sender, 'groups');
    }


    /**
     * Set Button Contents
     * @param string $buttonContents
     */
    public function setButtonContents($buttonContents) {
        $this->buttonContents = $buttonContents;
    }

    /**
     * Set Custom Group Search CSS Class
     * Note that this will remove the 'SiteSearch' class, making it easier to make a custom button
     * @param string $cssClass
     */
    public function setCssClass($cssClass) {
        $this->cssClass = $cssClass;
    }

    /**
     * @return string
     */
    public function toString() {
        $title = t('Search Groups');
        $searchPlaceholder = t('GroupSearchPlaceHolder', 'Search Groups');
        $output = '';

        $value = ''; //Todo, load current search value if applicable

        $moduleClasses = 'groupSearch ';

        if ($this->cssClass) {
            $moduleClasses .= trim($this->cssClass);
        } else {
            $moduleClasses .= 'SiteSearch';
        }

        $output .= '<div class="'.$moduleClasses.'">';
        $Form = new Gdn_Form();
        $output .= $Form->open(['action' => url('/groups/browse/search'), 'method' => 'get']);
        $output .= $Form->hidden('group_group', ['value' => '1']);

        $output .= '<div class="groupSearch-search">';
        $output .= $Form->textBox('Search', ['class' => 'InputBox BigInput groupSearch-text js-search-groups', 'placeholder' => $searchPlaceholder, 'aria-label' => $searchPlaceholder]);

        $output .= '<button type="submit" class="Button groupSearch-button" role="search" title="'.$title.'">';

        if ($this->buttonContents) {
            $output .= $this->buttonContents;
        } else {
            $output .= '<span class="sr-only">'.$title.'</span>';
        }

        $output .= '</button>';
        $output .= '</div>';

        $output .= $Form->close();
        $output .= '</div>';

        return $output;
    }
}
