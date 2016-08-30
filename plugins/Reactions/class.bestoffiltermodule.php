<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Renders the best of filter menu
 */
class BestOfFilterModule extends Gdn_Module {

    /**
     *
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     *
     *
     * @param $Name
     * @param $Code
     * @param $CurrentReactionType
     * @return string
     */
    private function button($Name, $Code, $CurrentReactionType) {
        $LCode = strtolower($Code);
        $Url = url("/bestof/$LCode");
        $CssClass = $Code;
        if ($CurrentReactionType == $LCode) {
            $CssClass .= ' Active';
        }

        return '<li class="BestOf'.$CssClass.'"><a href="'.$Url.'"><span class="ReactSprite React'.$Code.'"></span> '.$Name.'</a></li>';
    }

    /**
     *
     *
     * @return string
     */
    public function toString() {
        $Controller = Gdn::controller();
        $CurrentReactionType = $Controller->data('CurrentReaction');
        $ReactionTypeData = $Controller->data('ReactionTypes');
        $FilterMenu = '<div class="BoxFilter BoxBestOfFilter"><ul class="FilterMenu">';

        $FilterMenu .= $this->button(t('Everything'), 'Everything', $CurrentReactionType);
        foreach ($ReactionTypeData as $Key => $ReactionType) {
            $FilterMenu .= $this->button(t(val('Name', $ReactionType, '')), val('UrlCode', $ReactionType, ''), $CurrentReactionType);
        }

        $FilterMenu .= '</ul></div>';
        return $FilterMenu;
    }
}