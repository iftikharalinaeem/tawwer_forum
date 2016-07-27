<?php if (!defined('APPLICATION')) exit(); ?>
    <h1><?php echo $this->Data('Title'); ?></h1>
<?php
$Roles = RoleModel::Roles();
$RoleNames[] = '';
foreach($Roles as $Role) {
    $RoleNames[GetValue('Name', $Role)] = GetValue('Name', $Role);
}
echo $this->Form->Open(), $this->Form->Errors();
?>
    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->Label('Name', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Name'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->Label('Label', 'Label'),
                    '<div class="info">'."This label will display beside the user. It can be the same as the rank's name or have a more visual appearance. HTML is allowed.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Label'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Level', 'Level'),
                    '<div class="info">'."The level of the rank determines it's sort order. Users will always be given the highest level that they qualify for.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Level'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('CssClass', 'CssClass'),
                    '<div class="info">'."You can enter a css class here and it will be added to certain elements on the page. You can combine this with custom theming to add some great effects to your community.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('CssClass'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Body', 'Body'),
                    '<div class="info">'."Enter a message for the users when they earn this rank. This will be put in an email so keep it to plain text.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Body', ['Multiline' => true]); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Message', 'Message'),
                    '<div class="info">'."Enter a message for the users that will display at the top of the page.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Message', ['Multiline' => true]); ?>
            </div>
        </li>
    </ul>
    <h2>Criteria</h2>
    <div class="padded">
        This section determines what a user needs to get this rank. Users must satisfy <em>all</em> of the criteria.
    </div>
    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Points', 'Criteria_Points'),
                    '<div class="info">'."Users will need this many points to gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Criteria_Points'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Time', 'Criteria_Time'),
                    '<div class="info">'."Users need to have been members for this length of time to gain this rank. (examples: 1 day, 3 weeks, 1 month)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Criteria_Time'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Posts', 'Criteria_CountPosts'),
                    '<div class="info">'."Users will need this many posts to gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Criteria_CountPosts'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->Label('Role', 'Criteria_Points'),
                    '<div class="info">'."Users with the following roles will gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('Criteria_Role', $RoleNames); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->Label('Permission', 'Criteria_Permission'),
                    '<div class="info">'."Users will need this permission to gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php $this->Form->DropDown('Criteria_Permission', array('' => '', 'Garden.Moderation.Manage' => 'Moderator', 'Garden.Settings.Manage' => 'Administrator')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo
                    '<div class="info">'."You can have administrators manually apply ranks. This is useful if only a few people will have the rank and its criteria is subjective.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->CheckBox('Criteria_Manual', 'Applied Manually'); ?>
            </div>
        </li>
    </ul>
    <h2>Abilities</h2>
    <div class="padded">
        This section determines what abilities users with this rank get.
    </div>
    <ul>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->Label('Start Discussions', 'Abilities_DiscussionsAdd'),
                    '<div class="info">'."You can remove the ability to start discussions from lower-ranking members.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_DiscussionsAdd', array('no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Add Comments', 'Abilities_CommentsAdd'),
                    '<div class="info">'."You can remove the ability to add comments from lower-ranking (or punished) members.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_CommentsAdd', array('no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Start Private Conversations', 'Abilities_ConversationsAdd'),
                    '<div class="info">'."You can limit the ability of members to initiate new private conversations.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_ConversationsAdd', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Verified', 'Abilities_Verified'),
                    '<div class="info">'."You make higher-ranking users bypass the spam checking system.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Verified', array('yes' => 'bypass', 'no' => 'force check', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Formatting Posts', 'Abilities_Format'),
                    '<div class="info">'."You can limit the formatting options on posts for lower-ranking (or punished) members.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Format', $this->Data('_Formats')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Post Links', ''),
                    '<div class="info">'."You can take away the ability to post links to help prevent link spammers.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <div class="form-group">
                    <div class="label-wrap">
                        <?php echo $this->Form->Label('Activities', 'Abilities_ActivityLinks'); ?>
                    </div>
                    <div class="input-wrap">
                        <?php echo $this->Form->RadioList('Abilities_ActivityLinks', array('no' => 'take away', '' => 'default')); ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="label-wrap">
                        <?php echo $this->Form->Label('@'.T('Discussions').' & '.T('Comments'), 'Abilities_CommentLinks'); ?>
                    </div>
                    <div class="input-wrap">
                        <?php echo $this->Form->RadioList('Abilities_CommentLinks', array('no' => 'take away', '' => 'default')); ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="label-wrap">
                        <?php echo $this->Form->Label('Conversations', 'Abilities_ConversationLinks'); ?>
                    </div>
                    <div class="input-wrap">
                        <?php echo $this->Form->RadioList('Abilities_ConversationLinks', array('no' => 'take away', '' => 'default')); ?>
                    </div>
                </div>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Titles', 'Abilities_Titles'),
                    '<div class="info">'."You can give or take away the ability to have a user title.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Titles', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Locations', 'Abilities_Locations'),
                    '<div class="info">'."You can give or take away the ability to have a user location.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Locations', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Avatars', 'Abilities_Avatars'),
                    '<div class="info">'."You can give or take away the ability to have avatars. (Requires permission to edit profile)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Avatars', array('no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Signatures', 'Abilities_Signatures'),
                    '<div class="info">'."You can give or take away the ability to have signatures. (Requires the signatures addon)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Signatures', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                $Options = array('' => T('Default'), 'Unlimited' => T('Unlimited'), 'None' => T('None'), '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5');
                echo $this->Form->Label('Max number of images in signature', 'Abilities_SignatureMaxNumberImages'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('Abilities_SignatureMaxNumberImages', $Options); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php echo $this->Form->Label('Max signature length', 'Abilities_SignatureMaxLength'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->TextBox('Abilities_SignatureMaxLength'); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Polls', 'Abilities_Polls'),
                    '<div class="info">'."You can give or take away the ability to add polls. (Requires the polls addon)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Polls', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Me Actions', 'Abilities_MeAction'),
                    '<div class="info">'."You can give or take away the ability to use 'me actions'.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_MeAction', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Content Curation', 'Abilities_Curation'),
                    '<div class="info">'."You have enhanced content curation abilities. This is a good ability to give users that you want to give a little moderation ability, but not make full moderators.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Curation', array('yes' => 'give', 'no' => 'take away', '' => 'default')); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                $Options = RankModel::ContentEditingOptions();
                $Fields = array('TextField' => 'Text', 'ValueField' => 'Code');
                ?>
                <?php echo $this->Form->Label('Discussion & Comment Editing', 'Abilities_EditContentTimeout');
                echo Wrap(T('EditContentTimeout.Notes', 'If a user is in a role that has permission to edit content, those permissions will override this.'), 'div', array('class' => 'info')); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('Abilities_EditContentTimeout', $Options, $Fields); ?>
            </div>
        </li>
        <li class="form-group row">
            <div class="label-wrap">
                <?php
                echo $this->Form->Label('Role Permissions', 'Abilities_PermissionRole'),
                    '<div class="info">'.t('Grant the permissions of this role.', "Users with this rank will gain the permissions of this role.").'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('Abilities_PermissionRole', $this->data('_Roles'), array('IncludeNull' => true)); ?>
            </div>
        </li>
    </ul>
<?php
echo '<div class="form-footer js-modal-footer">';
echo $this->Form->Button('Save');
echo '</div>';

echo $this->Form->Close();
