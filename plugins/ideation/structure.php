<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Stage');
$stageExists = Gdn::Structure()->TableExists();

Gdn::structure()
    ->table('Stage')
    ->primaryKey('StageID')
    ->column('Name', 'varchar(100)', false, array('unique'))
    ->column('Status', array('Open', 'Closed'))
    ->column('Description', 'text', true)
    ->column('TagID', 'int', true)
    ->set();

// Add the activity type for stages.
// $activityModel = new ActivityModel();
// $activityModel->defineType('Stage');

if (!$stageExists) {
    // Add some default statuses.
    require_once dirname(__FILE__).'/class.stagemodel.php';
    $stageModel = new StageModel();
    $stageModel->save('Open', 'Open', 'Make your vote count.');
    $stageModel->save('Resolved', 'Closed', 'We\'ve resolved this one.');
    $stageModel->save('Planned', 'Closed', 'It\'s in the works.');
    $stageModel->save('Duplicate', 'Closed', 'We\'ve seen this one before.');
    $stageModel->save('Won\'t Implement', 'Closed', 'Not a candidate.');
    $stageModel->save('Implemented', 'Closed', 'All set.');
}

$reactionModel = new ReactionModel();
$reactionModel->defineReactionType(array('UrlCode' => 'IdeaUp', 'Name' => 'Up', 'Sort' => 100, 'Class' => 'Positive', 'IncrementColumn' => 'Score', 'IncrementValue' => 1, 'Points' => 1, 'Hidden' => true, 'Active' => true,
    'Description' => "This reaction is reserved for idea upvotes."));
$reactionModel->defineReactionType(array('UrlCode' => 'IdeaDown', 'Name' => 'Down', 'Sort' => 101, 'Class' => 'Negative', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1, 'Hidden' => true, 'Active' => true,
    'Description' => "This reaction is reserved for idea downvotes."));
