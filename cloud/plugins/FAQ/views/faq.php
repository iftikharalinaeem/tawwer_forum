<?php if (!defined('APPLICATION')) exit();
$CategoryData = $this->data('CategoryData');
$DiscussionData = $this->data('DiscussionData');
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
   ul.TOC li:first-child ul {
      margin-top: 5px;
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
   .FAQAnswer > strong {
      font-size: 16px;
      display: block;
      margin: 0 0 10px 0;
   }
   .Answers > strong {
      font-size: 24px;
      display: block;
      margin: 40px 0 0;
      padding: 20px 0 0;
      border-top: 4px solid #f5f5f5;
   }
   .ToTop {
      font-size: 11px;
      position: absolute;
      right: 10px;
      margin-top: -25px;
   }
</style>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
function fAQList($categoryData, $discussionData) {
   $list = '';
   $depth = 1;
   foreach ($categoryData as $categoryRow) {
      $category = (object)$categoryRow;
      $categoryID = getValue('CategoryID', $category);

      if ($category->Depth < $depth) {
         // You're less deep than before, so close some open containers. 
         $unWrap = $depth - $category->Depth;
         while ($unWrap > 0) {
            $list .= '</li>';
            $list .= "\r\n";
            $list .= '</ul>';
            $list .= "\r\n";
            $unWrap--;
         }
         $list .= '</li>';
      } else if ($category->Depth > $depth) {
         // You're deeper than before, so wrap in another container.
         $list .= "\r\n";
         $list .= '<ul>';
      } else {
         if ($list != '')
            $list .= '</li>';
      }
      $list .= "\r\n";
      $list .= '<li>';
      if ($category->Depth > 1) { // Don't write out the main container info
         // $List .= wrap('['.$Category->Depth.'] '.$Category->Name, 'strong');
         $list .= wrap($category->Name, 'strong');
         if ($category->Description != '')
            $list .= wrap(Gdn_Format::text($category->Description), 'p');
      }
      
      $depth = $category->Depth;
      
      // Write out the FAQs in this category
      $qList = '';
      foreach ($discussionData as $discussion) {
         if ($discussion->CategoryID == $category->CategoryID) {
            $qList .= wrap(anchor(Gdn_Format::plainText($discussion->Name), '#Q'.$discussion->DiscussionID), 'li');
         }
      }
      $list .= $qList != '' ? wrap($qList, 'ol class="QList"') : '';
      
   }
   // Unwrap if necessary
   while ($depth > 1) {
      $list .= '</li>';
      $list .= "\r\n";
      $list .= '</ul>';
      $list .= "\r\n";
      $depth--;
   }
   $list .= '</li>';
   return wrap($list, 'ul class="TOC"');
   
}

function answerList($categoryData, $discussionData) {
   $list = '';
   foreach ($categoryData as $categoryRow) {
      $category = (object)$categoryRow;
      $categoryID = getValue('CategoryID', $category);
      
      // Write out the FAQs in this category
      $qList = '';
      foreach ($discussionData as $discussion) {
         if ($discussion->CategoryID == $category->CategoryID) {
            $qList .= '<div class="FAQAnswer" id="Q'.$discussion->DiscussionID.'">';
            $qList .= wrap(Gdn_Format::plainText($discussion->Name), 'strong');
            $qList .= wrap(Gdn_Format::to($discussion->Body, $discussion->Format), 'div class="Answer Legal"');
            $qList .= '</div>';
            $qList .= anchor('Back to Top', '#top', 'ToTop');
         }
      }
      if ($qList != '') {
         if ($category->Depth > 1)
            $list .= wrap($category->Name, 'strong');

         $list .= $qList;
      }
      
   }
   return $list;
   
}

echo "\r\n";
echo '<div class="Section">';
   echo fAQList($CategoryData, $DiscussionData);
   echo '<div class="Answers">';
      echo answerList($CategoryData, $DiscussionData);
   echo '</div>';
echo '</div>';
