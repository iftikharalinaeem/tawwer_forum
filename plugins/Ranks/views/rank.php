<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
// Setup our roles list.
$Roles = RoleModel::roles();
$RoleNames[] = '';
foreach($Roles as $Role) {
    $RoleNames[val('Name', $Role)] = val('Name', $Role);
}

// Default radio button options.
$giveTakeOptions = ['yes' => 'give', 'no' => 'take away', '' => 'default'];
$takeOptions = ['no' => 'take away', '' => 'default'];

// Begin form.
echo $this->Form->open(), $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Name', 'Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Label', 'Label'),
                    '<div class="info">'."This label will display beside the user. It can be the same as the rank's name or have a more visual appearance. HTML is allowed.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Label'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Level', 'Level'),
                    '<div class="info">'."The level of the rank determines it's sort order. Users will always be given the highest level that they qualify for.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Level'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('CssClass', 'CssClass'),
                    '<div class="info">'."You can enter a css class here and it will be added to certain elements on the page. You can combine this with custom theming to add some great effects to your community.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('CssClass'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Body', 'Body'),
                    '<div class="info">'."Enter a message for the users when they earn this rank. This will be put in an email so keep it to plain text.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Body', ['Multiline' => true]); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Message', 'Message'),
                    '<div class="info">'."Enter a message for the users that will display at the top of the page.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Message', ['Multiline' => true]); ?>
            </div>
        </li>
    </ul>
    <div class="subheading-block">
        <div class="subheading-title">Criteria</div>
        <div class="subheading-description">This section determines what a user needs to get this rank. Users must satisfy <em>all</em> of the criteria.</div>
    </div>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Points', 'Criteria_Points'),
                    '<div class="info">'."Users will need this many points to gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Criteria_Points'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Time', 'Criteria_Time'),
                    '<div class="info">'."Users need to have been members for this length of time to gain this rank. (examples: 1 day, 3 weeks, 1 month)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Criteria_Time'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Posts', 'Criteria_CountPosts'),
                    '<div class="info">'."Users will need this many posts to gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Criteria_CountPosts'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Role', 'Criteria_Points'),
                    '<div class="info">'."Users with the following roles will gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->DropDown('Criteria_Role', $RoleNames); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Permission', 'Criteria_Permission'),
                    '<div class="info">'."Users will need this permission to gain this rank.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php $this->Form->dropDown('Criteria_Permission', ['' => '', 'Garden.Moderation.Manage' => 'Moderator', 'Garden.Settings.Manage' => 'Administrator']); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo
                    '<div class="info">'."You can have administrators manually apply ranks. This is useful if only a few people will have the rank and its criteria is subjective.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->checkBox('Criteria_Manual', 'Applied Manually'); ?>
            </div>
        </li>
    </ul>
    <div class="subheading-block">
        <div class="subheading-title">Abilities</div>
        <div class="subheading-description">This section determines what abilities users with this rank get.</div>
    </div>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Start Discussions', 'Abilities_DiscussionsAdd'),
                    '<div class="info">'."You can remove the ability to start discussions from lower-ranking members.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_DiscussionsAdd', $takeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Add Comments', 'Abilities_CommentsAdd'),
                    '<div class="info">'."You can remove the ability to add comments from lower-ranking (or punished) members.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_CommentsAdd', $takeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Start Private Conversations', 'Abilities_ConversationsAdd'),
                    '<div class="info">'."You can limit the ability of members to initiate new private conversations.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_ConversationsAdd', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Verified', 'Abilities_Verified'),
                    '<div class="info">'."You make higher-ranking users bypass the spam checking system.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Verified', ['yes' => 'bypass', 'no' => 'force check', '' => 'default']); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Formatting Posts', 'Abilities_Format'),
                    '<div class="info">'."You can limit the formatting options on posts for lower-ranking (or punished) members.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Format', $this->data('_Formats')); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Post Links', ''),
                    '<div class="info">'."You can take away the ability to post links to help prevent link spammers.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <div class="form-group">
                    <div class="label-wrap">
                        <?php echo $this->Form->label('Activities', 'Abilities_ActivityLinks'); ?>
                    </div>
                    <div class="input-wrap">
                        <?php echo $this->Form->RadioList('Abilities_ActivityLinks', $takeOptions); ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="label-wrap">
                        <?php echo $this->Form->label('@'.t('Discussions').' & '.t('Comments'), 'Abilities_CommentLinks'); ?>
                    </div>
                    <div class="input-wrap">
                        <?php echo $this->Form->radioList('Abilities_CommentLinks', $takeOptions); ?>
                    </div>
                </div>
                <div class="form-group">
                    <div class="label-wrap">
                        <?php echo $this->Form->label('Conversations', 'Abilities_ConversationLinks'); ?>
                    </div>
                    <div class="input-wrap">
                        <?php echo $this->Form->radioList('Abilities_ConversationLinks', $takeOptions); ?>
                    </div>
                </div>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Titles', 'Abilities_Titles'),
                    '<div class="info">'."You can give or take away the ability to have a user title.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Titles', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Locations', 'Abilities_Locations'),
                    '<div class="info">'."You can give or take away the ability to have a user location.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Locations', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Avatars', 'Abilities_Avatars'),
                    '<div class="info">'."You can give or take away the ability to have avatars. (Requires permission to edit profile)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->RadioList('Abilities_Avatars', $takeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Signatures', 'Abilities_Signatures'),
                    '<div class="info">'."You can give or take away the ability to have signatures. (Requires the signatures addon)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Signatures', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                $sigImageOptions = ['' => t('Default'), 'Unlimited' => t('Unlimited'), 'None' => t('None'), '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5'];
                echo $this->Form->label('Max number of images in signature', 'Abilities_SignatureMaxNumberImages'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('Abilities_SignatureMaxNumberImages', $sigImageOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Max signature length', 'Abilities_SignatureMaxLength'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Abilities_SignatureMaxLength'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Polls', 'Abilities_Polls'),
                    '<div class="info">'."You can give or take away the ability to add polls. (Requires the polls addon)".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Polls', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Me Actions', 'Abilities_MeAction'),
                    '<div class="info">'."You can give or take away the ability to use 'me actions'.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_MeAction', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                echo $this->Form->label('Content Curation', 'Abilities_Curation'),
                    '<div class="info">'."You have enhanced content curation abilities. This is a good ability to give users that you want to give a little moderation ability, but not make full moderators.".'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->radioList('Abilities_Curation', $giveTakeOptions); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php
                $editingOptions = RankModel::contentEditingOptions();
                $fields = ['TextField' => 'Text', 'ValueField' => 'Code'];
                ?>
                <?php echo $this->Form->label('Discussion & Comment Editing', 'Abilities_EditContentTimeout');
                echo wrap(t('EditContentTimeout.Notes', 'If a user is in a role that has permission to edit content, those permissions will override this.'), 'div', ['class' => 'info']); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('Abilities_EditContentTimeout', $editingOptions, $fields); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Role Permissions', 'Abilities_PermissionRole'),
                    '<div class="info">'.t('Grant the permissions of this role.', "Users with this rank will gain the permissions of this role.").'</div>'; ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->dropDown('Abilities_PermissionRole', $this->data('_Roles'), ['IncludeNull' => true]); ?>
            </div>
        </li>
    </ul>
<?php
echo '<div class="form-footer js-modal-footer">';
echo $this->Form->button('Save');
echo '</div>';

echo $this->Form->close();
