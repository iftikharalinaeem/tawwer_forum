<?php if (!defined('APPLICATION')) exit();

$UserData = GetValue('UserData', $this->Data);
$DiscussionData = GetValue('DiscussionData', $this->Data);
$CommentData = GetValue('CommentData', $this->Data);
function WriteData($Data, $Field = 'Value') {
   $Alt = 0;
   foreach ($Data as $Date => $Row) {
      $Alt = $Alt == 0 ? 1 : 0;
      $Val = GetValue($Field, $Row, 0);
      if ($Field == 'Date')
         $Val = date('m d', strtotime($Val));
         
      echo Wrap($Val, 'td', $Alt ? array('class' => 'Alt') : '');
   }
}
?>
<h1>Statistics Dashboard</h1>
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
            <?php
            $Alt = 0;
            foreach ($UserData as $Date => $Data) {
               $Alt = $Alt == 0 ? 1 : 0;
               echo Wrap($Date, 'td', Alt($Alt));
            }
            ?>
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
