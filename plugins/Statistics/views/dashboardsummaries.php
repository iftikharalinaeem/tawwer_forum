<?php if (!defined('APPLICATION')) exit(); ?>
<div class="table-summary-wrap PopularDiscussionSummary">
   <table class="table-summary">
      <thead>
         <tr>
            <th><?php echo t('Popular Discussions'); ?></th>
            <th><?php echo t('Comments'); ?></th>
            <th><?php echo t('Follows'); ?></th>
            <th><?php echo t('Views'); ?></th>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data['DiscussionData'] as $Discussion) { ?>
         <tr>
            <td><?php echo anchor(htmlspecialchars($Discussion->Name), discussionUrl($Discussion)); ?></td>
            <td><?php echo number_format($Discussion->CountComments); ?></td>
            <td><?php echo number_format($Discussion->CountBookmarks); ?></td>
            <td><?php echo number_format($Discussion->CountViews); ?></td>
         </tr>
         <?php } ?>
      </tbody>
   </table>
</div>
<div class="table-summary-wrap ActiveUserSummary">
   <table class="table-summary">
      <thead>
         <tr>
            <th><?php echo t('Active Users'); ?></th>
            <th><?php echo t('Comments'); ?></th>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data['UserData'] as $User) { ?>
         <tr>
            <td><?php echo anchor($User->Name, 'profile/'.$User->UserID.'/'.Gdn_Format::Url($User->Name)); ?></td>
            <td><?php echo number_format($User->CountComments); ?></td>
         </tr>
         <?php } ?>
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
