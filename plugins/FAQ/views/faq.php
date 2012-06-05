<?php if (!defined('APPLICATION')) exit();
$CategoryData = $this->Data('CategoryData');
$DiscussionData = $this->Data('DiscussionData');
?>
<style type="text/css">
   /* Main Container */
   ul.TOC {
      font-size: 14px;
   }
   ul.TOC > li > strong {
      font-size: 24px;
   }
   ul.TOC ul {
      margin: 20px 0;
   }
   ul.TOC ul li strong {
      font-size: 20px;
   }
   /* TOC Questions */
   ol.QList {
      margin: 15px 30px;
   }
   ol.QList li {
      list-style-type: decimal;
      font-size: 14px;
      margin: 4px 0;
   }
   ol.QList li > strong:first-child {
      font-size: 16px;
   }   
   .FAQAnswer {
      margin: 20px 0;
      border-top: 4px solid #f5f5f5;
      padding: 20px 0 0;
   }
   .FAQAnswer > strong:first-child {
      font-size: 16px;
      margin-bottom: 10px;
   }
   .Answers > strong {
      font-size: 24px;
      display: block;
      margin: 40px 0 0;
      padding: 20px 0 0;
      border-top: 4px solid #f5f5f5;
   }
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
function FAQList($CategoryData, $DiscussionData) {
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
      if ($Category->Depth > 1) { // Don't write out the main container info
         // $List .= Wrap('['.$Category->Depth.'] '.$Category->Name, 'strong');
         $List .= Wrap($Category->Name, 'strong');
         if ($Category->Description != '')
            $List .= Wrap(Gdn_Format::Text($Category->Description), 'p');
      }
      
      $Depth = $Category->Depth;
      
      // Write out the FAQs in this category
      $QList = '';
      foreach ($DiscussionData as $Discussion) {
         if ($Discussion->CategoryID == $Category->CategoryID) {
            $QList .= Wrap(Anchor(Gdn_Format::PlainText($Discussion->Name), '#Q'.$Discussion->DiscussionID), 'li');
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
   return Wrap($List, 'ul class="TOC"');
   
}

function AnswerList($CategoryData, $DiscussionData) {
   $List = '';
   foreach ($CategoryData as $CategoryRow) {
      $Category = (object)$CategoryRow;
      $CategoryID = GetValue('CategoryID', $Category);
      
      // Write out the FAQs in this category
      $QList = '';
      foreach ($DiscussionData as $Discussion) {
         if ($Discussion->CategoryID == $Category->CategoryID) {
            $QList .= '<div class="FAQAnswer" id="Q'.$Discussion->DiscussionID.'">';
            $QList .= Wrap(Gdn_Format::PlainText($Discussion->Name), 'strong');
            $QList .= Wrap(Gdn_Format::To($Discussion->Body, $Discussion->Format), 'div class="Answer Legal"');
            $QList .= '</div>';
            $QList .= Anchor('Back to Top', '#top', 'ToTop');
         }
      }
      if ($QList != '') {
         if ($Category->Depth > 1)
            $List .= Wrap($Category->Name, 'strong');

         $List .= $QList;
      }
      
   }
   return $List;
   
}

echo "\r\n";
echo '<div class="Section">';
   echo FAQList($CategoryData, $DiscussionData);
   echo '<div class="Answers">';
      echo AnswerList($CategoryData, $DiscussionData);
   echo '</div>';
echo '</div>';
