<?php if (!defined('APPLICATION')) exit();

$PageViewSum = 0;
$UserSum = 0;
$DiscussionSum = 0;
$CommentSum = 0;

function Capitalize($Word) {
   return strtoupper(substr($Word, 0, 1)).substr($Word, 1);
}
function WriteRangeTab($Range, $Sender) {
   echo Wrap(
      Anchor(
         Capitalize($Range),
         'settings?'
         .http_build_query(array(
            'Range' => $Range
            // ,'DateStart' => StatisticsPlugin::GetDateStart($Sender, $Range)
            // ,'DateEnd' => $Sender->DateEnd
         ))
      ),
      'li',
      $Range == $Sender->Range ? array('class' => 'Active') : ''
   )."\n";
}
?>
<h1>Statistics Dashboard</h1>
<div class="Tabs DateRangeTabs">
   <input type="text" name="DateRange" class="DateRange DateRangeActive" value="<?php echo Gdn_Format::Date($this->StampStart, T('Date.DefaultFormat')) . ' - ' . Gdn_Format::Date($this->StampEnd, T('Date.DefaultFormat')); ?>" />
   <input type="hidden" name="Range" class="Range" value="<?php echo $this->Range; ?>" />
   <ul>
      <?php
      WriteRangeTab(StatisticsPlugin::RESOLUTION_DAY, $this);
      WriteRangeTab(StatisticsPlugin::RESOLUTION_WEEK, $this);
      WriteRangeTab(StatisticsPlugin::RESOLUTION_MONTH, $this);
      ?>
   </ul>
</div>
<div class="Picker"></div>
<div id="GraphHolder">
   <div class="Loading"></div>
   <span class="Metrics"></span>
   <span class="Metric1"></span>
   <span class="Metric2"></span>
   <span class="Metric3"></span>
   <span class="Metric4"></span>
   <span class="Metric5"></span>
   <span class="Metric6"></span>
   <span class="Headings"></span>
   <span class="Legend"></span>
</div>
<?php
/*
<table class="GraphData AltColumns">
    <thead>
        <tr>
            <th>Date</th>
            <?php WriteData($UserData, 'Date'); ?>
        </tr>
    </thead>
    <tbody>
        <tr class="Alt">
            <th>Users</th>
            <?php WriteData($UserData); ?>
        </tr>
        <tr>
            <th>Discussions</th>
            <?php WriteData($DiscussionData); ?>
        </tr>
        <tr class="Alt">
            <th>Comments</th>
            <?php WriteData($CommentData); ?>
        </tr>
    </tbody>
</table>
*/
?>
<ul class="StatsOverview">
   <li class="PageViews">
      <div>
         Page Views
         <strong><?php echo number_format($PageViewSum); ?></strong>
      </div>
   </li>
   <?php
   /*
    TODO:
   <li class="UniqueVisitors">
      <div>
         Unique Visitors
         <strong><?php echo number_format($VisitorSum); ?></strong>
      </div>
   </li>
   */
   ?>
   <li class="NewUsers">
      <div>
         Users
         <strong><?php echo number_format($UserSum); ?></strong>
      </div>
   </li>
   <li class="NewDiscussions">
      <div>
         Discussions
         <strong><?php echo number_format($DiscussionSum); ?></strong>
      </div>
   </li>
   <li class="NewComments">
      <div>
         Comments
         <strong><?php echo number_format($CommentSum); ?></strong>
      </div>
   </li>
</ul>
<div class="DashboardSummaries">
   <div class="Loading"></div>
</div>

<script type="text/javascript">
   Loader.LoadHook('Canvas', 'available', '<?php echo $this->RaphaelLocation; ?>');
   Loader.LoadHook('Canvas', 'available', '<?php echo $this->GraphLocation; ?>');
   Loader.LoadHook('Canvas', 'available', '<?php echo $this->PickerLocation; ?>');
   
   // Delayed hook
   Loader.Hook("Canvas", "available", function(){
      GraphPicker.Attach({
         'Range': $('div.DateRangeTabs input.DateRange'),
         'Units': '<?php echo $this->Range; ?>',
         'MaxGraduations': 15,
         'MaxPageSize': -1,
         'DateStart': '<?php echo $this->BoundaryStart; ?>',
         'DateEnd': '<?php echo $this->BoundaryEnd; ?>',
         'RangeStart': '<?php echo $this->DateStart; ?>',
         'RangeEnd': '<?php echo $this->DateEnd; ?>' 
      });
   },true);
   
   Loader.Hook("Canvas", "unavailable", function() {
      $('div.DateRangeTabs').remove();
      $('ul.StatsOverview').remove();
      $('div.DashboardSummaries').remove();
      
      var GraphArea = $('div#GraphHolder');
      
      // Get rid of graph stuff
      GraphArea.html('');
      
      // Remove extra styles
      GraphArea.css('border-top', '0px');
      GraphArea.css('padding', '0px');
      GraphArea.css('text-align', 'center');
      
      // Add explanation
      GraphArea.append('<p>The <a href="http://en.wikipedia.org/wiki/Canvas_element">Canvas feature</a> is not available in your browser.</p>');
      GraphArea.append('<div>Please upgrade to <a href="http://en.wikipedia.org/wiki/HTML5">an HTML5-capable browser</a> to make use of the Statistics features.</div>');
      GraphArea.append('<div class="AlternativeBrowsers"></div>');
      
      $('<style type="text/css"> \
         div.AlternativeBrowsers { margin:15px; } \
         div.AlternativeBrowsers div.Browser{ margin: 10px; width:64px; height:64px; display:inline; } \
      </style>').appendTo("head");
      
      var AlternativeBrowsers = GraphArea.find('div.AlternativeBrowsers');
      AlternativeBrowsers.append('<div class="Browser"><a href="http://www.firefox.com" title="Mozilla Firefox"><img src="/plugins/Statistics/design/images/firefox.png"/></a></div>');
      AlternativeBrowsers.append('<div class="Browser"><a href="http://www.google.com/chrome" title="Google Chrome"><img src="/plugins/Statistics/design/images/chrome.png"/></a></div>');
      AlternativeBrowsers.append('<div class="Browser"><a href="http://www.apple.com/safari" title="Apple Safari"><img src="/plugins/Statistics/design/images/safari.png"/></a></div>');
      AlternativeBrowsers.append('<div class="Browser"><a href="http://www.opera.com" title="Opera"><img src="/plugins/Statistics/design/images/opera.png"/></a></div>');
      
      var UrlParts = ['plugin','Statistics','toggle',gdn.definition('TransientKey')];
      var DisableURL = gdn.url(UrlParts.join('/'));
      GraphArea.append('<div class="StatsQuickDisable">Alternatively, you can simply <a href="javascript:return false;">disable the Statistics plugin</a> to remove this feature entirely.</div>');
      
      $('div.StatsQuickDisable a').click(function(){
         jQuery.ajax({
            url: DisableURL,
            complete:function(){ 
               document.location.reload(); 
            }
         });
      });
      
      GraphArea.find('p').css({'font-size':'24px','margin-top':'40px'});
      GraphArea.find('div').css('font-size','11px');
   });
</script>


