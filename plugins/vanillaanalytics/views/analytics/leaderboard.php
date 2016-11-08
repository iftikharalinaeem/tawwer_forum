<?php if (!defined('APPLICATION')) exit();

$labels = $this->data('Labels');
$leaderboard = new TableSummaryModule();
$leaderboard->addColumn('record', t(val('record', $labels, 'Record')), [], TableSummaryModule::MAIN_CSS_CLASS)
    ->addColumn('count', t(val('count', $labels, 'Count')))
    ->addColumn('position', t(val('position', $labels, 'Position')))
    ->addColumn('position-previous', t(val('position-previous', $labels, 'Previous')), [], 'table-summary-highlight')
    ->addColumn('position-change', t(val('position-change', $labels, 'Change')));

foreach ($this->data('Leaderboard') as $currentRow) {
    $leaderRecord = $currentRow['LeaderRecord'];

    if (val('UserID', $currentRow)) {
        $user = Gdn::userModel()->getID(val('UserID', $currentRow));
        $recordBlock = new MediaItemModule(val('Name', $user), userUrl($user));
        $recordBlock->setView('media-sm')
            ->setImage(userPhotoUrl($user))
            ->addMeta(Gdn_Format::date(val('DateLastActive', $user), 'html'));

    } elseif (val('DiscussionID', $currentRow)) {
        $discussion = Gdn::userModel()->getID(val('UserID', $currentRow));
        $recordBlock = new MediaItemModule(val('Name', $discussion), discussionUrl($discussion));
        $recordBlock->setView('media-sm')
            ->addMeta(Gdn_Format::date(val('DateInserted', $discussion), 'html'));

    } else {
        $recordBlock = $leaderRecord['Title'];
    }

    $leaderboard->addRow([
            'record' => $recordBlock,
            'count' => $leaderRecord['Count'],
            'position' => $leaderRecord['Position'],
            'position-previous' => $leaderRecord['Previous'],
            'position-change' => $leaderRecord['PositionChange'],
        ],
        '',
        ['class' => slugify($this->title()).' '.slugify($leaderRecord['PositionChange'])]
    );
}

echo $leaderboard;

