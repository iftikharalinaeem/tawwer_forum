<?php if (!defined('APPLICATION')) exit;

Gdn::Structure()->Table('Rank');
$RankExists = Gdn::Structure()->TableExists();

Gdn::Structure()
            ->PrimaryKey('RankID')
            ->Column('Name', 'varchar(100)')
            ->Column('Level', 'smallint')
            ->Column('Label', 'varchar(255)')
            ->Column('Body', 'text', true)
            ->Column('Attributes', 'text', true)
            ->Set();

Gdn::Structure()
    ->Table('User')
    ->Column('RankID', 'int', true)
    ->Set();

// Add the activity type for ranks.
$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Rank');

// Add some default ranks.
require_once dirname(__FILE__).'/class.rankmodel.php';
$RankModel = new RankModel();

if (!$RankExists) {
    $RankModel->Save(
        ['RankID' => 1,
            'Name' => 'Level 1',
            'Label' => '✭',
            'Level' => 1]);

    $RankModel->Save(
        ['RankID' => 2,
            'Name' => 'Level 2',
            'Label' => '✭✭',
            'Level' => 2,
            'Criteria' => ['Points' => '50']]);

    $RankModel->Save(
        ['RankID' => 3,
            'Name' => 'Level 3',
            'Label' => '✭✭✭',
            'Level' => 3,
            'Criteria' => ['Points' => '100']]);

    $RankModel->Save(
        ['RankID' => 4,
            'Name' => 'Level 4',
            'Label' => '✭✭✭✭',
            'Level' => 4,
            'Criteria' => ['Points' => '500']]);

    $RankModel->Save(
        ['RankID' => 5,
            'Name' => 'Level 5',
            'Label' => '✭✭✭✭✭',
            'Level' => 5,
            'Criteria' => ['Points' => '1000']]);

    $RankModel->Save(
        ['RankID' => 100,
            'Name' => 'Moderator',
            'Label' => 'mod',
            'CssClass' => 'Rank-Mod',
            'Level' => 100,
            'Criteria' => ['Permission' => 'Garden.Moderation.Manage']]);

    $RankModel->Save(
        ['RankID' => 110,
            'Name' => 'Administrator',
            'Label' => 'admin',
            'CssClass' => 'Rank-Admin',
            'Level' => 110,
            'Criteria' => ['Permission' => 'Garden.Settings.Manage']]);
}
