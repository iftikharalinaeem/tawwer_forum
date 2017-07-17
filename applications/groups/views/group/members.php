<?php if (!defined('APPLICATION')) exit();

$header = new GroupHeaderModule($this->data('Group'));
echo $header;

/** @var Gdn_Form $form */
$form = $this->Form;
$pageUrl = groupUrl($this->data('Group'), 'members');
$memberFilter = Gdn::request()->get('memberFilter');

echo $form->open(['action' => $pageUrl, 'method' => 'get']);
?>
<div class="group-members-filter-box">
    <?php echo $form->textBox('memberFilter', [
        'class' => 'InputBox',
        'value' => $memberFilter,
        'placeholder' => t('Search group members')
    ]); ?>
    <button type="submit" class="Button search" title="<?php echo t('Search'); ?>"><?php echo t('Search'); ?></button>
    <a href="<?php echo $pageUrl; ?>" class="Button" title="<?php echo t('Clear'); ?>"><?php echo t('Clear'); ?></a>
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
    'Url' => groupUrl($this->data('Group'), 'members', '/').'/{Page}?filter=members'.($memberFilter ? '&memberFilter='.$memberFilter : ''),
    'CurrentRecords' => count($this->data('Members'))
]);
