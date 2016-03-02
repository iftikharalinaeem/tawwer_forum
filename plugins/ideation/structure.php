<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Status');
$statusExists = Gdn::Structure()->TableExists();

Gdn::structure()
    ->table('Status')
    ->primaryKey('StatusID')
    ->column('Name', 'varchar(100)', false, array('unique'))
    ->column('State', array('Open', 'Closed'))
    ->column('TagID', 'int', true)
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
    $statusModel->save('Open', 'Open');
    $statusModel->save('Resolved', 'Closed');
    $statusModel->save('Planned', 'Closed');
    $statusModel->save('Duplicate', 'Closed');
    $statusModel->save('Won\'t Implement', 'Closed');
    $statusModel->save('Implemented', 'Closed');
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

touchConfig('Plugins.Ideation.DefaultStatusID', 1);
saveToConfig('Garden.AttachmentsEnabled', true);
