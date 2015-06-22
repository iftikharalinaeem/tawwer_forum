<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   select {
      border: 1px solid #ccc;
      height: 28px;
   }

   label + .RadioLabel {
      margin-left: 5px;
   }

   .SmallLabel {
      font-size: 12px;
      margin: 5px 0 0 3px;
  }
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open(), $this->Form->Errors();
?>
<ul>
   <li>
     <?php
     echo $this->Form->Label('Name', 'Name'),
//     '<div class="Info">'.''.'</div>',
      $this->Form->TextBox('Name');
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Label', 'Label'),
     '<div class="Info2">'."This label will display beside the user. It can be the same as the rank's name or have a more visual appearance. HTML is allowed.".'</div>',
      $this->Form->TextBox('Label');
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Level', 'Level'),
      '<div class="Info2">'."The level of the rank determines it's sort order. Users will always be given the highest level that they qualify for.".'</div>',
      $this->Form->TextBox('Level', array('class' => 'SmallInput'));
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('CssClass', 'CssClass'),
      '<div class="Info2">'."You can enter a css class here and it will be added to certain elements on the page. You can combine this with custom theming to add some great effects to your community.".'</div>',
      $this->Form->TextBox('CssClass');
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Body', 'Body'),
      '<div class="Info2">'."Enter a message for the users when they earn this rank. This will be put in an email so keep it to plain text.".'</div>',
      $this->Form->TextBox('Body', array('Multiline' => TRUE));
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Message', 'Message'),
      '<div class="Info2">'."Enter a message for the users that will display at the top of the page.".'</div>',
      $this->Form->TextBox('Message', array('Multiline' => TRUE));
     ?>
   </li>
</ul>
<h3>Criteria</h3>
<div class="Info">
   This section determines what a user needs to get this rank. Users must satisfy <em>all</em> of the criteria.
</div>
<ul>
   <li>
     <?php
     echo $this->Form->Label('Points', 'Criteria_Points'),
     '<div class="Info2">'."Users will need this many points to gain this rank.".'</div>',
      $this->Form->TextBox('Criteria_Points', array('class' => 'Input SmallInput'));
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Time', 'Criteria_Time'),
      '<div class="Info2">'."Users need to have been members for this length of time to gain this rank.".'</div>',
      $this->Form->TextBox('Criteria_Time', array('class' => 'Input SmallInput')),
      ' <span class="Gloss">examples: 1 day, 3 weeks, 1 month</span>';
      ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Posts', 'Criteria_CountPosts'),
     '<div class="Info2">'."Users will need this many posts to gain this rank.".'</div>',
      $this->Form->TextBox('Criteria_CountPosts', array('class' => 'Input SmallInput'));
     ?>
   </li>
   <li>
      <?php
         echo $this->Form->Label('Role', 'Criteria_Points'),
            '<div class="Info2">'."Users with the following roles will gain this rank.".'</div>';
         $Roles = RoleModel::Roles();
         $RoleNames[] = '';
         foreach($Roles as $Role) {
            $RoleNames[GetValue('Name', $Role)] = GetValue('Name', $Role);
         }
         echo $this->Form->DropDown('Criteria_Role', $RoleNames);
      ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Permission', 'Criteria_Permission'),
     '<div class="Info2">'."Users will need this permission to gain this rank.".'</div>',
      $this->Form->DropDown('Criteria_Permission', array('' => '', 'Garden.Moderation.Manage' => 'Moderator', 'Garden.Settings.Manage' => 'Administrator'));
     ?>
   </li>
   <li>
     <?php
     echo
     '<div class="Info2">'."You can have administrators manually apply ranks. This is useful if only a few people will have the rank and its criteria is subjective.".'</div>',
      $this->Form->CheckBox('Criteria_Manual', 'Applied Manually');
     ?>
   </li>
</ul>
<h3>Abilities</h3>
<div class="Info">
   This section determines what abilities users with this rank get.
</div>
<ul>
   <li>
     <?php
     echo $this->Form->Label('Start Discussions', 'Abilities_DiscussionsAdd'),
     '<div class="Info2">'."You can remove the ability to start discussions from lower-ranking members.".'</div>',
      $this->Form->RadioList('Abilities_DiscussionsAdd', array('no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Add Comments', 'Abilities_CommentsAdd'),
     '<div class="Info2">'."You can remove the ability to add comments from lower-ranking (or punished) members.".'</div>',
      $this->Form->RadioList('Abilities_CommentsAdd', array('no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Start Private Conversations', 'Abilities_ConversationsAdd'),
         '<div class="Info2">'."You can limit the ability of members to initiate new private conversations.".'</div>',
      $this->Form->RadioList('Abilities_ConversationsAdd', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
      ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Verified', 'Abilities_Verified'),
     '<div class="Info2">'."You make higher-ranking users bypass the spam checking system.".'</div>',
      $this->Form->RadioList('Abilities_Verified', array('yes' => 'bypass', 'no' => 'force check', '' => 'default'));
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Formatting Posts', 'Abilities_Format'),
         '<div class="Info2">'."You can limit the formatting options on posts for lower-ranking (or punished) members.".'</div>',
         $this->Form->RadioList('Abilities_Format', $this->Data('_Formats'));
      ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Post Links', ''),
     '<div class="Info2">'."You can take away the ability to post links to help prevent link spammers.".'</div>',

      $this->Form->Label('Activities', 'Abilities_ActivityLinks', array('class' => 'SmallLabel')),
      $this->Form->RadioList('Abilities_ActivityLinks', array('no' => 'take away', '' => 'default')),

      $this->Form->Label('@'.T('Discussions').' & '.T('Comments'), 'Abilities_CommentLinks', array('class' => 'SmallLabel')),
      $this->Form->RadioList('Abilities_CommentLinks', array('no' => 'take away', '' => 'default')),

      $this->Form->Label('Conversations', 'Abilities_ConversationLinks', array('class' => 'SmallLabel')),
      $this->Form->RadioList('Abilities_ConversationLinks', array('no' => 'take away', '' => 'default'));


     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Titles', 'Abilities_Titles'),
     '<div class="Info2">'."You can give or take away the ability to have a user title.".'</div>',
      $this->Form->RadioList('Abilities_Titles', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Locations', 'Abilities_Locations'),
         '<div class="Info2">'."You can give or take away the ability to have a user location.".'</div>',
      $this->Form->RadioList('Abilities_Locations', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
      ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Avatars', 'Abilities_Avatars'),
     '<div class="Info2">'."You can give or take away the ability to have avatars. (Requires permission to edit profile)".'</div>',
      $this->Form->RadioList('Abilities_Avatars', array('no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Signatures', 'Abilities_Signatures'),
     '<div class="Info2">'."You can give or take away the ability to have signatures. (Requires the signatures addon)".'</div>',
      $this->Form->RadioList('Abilities_Signatures', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
     <?php
     $Options = array('' => T('Default'), 'Unlimited' => T('Unlimited'), 'None' => T('None'), '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5');
     echo $this->Form->Label('Max number of images in signature', 'Abilities_SignatureMaxNumberImages');
     echo $this->Form->DropDown('Abilities_SignatureMaxNumberImages', $Options);
     ?>
   </li>
   <li>
     <?php
     $Attributes = array('class' => 'SmallInput');
     echo $this->Form->Label('Max signature length', 'Abilities_SignatureMaxLength');
     echo $this->Form->TextBox('Abilities_SignatureMaxLength', $Attributes);
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Polls', 'Abilities_Polls'),
     '<div class="Info2">'."You can give or take away the ability to add polls. (Requires the polls addon)".'</div>',
      $this->Form->RadioList('Abilities_Polls', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Me Actions', 'Abilities_MeAction'),
     '<div class="Info2">'."You can give or take away the ability to use 'me actions'.".'</div>',
      $this->Form->RadioList('Abilities_MeAction', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
     <?php
     echo $this->Form->Label('Content Curation', 'Abilities_Curation'),
     '<div class="Info2">'."You have enhanced content curation abilities. This is a good ability to give users that you want to give a little moderation ability, but not make full moderators.".'</div>',
      $this->Form->RadioList('Abilities_Curation', array('yes' => 'give', 'no' => 'take away', '' => 'default'));
     ?>
   </li>
   <li>
      <?php
         $Options = RankModel::ContentEditingOptions();
         $Fields = array('TextField' => 'Text', 'ValueField' => 'Code');
         echo $this->Form->Label('Discussion & Comment Editing', 'Abilities_EditContentTimeout');
			echo Wrap(T('EditContentTimeout.Notes', 'If a user is in a role that has permission to edit content, those permissions will override this.'), 'div', array('class' => 'Info2'));
         echo $this->Form->DropDown('Abilities_EditContentTimeout', $Options, $Fields);
      ?>
   </li>
    <li>
        <?php
        echo $this->Form->Label('Role Earned', 'Abilities_PermissionRole'),
            '<div class="Info2">'.t('Grant the permissions of this role.', "Users with this rank will gain the permissions of this role.").'</div>';
        echo $this->Form->DropDown('Abilities_PermissionRole', $this->data('_Roles'), array('IncludeNull' => true));
        ?>
    </li>
</ul>
<?php
echo '<div class="Buttons">';
echo $this->Form->Button('Save');
echo '</div>';

echo $this->Form->Close();