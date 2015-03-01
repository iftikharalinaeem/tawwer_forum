<?php if (!defined('APPLICATION')) exit();
/**
 * Reputation Model.
 *
 * @package Reputation
 */
 
/**
 * Introduces common methods that child classes can use.
 *
 * @package Reputation
 */
abstract class ReputationModel extends Gdn_Model {
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