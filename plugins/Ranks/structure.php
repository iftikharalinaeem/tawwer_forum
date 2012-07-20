<?php if (!defined('APPLICATION')) exit;

$RankExists = Gdn::Structure()->TableExists('Rank');

Gdn::Structure()
         ->Table('Rank')
         ->PrimaryKey('RankID')
         ->Column('Name', 'varchar(100)')
         ->Column('Level', 'smallint')
         ->Column('Label', 'varchar(255)')
         ->Column('Body', 'text')
         ->Column('Attributes', 'text', TRUE)
         ->Set();
      
Gdn::Structure()
   ->Table('User')
   ->Column('RankID', 'int', TRUE)
   ->Set();

// Add the activity type for ranks.
$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Rank');

// Add some default ranks.
require_once dirname(__FILE__).'/class.rankmodel.php';
$RankModel = new RankModel();

if (!$RankExists) {
   $RankModel->Save(
      array('RankID' => 1,
         'Name' => 'Level 1',
         'Label' => '✭',
         'Level' => 1));

   $RankModel->Save(
      array('RankID' => 2,
         'Name' => 'Level 2',
         'Label' => '✭✭',
         'Level' => 2,
         'Criteria' => array('Points' => '50')));

   $RankModel->Save(
      array('RankID' => 3,
         'Name' => 'Level 3',
         'Label' => '✭✭✭',
         'Level' => 3,
         'Criteria' => array('Points' => '100')));

   $RankModel->Save(
      array('RankID' => 4,
         'Name' => 'Level 4',
         'Label' => '✭✭✭✭',
         'Level' => 4,
         'Criteria' => array('Points' => '500')));

   $RankModel->Save(
      array('RankID' => 5,
         'Name' => 'Level 5',
         'Label' => '✭✭✭✭✭',
         'Level' => 5,
         'Criteria' => array('Points' => '1000')));

   $RankModel->Save(
      array('RankID' => 100,
         'Name' => 'Moderator',
         'Label' => 'mod',
         'CssClass' => 'Rank-Mod',
         'Level' => 100,
         'Criteria' => array('Permission' => 'Garden.Moderation.Manage')));

   $RankModel->Save(
      array('RankID' => 110,
         'Name' => 'Administrator',
         'Label' => 'admin',
         'CssClass' => 'Rank-Admin',
         'Level' => 110,
         'Criteria' => array('Permission' => 'Garden.Settings.Manage')));
}