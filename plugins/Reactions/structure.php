<?php if (!defined('APPLICATION')) exit;

include_once dirname(__FILE__).'/class.reactionmodel.php';
      
$St = Gdn::Structure();
$Sql = Gdn::SQL();

$St->Table('ReactionType');
$ReactionTypeExists = $St->TableExists();

$St
   ->Column('UrlCode', 'varchar(20)', FALSE, 'primary')
   ->Column('Name', 'varchar(20)')
   ->Column('Description', 'text', TRUE)
   ->Column('Class', 'varchar(10)', TRUE)
   ->Column('TagID', 'int')
   ->Column('Attributes', 'text', TRUE)
   ->Column('Sort', 'smallint', TRUE)
   ->Column('Active', 'tinyint(1)', 1)
   ->Set();

$St->Table('UserTag')
   ->Column('RecordType', array('Discussion', 'Discussion-Total', 'Comment', 'Comment-Total', 'User', 'User-Total', 'Activity', 'Activity-Total', 'ActivityComment', 'ActivityComment-Total'), FALSE, 'primary')
   ->Column('RecordID', 'int', FALSE, 'primary')
   ->Column('TagID', 'int', FALSE, 'primary')
   ->Column('UserID', 'int', FALSE, array('primary', 'key'))
   ->Column('DateInserted', 'datetime')
   ->Column('Total', 'int', 0)
   ->Set();

$Rm = new ReactionModel();

// Insert some default tags.
$Rm->DefineReactionType(array('UrlCode' => 'Spam', 'Name' => 'Spam', 'Sort' => 100, 'Class' => 'Flag', 'Log' => 'Spam', 'LogThreshold' => 5, 'RemoveThreshold' => 5, 'ModeratorInc' => 5, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "Allow your community to report any spam that get's posted so that it can be removed as quickly as possible."));
$Rm->DefineReactionType(array('UrlCode' => 'Abuse', 'Name' => 'Abuse', 'Sort' => 101, 'Class' => 'Flag', 'Log' => 'Moderate', 'LogThreshold' => 5, 'RemoveThreshold' => 10, 'ModeratorInc' => 5, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "Report posts that are abusive or violate your terms of service so that they can be alerted to a moderator's attention."));
$Rm->DefineReactionType(array('UrlCode' => 'Troll', 'Name' => 'Troll', 'Sort' => 102, 'Class' => 'Flag', 'ModeratorInc' => 5, 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "Trollish posts are those that are trying to elicit a heated argument from other people. Posts that are flagged as trolls will be buried."));

$Rm->DefineReactionType(array('UrlCode' => 'Promote', 'Name' => 'Promote', 'Sort' => 0, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'IncrementValue' => 5, 'Points' => 3, 'Permission' => 'Garden.Moderation.Manage',
   'Description' => "Moderators can can promote the absolute best posts in the community. This way they can be remembered or read by new visitors that weren't here when they were originally posted."));

$Rm->DefineReactionType(array('UrlCode' => 'OffTopic', 'Name' => 'Off Topic', 'Sort' => 1, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "Off topic posts are not releveant to the topic being discussed. If a post gets enough off-topic votes then it will be buried so it won't derail the discussion."));

$Rm->DefineReactionType(array('UrlCode' => 'Disagree', 'Name' => 'Disagree', 'Sort' => 2, 'Class' => 'Bad', 
   'Description' => "Users that disagree with a post can give their opinion with this reaction. Since a disagreement is highly subjective, this reaction desn't promote or bury the post or give any points."));
$Rm->DefineReactionType(array('UrlCode' => 'Agree', 'Name' => 'Agree', 'Sort' => 3, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "Users that agree with a post can give their option with this reaction."));

$Rm->DefineReactionType(array('UrlCode' => 'Dislike', 'Name' => 'Dislike', 'Sort' => 4, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "A dislike is a general disapproval of a post. Enough dislikes will bury a post."));
$Rm->DefineReactionType(array('UrlCode' => 'Like', 'Name' => 'Like', 'Sort' => 5, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "A like is a general approval of a post. Enough likes will promote a post."));

$Rm->DefineReactionType(array('UrlCode' => 'Down', 'Name' => 'Vote Down', 'Sort' => 6, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "A down vote is a general disapproval of a post. Enough dislikes will bury a post."));
$Rm->DefineReactionType(array('UrlCode' => 'Up', 'Name' => 'Vote Up', 'Sort' => 7, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "An up vote is a general disapproval of a post. Enough dislikes will bury a post."));

$Rm->DefineReactionType(array('UrlCode' => 'WTF', 'Name' => 'WTF', 'Sort' => 8, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => 'WTF stands for "What the F___?" You usually react this way when a post makes absolutely no sense.'));
$Rm->DefineReactionType(array('UrlCode' => 'Awesome', 'Name' => 'Awesome', 'Sort' => 9, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => 'Awesome posts amaze you. You want to repeat them to your friends and remember them later.'));
$Rm->DefineReactionType(array('UrlCode' => 'LOL', 'Name' => 'LOL', 'Sort' => 10, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => 'For posts that make you "laugh out loud." Funny content is almost always good and is rewarded with points and promotion.'));



if (class_exists('BadgeModel')) {
   // Define some badges for the reactions.
   $BadgeModel = new BadgeModel();

   $Reactions = array('Agree' => 'Agrees', 'Like' => 'Likes', 'Up' => 'Up Votes', 'Awesome' => 'Awesomes', 'LOL' => 'LOLs'); 
   $Thresholds = array(1 => 5, 2 => 25, 3 => 100, 4 => 250, 5 => 500);

   foreach ($Reactions as $Class => $NameSuffix) {
      $ClassSlug = strtolower($Class);
      foreach ($Thresholds as $Level => $Threshold) {
         $Points = round($Threshold / 10);
         if ($Points < 10)
            $Points = 10;

         //foreach ($Likes as $Count => $Body) {
         $BadgeModel->Define(array(
             'Name' => "$Threshold $NameSuffix",
             'Slug' => "$ClassSlug-$Threshold",
             'Type' => 'Reaction',
             'Body' => '',
             'Photo' => "http://badges.vni.la/100/$ClassSlug-$Level.png",
             'Points' => $Points,
             'Threshold' => $Threshold,
             'Class' => $Class,
             'Level' => $Level
         ));
      }
   }
}