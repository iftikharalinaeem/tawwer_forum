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
   ->Column('Active', 'tinyint(1)', 0)
   ->Column('Custom', 'tinyint(1)', 0)
   ->Column('Hidden', 'tinyint(1)', 0)
   ->Set();

$St->Table('UserTag')
   ->Column('RecordType', array('Discussion', 'Discussion-Total', 'Comment', 'Comment-Total', 'User', 'User-Total', 'Activity', 'Activity-Total', 'ActivityComment', 'ActivityComment-Total'), FALSE, 'primary')
   ->Column('RecordID', 'int', FALSE, 'primary')
   ->Column('TagID', 'int', FALSE, array('primary', 'key'))
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
   'Description' => "Troll posts are typically trying to elicit a heated argument from other people. Trolls are community poison, making your community a scary place for new members. Troll posts will be buried."));

$Rm->DefineReactionType(array('UrlCode' => 'Promote', 'Name' => 'Promote', 'Sort' => 0, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'IncrementValue' => 5, 'Points' => 3, 'Permission' => 'Garden.Curation.Manage',
   'Description' => "Moderators have the ability to promote the best posts in the community. This way they can be featured for new visitors."));

$Rm->DefineReactionType(array('UrlCode' => 'OffTopic', 'Name' => 'Off Topic', 'Sort' => 1, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "Off topic posts are not releveant to the topic being discussed. If a post gets enough off-topic votes then it will be buried so it won't derail the discussion."));
$Rm->DefineReactionType(array('UrlCode' => 'Insightful', 'Name' => 'Insightful', 'Sort' => 2, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "Insightful comments bring new information or perspective to the discussion and increase the value of the conversation as a whole."));

$Rm->DefineReactionType(array('UrlCode' => 'Disagree', 'Name' => 'Disagree', 'Sort' => 3, 'Class' => 'Bad', 
   'Description' => "Users that disagree with a post can give their opinion with this reaction. Since a disagreement is highly subjective, this reaction desn't promote or bury the post or give any points."));
$Rm->DefineReactionType(array('UrlCode' => 'Agree', 'Name' => 'Agree', 'Sort' => 4, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "Users that agree with a post can give their option with this reaction."));

$Rm->DefineReactionType(array('UrlCode' => 'Dislike', 'Name' => 'Dislike', 'Sort' => 5, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "A dislike is a general disapproval of a post. Enough dislikes will bury a post."));
$Rm->DefineReactionType(array('UrlCode' => 'Like', 'Name' => 'Like', 'Sort' => 6, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "A like is a general approval of a post. Enough likes will promote a post."));

$Rm->DefineReactionType(array('UrlCode' => 'Down', 'Name' => 'Vote Down', 'Sort' => 7, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => "A down vote is a general disapproval of a post. Enough down votes will bury a post."));
$Rm->DefineReactionType(array('UrlCode' => 'Up', 'Name' => 'Vote Up', 'Sort' => 8, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => "An up vote is a general approval of a post. Enough up votes will promote a post."));

$Rm->DefineReactionType(array('UrlCode' => 'WTF', 'Name' => 'WTF', 'Sort' => 9, 'Class' => 'Bad', 'IncrementColumn' => 'Score', 'IncrementValue' => -1, 'Points' => -1,
   'Description' => 'WTF stands for "What the Fuh?" You usually react this way when a post makes absolutely no sense.'));
$Rm->DefineReactionType(array('UrlCode' => 'Awesome', 'Name' => 'Awesome', 'Sort' => 10, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => 'Awesome posts amaze you. You want to repeat them to your friends and remember them later.'));
$Rm->DefineReactionType(array('UrlCode' => 'LOL', 'Name' => 'LOL', 'Sort' => 11, 'Class' => 'Good', 'IncrementColumn' => 'Score', 'Points' => 1,
   'Description' => 'For posts that make you "laugh out loud." Funny content is almost always good and is rewarded with points and promotion.'));

if (!$ReactionTypeExists) {
   // Activate the default reactions.
   $Defaults = array('Spam', 'Abuse', 'Troll', 'Promote', 'OffTopic', 'Disagree', 'Agree', 'Like');
   $Sql->Update('ReactionType')
      ->Set('Active', 1)
      ->WhereIn('UrlCode', $Defaults)
      ->Put();
   Gdn::Cache()->Remove('ReactionTypes');
}

if (class_exists('BadgeModel')) {
   // Define some badges for the reactions.
   $BadgeModel = new BadgeModel();

   $Reactions = array('Insightful' => 'Insightfuls', 'Agree' => 'Agrees', 'Like' => 'Likes', 'Up' => 'Up Votes', 'Awesome' => 'Awesomes', 'LOL' => 'LOLs'); 
   $Thresholds = array(1 => 5, 2 => 25, 3 => 100, 4 => 250, 5 => 500);
   $Sentences = array(
       1 => "We like that.",
       2 => "You're posting some good content. Great!",
       3 => "When you're liked this much, you'll be an MVP in no time!",
       4 => "Looks like you're popular around these parts.",
       5 => "It ain't no fluke, you post great stuff and we're lucky to have you here.");

   foreach ($Reactions as $Class => $NameSuffix) {
      $ClassSlug = strtolower($Class);
      foreach ($Thresholds as $Level => $Threshold) {
         $Points = round($Threshold / 10);
         if ($Points < 5)
            $Points = 5;
         
         $Sentence = $Sentences[$Level];

         //foreach ($Likes as $Count => $Body) {
         $BadgeModel->Define(array(
             'Name' => "$Threshold $NameSuffix",
             'Slug' => "$ClassSlug-$Threshold",
             'Type' => 'Reaction',
             'Body' => "You received $Threshold $NameSuffix. $Sentence",
             'Photo' => "http://badges.vni.la/100/$ClassSlug-$Level.png",
             'Points' => $Points,
             'Threshold' => $Threshold,
             'Class' => $Class,
             'Level' => $Level,
             'CanDelete' => 0
         ));
      }
   }
}