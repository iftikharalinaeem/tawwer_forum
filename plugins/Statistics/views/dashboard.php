<?php if (!defined('APPLICATION')) exit();

$PageViewData = GetValue('PageViewData', $this->Data);
$PageViewSum = array_sum(ConsolidateArrayValuesByKey($PageViewData, 'Value'));
// $VisitorData = GetValue('VisitorData', $this->Data);
// $VisitorSum = array_sum(ConsolidateArrayValuesByKey($VisitorData, 'Value'));
$UserData = GetValue('UserData', $this->Data);
$UserSum = array_sum(ConsolidateArrayValuesByKey($UserData, 'Value'));
$DiscussionData = GetValue('DiscussionData', $this->Data);
$DiscussionSum = array_sum(ConsolidateArrayValuesByKey($DiscussionData, 'Value'));
$CommentData = GetValue('CommentData', $this->Data);
$CommentSum = array_sum(ConsolidateArrayValuesByKey($CommentData, 'Value'));
function WriteData($Data, $Field = 'Value') {
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
   <div class="DateRange">
      <input type="text" name="DateRange" class="DateRange DateRangeActive" value="<?php echo Gdn_Format::Date($this->StampStart, T('Date.DefaultFormat')) . ' - ' . Gdn_Format::Date($this->StampEnd, T('Date.DefaultFormat')); ?>" />
      <a class="RangeToggle RangeToggleActive" href="#"><?php echo Gdn_Format::Date($this->StampStart, T('Date.DefaultFormat')) . ' - ' . Gdn_Format::Date($this->StampEnd, T('Date.DefaultFormat')); ?></a>
   </div>
   <ul>
      <?php
      WriteRangeTab(StatisticsPlugin::RESOLUTION_DAY, $this);
      WriteRangeTab(StatisticsPlugin::RESOLUTION_WEEK, $this);
      WriteRangeTab(StatisticsPlugin::RESOLUTION_MONTH, $this);
      ?>
   </ul>
</div>
<div class="Picker">
   <div class="Slider">
      <div class="SelectedRange"></div>
      <div class="HandleContainer">
         <div class="SliderHandle HandleStart">6/06/09</div>
         <div class="SliderHandle HandleEnd">8/10/10</div>
      </div>
      <div class="Range RangeStart"></div><div class="Range RangeMid"></div><div class="Range RangeEnd"></div>
      <div class="SliderDates">
         <div class="SliderDate">Jun 6</div>
         <div class="SliderDate">Aug 7</div>
         <div class="SliderDate">Oct 8</div>
         <div class="SliderDate">Dec 9</div>
         <div class="SliderDate">Feb 9</div>
         <div class="SliderDate">Apr 12</div>
         <div class="SliderDate">Jun 13</div>
      </div>
   </div>
   <hr />
   <div class="InputRange">
      <label for="DateStart" class="DateStart">Start Date</label>
      <input type="text" name="DateStart" />
      <label for="DateEnd" class="DateEnd">End Date</label>
      <input type="text" name="DateEnd" />
   </div>
</div>
<div id="GraphHolder">
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
         New Users
         <strong><?php echo number_format($UserSum); ?></strong>
      </div>
   </li>
   <li class="NewDiscussions">
      <div>
         New Discussions
         <strong><?php echo number_format($DiscussionSum); ?></strong>
      </div>
   </li>
   <li class="NewComments">
      <div>
         New Comments
         <strong><?php echo number_format($CommentSum); ?></strong>
      </div>
   </li>
</ul>
<div class="Summary PopularDiscussionSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Popular Discussions'); ?></th>
            <td><?php echo T('Comments'); ?></td>
            <td><?php echo T('Views'); ?></td>
            <td><?php echo T('% Views'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr>
            <th>Discussion Title</th>
            <td>#,###</td>
            <td>#,###</td>
            <td>##%</td>
         </tr>
      </tbody>
   </table>
</div>
<div class="Summary ActiveUserSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Active Users'); ?></th>
            <td><?php echo T('Discussions'); ?></td>
            <td><?php echo T('Comments'); ?></td>
            <td><?php echo T('PageViews'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr>
            <th>Username</th>
            <td>#,###</td>
            <td>#,###</td>
            <td>#,###</td>
         </tr>
      </tbody>
   </table>
</div>
<?php
/*
  TODO:
<div class="Summary PageViewSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Content'); ?></th>
            <td><?php echo T('Page Views'); ?></td>
            <td><?php echo T('% Page Views'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr>
            <th>/page/path</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
      </tbody>
   </table>
</div>
<div class="Summary SearchSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Search Keywords'); ?></th>
            <td><?php echo T('Searches'); ?></td>
            <td><?php echo T('% Searches'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr>
            <th>Search Keywords</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
      </tbody>
   </table>
</div>
<div class="Summary EntranceKeywordSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Entrance Keywords'); ?></th>
            <td><?php echo T('Page Views'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr>
            <th>Organic Search Keywords</th>
            <td>#,###</td>
         </tr>
      </tbody>
   </table>
</div>
<div class="Summary ReferrerSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Traffic Sources / Referrers'); ?></th>
            <td><?php echo T('Visits'); ?></td>
            <td><?php echo T('% Visits'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr class="ReservedRow">
            <th>Direct</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
         <tr>
            <th>Google (organic)</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
         <tr>
            <th>rackspace.com (referral)</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
      </tbody>
   </table>
</div>
<div class="Summary VisitorSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Visitors'); ?></th>
            <td><?php echo T('Visits'); ?></td>
            <td><?php echo T('% Visits'); ?></td>
         </tr>
      </thead>
      <tbody>
         <tr>
            <th>Member Page Views</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
         <tr>
            <th>Absolute Unique Members</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
         <tr>
            <th>Visitor Page Views</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
         <tr>
            <th>Absolute Unique Visitors</th>
            <td>#,###</td>
            <td>##%</td>
         </tr>
      </tbody>
   </table>
</div>
*/