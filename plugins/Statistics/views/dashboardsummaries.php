<?php if (!defined('APPLICATION')) exit();
$leaderboard = new TableSummaryModule();
$leaderboard->addColumn('discussions', t('Popular Discussions'))
    ->addColumn('count-comments', t('Comments'))
    ->addColumn('count-bookmarks', t('Follows'))
    ->addColumn('count-views', t('Views'));
foreach ($this->Data['DiscussionData'] as $discussion) {
   $leaderboard->addRow([
       'discussions' => anchor(htmlspecialchars($discussion->Name), discussionUrl($discussion)),
       'count-comments' => number_format($discussion->CountComments),
       'count-bookmarks' => number_format($discussion->CountBookmarks),
       'count-views' => number_format($discussion->CountViews)
   ]);
}
echo $leaderboard;

$leaderboard = new TableSummaryModule();
$leaderboard->addColumn('users', t('Active Users'))
    ->addColumn('count-comments', t('Comments'));

foreach ($this->Data['UserData'] as $user) {
   $leaderboard->addRow([
      'users' => anchor($user->Name, 'profile/'.$user->UserID.'/'.Gdn_Format::Url($user->Name)),
      'count-comments' => number_format($user->CountComments)
   ]);
}
echo $leaderboard;

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
