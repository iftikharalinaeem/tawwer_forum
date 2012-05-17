<?php if (!defined('APPLICATION')) return;

class BBCodeRelaxed extends BBCode {
   public function HTMLEncode($string) {
      return $string;
   }
}