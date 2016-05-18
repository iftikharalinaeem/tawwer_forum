<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Status');
$statusExists = Gdn::Structure()->TableExists();

Gdn::structure()
    ->table('Status')
    ->primaryKey('StatusID')
    ->column('Name', 'varchar(100)', false, array('unique'))
    ->column('State', ['Open', 'Closed'])
    ->column('TagID', 'int', true)
    ->column('IsDefault', 'tinyint')
    ->set();

// Add the activity type for statuses.
$activityModel = new ActivityModel();
$activityModel->defineType('AuthorStatus');
$activityModel = new ActivityModel();
$activityModel->defineType('VoterStatus');

if (!$statusExists) {
    // Add some default statuses.
    require_once dirname(__FILE__).'/class.statusmodel.php';
    $statusModel = new StatusModel();
    $statusModel->upsert(t('Active'), 'Open', 1);
    $statusModel->upsert(t('Already Offered'), 'Closed');
    $statusModel->upsert(t('Declined'), 'Closed');
    $statusModel->upsert(t('Completed'), 'Closed');
    $statusModel->upsert(t('In Progress'), 'Closed');
    $statusModel->upsert(t('In Review'), 'Open');
}

Gdn::structure()
    ->table('Category')
    ->column(IdeationPlugin::CATEGORY_IDEATION_COLUMN_NAME, [IdeationPlugin::CATEGORY_TYPE_UP, IdeationPlugin::CATEGORY_TYPE_UP_AND_DOWN], true)
    ->set();

// Make sure we've got the needed reactions
$reactionModel = new ReactionModel();
$reactionModel->defineReactionType(['UrlCode' => 'Down', 'Name' => 'Vote Down', 'Sort' => 7, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => 0,
    'Description' => "A down vote is a general disapproval of a post. Enough down votes will bury a post."]);
$reactionModel->defineReactionType(['UrlCode' => 'Up', 'Name' => 'Vote Up', 'Sort' => 8, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'Points' => 1,
    'Description' => "An up vote is a general approval of a post. Enough up votes will promote a post."]);

saveToConfig('Garden.AttachmentsEnabled', true);
