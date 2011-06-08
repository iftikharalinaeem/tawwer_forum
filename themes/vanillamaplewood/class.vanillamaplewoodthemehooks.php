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
   	if ($Sender->MasterView == 'admin.master')
   		return;

// $web_ref = @$_SERVER['HTTP_REFERER'];
// echo "refer: ".$web_ref; //$_SERVER['HTTP_REFERER'];
   
   $Url = Url('/', TRUE);
// echo "$Url";
if (StringBeginsWith($Url, 'http://www.southorangevillage.com/forum/')) {	  
      ob_start();
      @include '/home/maple/public_html/maplewood_forum_top.php';
      $Ad = ob_get_clean();
    } elseif (StringBeginsWith($Url, 'http://sov.maplewoodonline.com/vc')) {
      ob_start();
      @include '/home/maple/public_html/southorange_forum_top.php';
      $Ad = ob_get_clean();
    } elseif (StringBeginsWith($Url, 'http://mil.maplewoodonline.com/vc')) {
      ob_start();
      @include '/home/maple/public_html/millburn_forum_top.php';
      $Ad = ob_get_clean();
    } else {
      ob_start();
      @include '/home/maple/public_html/maplewood_forum_top.php';
      $Ad = ob_get_clean();
    }

     $Sender->SetData('Ad', $Ad);     
   }

   public function DiscussionsController_BeforeRenderAsset_Handler($Sender, $Args) {
      $this->_WritePager($Sender, $Args);
   }
   public function CategoriesController_BeforeRenderAsset_Handler($Sender, $Args) {
      $this->_WritePager($Sender, $Args);
   }

   protected function _WritePager($Sender, $Args) {
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