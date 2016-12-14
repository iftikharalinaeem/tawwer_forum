<?php if (!defined('APPLICATION')) exit();

$header = new GroupHeaderModule($this->data('Group'));
echo $header;

/** @var Gdn_Form $form */
$form = $this->Form;

echo $form->open();
echo $form->errors();

?>
<div class="group-members-filter-box">
    <?php echo $form->label('Members Name Filter', 'memberFilter'); ?>
    <?php echo $form->textBox('memberFilter', ['class' => 'InputBox']); ?>
    <button type="submit" class="Button search" title="<?php echo t('Search');?>"><?php echo sprite('SpSearch');?></button>
</div>
<?php

echo $form->close();

if (in_array($this->data('Filter'), ['', 'leaders'])) {
    $leaderList = new MemberListModule(
        $this->data('Leaders'),
        $this->data('Group'),
        t('Leaders'),
        t('GroupEmptyLeaders', 'There are no group leaders.')
    );
    echo $leaderList;
}

if (in_array($this->data('Filter'), ['', 'members'])) {

    $memberList = new MemberListModule(
        $this->data('Members'),
        $this->data('Group'),
        t('Members'),
        t('GroupEmptyMembers', 'There are no group members.')
    );
    echo $memberList;
}

PagerModule::write([
    'Url' => groupUrl($this->data('Group'), 'members', '/').'/{Page}?filter=members',
    'CurrentRecords' => count($this->data('Members'))
]);
