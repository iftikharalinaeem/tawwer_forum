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

    /**
     * Group Search Module Constructor
     * @param Gdn_Controller $sender
     * @throws Exception
     */
    public function __construct($sender) {
        parent::__construct($sender, 'groups');
        $this->setData('buttonContents', $this->buttonContents);
        $break = "here";

        if (property_exists($this, 'buttonContents')) {
            $this->ButtonContents = val('buttonContents', $sender);
        }
    }


    /**
     * Set Button Contents
     * @param string $buttonContents
     */
    public function setButtonContents($buttonContents) {
        $this->ButtonContents = $buttonContents;
    }

    /**
     * @return string
     */
    public function toString() {
        $title = t('Search Groups');
        $searchPlaceholder = t('GroupSearchPlaceHolder', 'Search Groups');
        $output = '';

        $output .= '<div class="SiteSearch groupsSearch">';
        $Form = new Gdn_Form();
        $output .= $Form->open(['action' => url('/groups/browse/search'), 'method' => 'get']);
        $output .= $Form->hidden('group_group', ['value' => '1']);

        $output .= $Form->textBox('Search', ['class' => 'InputBox BigInput groupsSearch-text js-search-groups', 'placeholder' => $searchPlaceholder, 'aria-label' => $searchPlaceholder]);

        $output .= '<button type="submit" class="Button groupsSearch-button" role="search" title="'.$title.'">';

        if ($this->buttonContents) {
            $output .= $this->buttonContents;
        } else {
            $output .= '<span class="sr-only">'.$title.'</span>';
        }

        $output .= '</button>';

        $output .= $Form->close();
        $output .= '</div>';

        return $output;
    }
}
