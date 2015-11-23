<?php
/**
 * @copyright 2011-2015 Vanilla Forums, Inc.
 * @package Badges
 */
 
/**
 * Introduces common methods that child classes can use.
 */
abstract class BadgesAppModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     *
     * @access public
     * @param string $Name Database table name.
     */
    public function __construct($Name = '') {
        parent::__construct($Name);
    }
}
