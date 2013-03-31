<?php if (!defined('APPLICATION')) exit();
 
/**
 * Reactions controller
 * 
 * @since 1.0.0
 * @package Reputation
 */
class KidnappersController extends Gdn_Controller {
   
   public function Initialize() {
      parent::Initialize();
      $this->Form = new Gdn_Form;
      $this->Application = 'vanilla';
   }
   
}