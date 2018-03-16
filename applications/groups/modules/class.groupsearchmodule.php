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

    /**
     * GroupSearchModule constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'groups';
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
        $output .= $Form->open(['action' => url('/search'), 'method' => 'get']);
        $output .= $Form->hidden('group_group', ['value' => '1']);

        $output .= $Form->textBox('Search', ['class' => 'InputBox BigInput groupsSearch-text js-search-groups', 'placeholder' => $searchPlaceholder, 'aria-label' => $searchPlaceholder]);

        $output .= '<button type="submit" class="Button groupsSearch-button" role="search" title="'.$title.'">';
        $output .= '  <span class="sr-only">'.$title.'</span>';
        $output .= '</button>';

        $output .= $Form->close();
        $output .= '</div>';

        return $output;
    }
}
