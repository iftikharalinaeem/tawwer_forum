<?php if (!defined('APPLICATION')) exit();

$UserData = GetValue('UserData', $this->Data);
$DiscussionData = GetValue('DiscussionData', $this->Data);
$CommentData = GetValue('CommentData', $this->Data);
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
         .Attribute(array(
            'Range' => $Range,
            'DateStart' => $Sender->DateStart,
            'DateEnd' => $Sender->DateEnd)
         )
      ),
      'li',
      $Range == $Sender->Range ? array('class' => 'Active') : ''
   )."\n";
}

?>
<style type="text/css">
.DateRangeTabs {
   position: relative;
}
.DateRange {
   position: absolute;
   right: 10px;   
}
</style>
<h1>Statistics Dashboard</h1>
<div class="Tabs DateRangeTabs">
   <div class="DateRange">
      <?php echo Anchor(Gdn_Format::Date($this->StampStart, T('Date.DefaultFormat')) . ' - ' . Gdn_Format::Date($this->StampEnd, T('Date.DefaultFormat')), '#daterangepicker'); ?>
   </div>
   <ul>
      <?php
      WriteRangeTab(StatisticsPlugin::RESOLUTION_DAY, $this);
      WriteRangeTab(StatisticsPlugin::RESOLUTION_WEEK, $this);
      WriteRangeTab(StatisticsPlugin::RESOLUTION_MONTH, $this);
      ?>
   </ul>
</ul>
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
