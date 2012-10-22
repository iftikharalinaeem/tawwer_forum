<?php if (!defined('APPLICATION')) exit(); ?>
<div class="Summary PopularDiscussionSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Popular Discussions'); ?></th>
            <td><?php echo T('Comments'); ?></td>
            <td><?php echo T('Follows'); ?></td>
            <td><?php echo T('Views'); ?></td>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data['DiscussionData'] as $Discussion) { ?>
         <tr>
            <th><?php echo Anchor($Discussion->Name, 'discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name)); ?></th>
            <td><?php echo number_format($Discussion->CountComments); ?></td>
            <td><?php echo number_format($Discussion->CountBookmarks); ?></td>
            <td><?php echo number_format($Discussion->CountViews); ?></td>
         </tr>
         <?php } ?>
      </tbody>
   </table>
</div>
<div class="Summary ActiveUserSummary">
   <table>
      <thead>
         <tr>
            <th><?php echo T('Active Users'); ?></th>
            <!-- <td><?php echo T('Discussions'); ?></td> -->
            <td><?php echo T('Comments'); ?></td>
            <!-- <td><?php echo T('PageViews'); ?></td> -->
         </tr>
      </thead>
      <tbody>
         <?php foreach ($this->Data['UserData'] as $User) { ?>
         <tr>
            <th><?php echo Anchor($User->Name, 'profile/'.$User->UserID.'/'.Gdn_Format::Url($User->Name)); ?></th>
            <td><?php echo number_format($User->CountComments); ?></td>
            <!-- <td><?php // echo number_format($Discussion->CountViews); ?></td> -->
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