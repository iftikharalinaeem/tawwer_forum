<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Status');
$statusExists = Gdn::Structure()->TableExists();

Gdn::structure()
    ->table('Status')
    ->primaryKey('StatusID')
    ->column('Name', 'varchar(100)', false, array('unique'))
    ->column('State', array('Open', 'Closed'))
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
    $statusModel->save('Active', 'Open', 1);
    $statusModel->save('Already Offered', 'Closed');
    $statusModel->save('Declined', 'Closed');
    $statusModel->save('Completed', 'Closed');
    $statusModel->save('In Progress', 'Closed');
    $statusModel->save('In Review', 'Closed');
}

Gdn::structure()
    ->table('Category')
    ->column('UseDownVotes', 'tinyint', true)
    ->set();

$reactionModel = new ReactionModel();
$reactionModel->defineReactionType(array('UrlCode' => IdeationPlugin::REACTION_UP, 'Name' => 'Up', 'Sort' => 100, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'IncrementValue' => 1, 'Points' => 1, 'Hidden' => true, 'Active' => true,
    'Description' => "This reaction is reserved for idea upvotes."));
$reactionModel->defineReactionType(array('UrlCode' => IdeationPlugin::REACTION_DOWN, 'Name' => 'Down', 'Sort' => 101, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1, 'Hidden' => true, 'Active' => true,
    'Description' => "This reaction is reserved for idea downvotes."));

saveToConfig('Garden.AttachmentsEnabled', true);
