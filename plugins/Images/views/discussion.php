<?php if (!defined('APPLICATION')) exit();
// Set the video embed size for this page explicitly (in memory only).
SaveToConfig('Garden.Format.EmbedSize', '594x335', array('Save' => FALSE));

if (!function_exists('WriteCommentForm'))
   include $this->FetchViewLocation('helper_functions', 'discussion', 'vanilla');

if (!function_exists('WriteImageItem'))
   include $this->FetchViewLocation('helper_functions', '', 'plugins/Images');

// Write the page title.
echo '<div id="Item_0" class="PageTitle">';
   echo '<div class="Options">';
      $this->FireEvent('BeforeDiscussionOptions');
      WriteBookmarkLink();
   echo '</div>';
   echo '<h1>'.$this->Data('Discussion.Name').'</h1>';
echo "</div>\n\n";

// Write the pager
$this->Pager->Wrapper = '<span %1$s>%2$s</span>';
$PagerString = $this->Pager->ToString('less');
if ($PagerString != '')
   echo Wrap($PagerString, 'div class="PageControls Top"');

//echo '<div id="filetable" class="Tiles UploadFiles files" role="presentation">

echo '<div id="filetable" class="Tiles UploadFiles ImagesWrap files" role="presentation">';
   // Write the initial discussion.
   if ($this->Data('Page') == 1) {
      $Discussion = (array)$this->Data('Discussion');
      WriteImageItem($Discussion);
   }
   
   // Write the comments
   $Comments = $this->Data('Comments')->ResultArray();
   foreach ($Comments as $Comment) {
      WriteImageItem($Comment);
   }
   
   if($this->Pager->LastPage()) {
      $LastCommentID = $this->AddDefinition('LastCommentID');
      if(!$LastCommentID || $this->Data['Discussion']->LastCommentID > $LastCommentID)
         $this->AddDefinition('LastCommentID', (int)$this->Data['Discussion']->LastCommentID);
   }
echo '</div>';

echo '<div class="PageControls Bottom">';
   $this->Pager->Wrapper = '<div %1$s>%2$s</div>';
   echo $this->Pager->ToString('more');
echo '</div>';

include dirname(__FILE__).'/commentform.php';
include_once($this->FetchViewLocation('template', '', 'plugins/Images'));
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