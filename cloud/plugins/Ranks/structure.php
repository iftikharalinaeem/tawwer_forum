<?php if (!defined('APPLICATION')) exit;

Gdn::structure()->table('Rank');
$RankExists = Gdn::structure()->tableExists();

Gdn::structure()
            ->primaryKey('RankID')
            ->column('Name', 'varchar(100)')
            ->column('Level', 'smallint')
            ->column('Label', 'varchar(255)')
            ->column('Body', 'text', true)
            ->column('Attributes', 'text', true)
            ->set();

Gdn::structure()
    ->table('User')
    ->column('RankID', 'int', true)
    ->set();

// Add the activity type for ranks.
$ActivityModel = new ActivityModel();
$ActivityModel->defineType('Rank');

// Add some default ranks.
require_once dirname(__FILE__).'/class.rankmodel.php';
$RankModel = new RankModel();

if (!$RankExists) {
    $RankModel->save(
        ['RankID' => 1,
            'Name' => 'Level 1',
            'Label' => '✭',
            'Level' => 1]);

    $RankModel->save(
        ['RankID' => 2,
            'Name' => 'Level 2',
            'Label' => '✭✭',
            'Level' => 2,
            'Criteria' => ['Points' => '50']]);

    $RankModel->save(
        ['RankID' => 3,
            'Name' => 'Level 3',
            'Label' => '✭✭✭',
            'Level' => 3,
            'Criteria' => ['Points' => '100']]);

    $RankModel->save(
        ['RankID' => 4,
            'Name' => 'Level 4',
            'Label' => '✭✭✭✭',
            'Level' => 4,
            'Criteria' => ['Points' => '500']]);

    $RankModel->save(
        ['RankID' => 5,
            'Name' => 'Level 5',
            'Label' => '✭✭✭✭✭',
            'Level' => 5,
            'Criteria' => ['Points' => '1000']]);

    $RankModel->save(
        ['RankID' => 100,
            'Name' => 'Moderator',
            'Label' => 'mod',
            'CssClass' => 'Rank-Mod',
            'Level' => 100,
            'Criteria' => ['Permission' => 'Garden.Moderation.Manage']]);

    $RankModel->save(
        ['RankID' => 110,
            'Name' => 'Administrator',
            'Label' => 'admin',
            'CssClass' => 'Rank-Admin',
            'Level' => 110,
            'Criteria' => ['Permission' => 'Garden.Settings.Manage']]);
}
