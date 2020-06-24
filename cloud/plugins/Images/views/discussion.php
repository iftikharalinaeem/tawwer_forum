<?php if (!defined('APPLICATION')) exit();
// Set the video embed size for this page explicitly (in memory only).
saveToConfig('Garden.Format.EmbedSize', '594x335', ['Save' => FALSE]);

if (!function_exists('WriteCommentForm'))
   include $this->fetchViewLocation('helper_functions', 'discussion', 'vanilla');

if (!function_exists('WriteImageItem'))
   include $this->fetchViewLocation('helper_functions', '', 'plugins/Images');

// Write the page title.
echo '<div id="Item_0" class="PageTitle">';
   echo '<div class="Options">';
      $this->fireEvent('BeforeDiscussionOptions');
      writeBookmarkLink();
   echo '</div>';
   echo '<h1>'.$this->data('Discussion.Name').'</h1>';
echo "</div>\n\n";

// Write the pager
$this->Pager->Wrapper = '<span %1$s>%2$s</span>';
$PagerString = $this->Pager->toString('less');
if ($PagerString != '')
   echo wrap($PagerString, 'div class="PageControls Top"');

//echo '<div id="filetable" class="Tiles UploadFiles files" role="presentation">

echo '<div id="filetable" class="Tiles UploadFiles ImagesWrap files" role="presentation">';
   // Write the initial discussion.
   if ($this->data('Page') == 1) {
      $Discussion = (array)$this->data('Discussion');
      writeImageItem($Discussion);
   }
   
   // Write the comments
   $Comments = $this->data('Comments')->resultArray();
   foreach ($Comments as $Comment) {
      writeImageItem($Comment);
   }
   
   if($this->Pager->lastPage()) {
      $LastCommentID = $this->addDefinition('LastCommentID');
      if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
         $this->addDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   }
echo '</div>';

echo '<div class="PageControls Bottom">';
   $this->Pager->Wrapper = '<div %1$s>%2$s</div>';
   echo $this->Pager->toString('more');
echo '</div>';

include dirname(__FILE__).'/commentform.php';
include_once($this->fetchViewLocation('template', '', 'plugins/Images'));
?>
<script type="text/javascript">
   jQuery(document).ready(function($) {
      $('.Tiles').imagesLoaded(function($images, $proper, $broken) {
         $('.Tile', this).animate({ opacity: 1 });
         
         console.log('images loaded');

         this.masonry({
            itemSelector: '.ImageWrap',
            animate: true
         });
      });
      
      
      
   });
</script>