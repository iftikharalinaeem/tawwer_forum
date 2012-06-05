<?php if (!defined('APPLICATION')) exit();
$CategoryData = $this->Data('CategoryData');
$DiscussionData = $this->Data('DiscussionData');
?>
<style type="text/css">
   #Content ul {
      margin: 15px 15px;
   }
   #Content ul li strong {
      font-size: 24px;
   }
   ul.TOC ol {
      margin: 15px 37px;
   }
   ul.TOC ol li {
      list-style-type: decimal;
      font-size: 16px;
   }
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
function FAQList($CategoryData, $DiscussionData, $Format = 'TOC') {
   $List = '';
   $Depth = 1;
   foreach ($CategoryData as $CategoryRow) {
      $Category = (object)$CategoryRow;
      $CategoryID = GetValue('CategoryID', $Category);

      if ($Category->Depth < $Depth) {
         // You're less deep than before, so close some open containers. 
         $UnWrap = $Depth - $Category->Depth;
         while ($UnWrap > 0) {
            $List .= '</li>';
            $List .= "\r\n";
            $List .= '</ul>';
            $List .= "\r\n";
            $UnWrap--;
         }
         $List .= '</li>';
      } else if ($Category->Depth > $Depth) {
         // You're deeper than before, so wrap in another container.
         $List .= "\r\n";
         $List .= '<ul>';
      } else {
         if ($List != '')
            $List .= '</li>';
      }
      $List .= "\r\n";
      $List .= '<li>';
      // $List .= Wrap('['.$Category->Depth.'] '.$Category->Name, 'strong');
      $List .= Wrap($Category->Name, 'strong');
      if ($Category->Description != '')
         $List .= Wrap(Gdn_Format::Text($Category->Description), 'p');
      
      $Depth = $Category->Depth;
      
      // Write out the FAQs in this category
      $QList = '';
      foreach ($DiscussionData as $Discussion) {
         if ($Discussion->CategoryID == $Category->CategoryID) {
            if ($Format == 'TOC') {
               $QList .= Wrap(Anchor(Gdn_Format::PlainText($Discussion->Name), '#Q'.$Discussion->DiscussionID), 'li');
            } else {
               $QList .= Wrap(Wrap(Anchor('', '', array('name' => '#Q'.$Discussion->DiscussionID)).Gdn_Format::PlainText($Discussion->Name), 'strong').Gdn_Format::To($Discussion->Body, $Discussion->Format), 'li');
            }
         }
      }
      $List .= $QList != '' ? Wrap($QList, 'ol class="QList"') : '';
      
   }
   // Unwrap if necessary
   while ($Depth > 1) {
      $List .= '</li>';
      $List .= "\r\n";
      $List .= '</ul>';
      $List .= "\r\n";
      $Depth--;
   }
   $List .= '</li>';
   return Wrap($List, 'ul class="'.$Format.'"');
   
}

echo "\r\n";
echo FAQList($CategoryData, $DiscussionData, 'TOC');
echo '<hr />';
echo FAQList($CategoryData, $DiscussionData, 'FAQAnswers');
