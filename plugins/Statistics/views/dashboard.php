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
      <div class="Picker">
         <div class="Slider">
            <div class="SliderHandle HandleStart">6/06/09</div>
            <div class="SliderHandle HandleEnd">8/10/10</div>
            <div class="SelectedRange"></div>
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
