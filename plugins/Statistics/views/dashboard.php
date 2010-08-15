<?php if (!defined('APPLICATION')) exit();

/*function WriteData($Data, $Field = 'Value') {
   $Alt = 0;
   foreach ($Data as $Date => $Row) {
      $Alt = $Alt == 0 ? 1 : 0;
      $Val = GetValue($Field, $Row, 0);
      if ($Field == 'Date') {
         $Date = Gdn_Format::ToTimestamp($Val);
         $Val = date(date('Y', $Date) < date('Y') ? 'M d, Y' : 'M d', strtotime($Val));
      }
         
      echo Wrap($Val, 'td', $Alt ? array('class' => 'Alt') : '');
   }
}
*/
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
 <script type="text/javascript">
   jQuery(document).ready(function(){
      var GraphPicker = new Picker();
      GraphPicker.Attach({
         'Range': $('div.DateRange input.DateRange'),
         'Units': 'hour',
         'DateStart': 'June 15th, 2010',
         'DateEnd': 'August 12th, 2010'
      });
   });
</script>
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
<?php echo $this->FetchView(PATH_PLUGINS.'/Statistics/views/dashboardsummaries.php'); ?>
</div>