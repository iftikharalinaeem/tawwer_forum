<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

class VanillaMaplewoodThemeHooks extends Gdn_Plugin {
   /**
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function Base_Render_Before($Sender, $Args) {
      ob_start();
//      include '../ad_solo.php';
      $Ad = ob_get_clean();
      $Sender->SetData('Ad', $Ad);
   }
}