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

   public function DiscussionsController_BeforeRenderAsset_Handler($Sender, $Args) {
      if ($Args['AssetName'] != 'Content')
         return;

      static $Rendered = FALSE;

      if (!$Rendered) {
         // This logic is from /applications/vanilla/views/discussions/index.php
         $PagerOptions = array('RecordCount' => $Sender->Data('CountDiscussions'));
         if ($Sender->Data('_PagerUrl')) {
            $PagerOptions['Url'] = $Sender->Data('_PagerUrl');
         }
         echo PagerModule::Write($PagerOptions);
         $Rendered = TRUE;
      }
   }

   public function DiscussionController_BeforeRenderAsset_Handler($Sender, $Args) {
      if ($Args['AssetName'] != 'Content' || !isset($Sender->Pager))
         return;

      static $Rendered = FALSE;

      if (!$Rendered) {
         echo $Sender->Pager->ToString('less');
         $Rendered = TRUE;
      }
   }
}