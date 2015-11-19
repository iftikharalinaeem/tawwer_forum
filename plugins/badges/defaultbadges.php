<?php if (!defined('APPLICATION')) exit();

require_once dirname(__FILE__).'/models/class.badgesappmodel.php';
require_once dirname(__FILE__).'/models/class.badgemodel.php';

function DefaultPoints($Threshold) {
    if ($Threshold < 10) {
        return 2;
    }
    if ($Threshold <= 250) {
        return 5;
    }
    if ($Threshold < 1000) {
        return 10;
    }
    return 20;
}

// Insert some default badges
$BadgeModel = new BadgeModel();

/**
 * Badge Types: Manual, Custom, UserCount, DiscussionContent.
 */

// Getting Started
$BadgeModel->Define(array(
     'Name' => 'Photogenic',
     'Slug' => 'photogenic',
     'Type' => 'Custom',
     'Body' => 'Little things like uploading a profile picture make the community a better place. Thanks!',
     'Photo' => 'http://badges.vni.la/100/user.png',
     'Points' => 10,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => 'Name Dropper',
     'Slug' => 'name-dropper',
     'Type' => 'Custom',
     'Body' => 'Mentioning someone in a discussion (like this: @Name) is a great way to encourage dialog and let them know who you&rsquo;re talking to.',
     'Photo' => 'http://badges.vni.la/100/address-book.png',
     'Points' => 5,
     'CanDelete' => 0
));

// Helper
//$BadgeModel->Define(array(
//     'Name' => 'Welcoming Committee',
//     'Slug' => 'welcome',
//     'Type' => 'Custom',
//     'Body' => 'Commenting on a new member&rsquo;s first discussion is a great way to make them feel at home.',
//     'Photo' => 'http://badges.vni.la/100/group.png',
//     'Points' => 10
//));

// Holiday / Timing
//$BadgeModel->Define(array(
//     'Name' => 'Fresh Start',
//     'Slug' => 'fresh-start',
//     'Type' => 'Custom',
//     'Body' => 'Visiting on the first day of the year is the best way to start it off on the right foot.',
//     'Photo' => 'http://badges.vni.la/100/flower.png',
//     'Points' => 5
//));
//$BadgeModel->Define(array(
//     'Name' => 'Morning Treat',
//     'Slug' => 'morning',
//     'Type' => 'Custom',
//     'Body' => 'Visiting first thing in the morning is a great way to start the day. Are you up super early or super late?',
//     'Photo' => 'http://badges.vni.la/100/doughnut.png',
//     'Points' => 5
//));

// Timeouts
/*$BadgeModel->Define(array(
     'Name' => 'Comment Marathon',
     'Slug' => 'marathon',
     'Type' => 'Timeout',
     'Body' => 'Commenting that many times in one day is above and beyond the call of duty. They better be good ones!',
     'Photo' => 'http://badges.vni.la/100/run.png',
     'Points' => 5,
     'Attributes' => array('Timeout' => 86400), // 24 hours
     'Threshold' => 42,
)); */
$BadgeModel->Define(array(
     'Name' => 'Combo Breaker',
     'Slug' => 'combo',
     'Type' => 'Timeout',
     'Body' => 'Earned badges for 5 different things in one day (now you can say it was 6!).', // 1 per class
     'Photo' => 'http://badges.vni.la/100/meal-deal.png',
     'Points' => 5,
     'Attributes' => array('Timeout' => 86400), // 24 hours
     'Threshold' => 5,
     'CanDelete' => 0
));

// Speed
/*$BadgeModel->Define(array(
     'Name' => 'Lightning Reflexes',
     'Slug' => 'lightning',
     'Type' => 'Custom',
     'Body' => 'Commenting on a new discussion within 60 seconds takes superhuman skills.',
     'Photo' => 'http://badges.vni.la/100/power.png',
     'Points' => 5
));*/

// Comment Counts
$BadgeModel->Define(array(
     'Name' => 'First Comment',
     'Slug' => 'comment',
     'Type' => 'UserCount',
     'Body' => 'Commenting is the best way to get involved. Jump in the fray!',
     'Photo' => 'http://badges.vni.la/100/comment.png',
     'Points' => 2,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 1,
     'Class' => 'Commenter',
     'Level' => 1,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '10 Comments',
     'Slug' => 'comment-10',
     'Type' => 'UserCount',
     'Body' => 'No longer a one-hit wonder! It looks like you&rsquo;re going places.',
     'Photo' => 'http://badges.vni.la/100/comment-2.png',
     'Points' => 5,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 10,
     'Class' => 'Commenter',
     'Level' => 2,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '100 Comments',
     'Slug' => 'comment-100',
     'Type' => 'UserCount',
     'Body' => 'Getting this far requires gumption, something you have in spades.',
     'Photo' => 'http://badges.vni.la/100/comment-3.png',
     'Points' => 10,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 100,
     'Class' => 'Commenter',
     'Level' => 3,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '500 Comments',
     'Slug' => 'comment-500',
     'Type' => 'UserCount',
     'Body' => 'Settled in, saw the sights, learned the territory, and most importantly: gave back.',
     'Photo' => 'http://badges.vni.la/100/comment-4.png',
     'Points' => 15,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 500,
     'Class' => 'Commenter',
     'Level' => 4,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '1000 Comments',
     'Slug' => 'comment-1000',
     'Type' => 'UserCount',
     'Body' => 'You&rsquo;re practically family.',
     'Photo' => 'http://badges.vni.la/100/comment-5.png',
     'Points' => 20,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 1000,
     'Class' => 'Commenter',
     'Level' => 5,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '2500 Comments',
     'Slug' => 'comment-2500',
     'Type' => 'UserCount',
     'Body' => 'Another day, another comment, another badge.',
     'Photo' => 'http://badges.vni.la/100/comment-6.png',
     'Points' => 20,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 2500,
     'Class' => 'Commenter',
     'Level' => 6,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '5000 Comments',
     'Slug' => 'comment-5000',
     'Type' => 'UserCount',
     'Body' => 'Eat, sleep, comment. We like your style.',
     'Photo' => 'http://badges.vni.la/100/comment-7.png',
     'Points' => 20,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 5000,
     'Class' => 'Commenter',
     'Level' => 7,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '10000 Comments',
     'Slug' => 'comment-10000',
     'Type' => 'UserCount',
     'Body' => 'You are a comment-making machine.',
     'Photo' => 'http://badges.vni.la/100/comment-8.png',
     'Points' => 20,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 10000,
     'Class' => 'Commenter',
     'Level' => 8,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '25000 Comments',
     'Slug' => 'comment-25000',
     'Type' => 'UserCount',
     'Body' => 'Who&rsquo;s house? Your house.',
     'Photo' => 'http://badges.vni.la/100/comment-9.png',
     'Points' => 20,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 25000,
     'Class' => 'Commenter',
     'Level' => 9,
     'CanDelete' => 0
));
$BadgeModel->Define(array(
     'Name' => '50000 Comments',
     'Slug' => 'comment-50000',
     'Type' => 'UserCount',
     'Body' => 'Some people are beginning to wonder if you&rsquo;re the owner.',
     'Photo' => 'http://badges.vni.la/100/comment-10.png',
     'Points' => 20,
     'Attributes' => array('Column' => 'CountComments'),
     'Threshold' => 50000,
     'Class' => 'Commenter',
     'Level' => 10,
     'CanDelete' => 0
));

// Likes
//$Likes = array(
//     1 => 'Someone liked something you posted! We like that!',
//     10 => 'You&rsquo;re posting some good content. Great!',
//     50 => 'When you&rsquo;re liked this much, you&rsquo;ll be an MVP in no time!',
//     100 => 'Looks like you&rsquo;re popular around these parts.',
//     250 => 'It ain&rsquo;t no fluke, you post great stuff and we&rsquo;re lucky to have you here.');
//
//$Level = 1;
//foreach ($Likes as $Count => $Body) {
//    $BadgeModel->Define(array(
//         'Name' => $Count == 1 ? 'First Like' : ("$Count Likes"),
//         'Slug' => 'like-'.$Count,
//         'Type' => 'UserCount',
//         'Body' => $Body,
//         'Photo' => 'http://badges.vni.la/100/like-'.$Level.'.png',
//         'Points' => DefaultPoints($Count),
//         'Attributes' => array('Column' => 'Likes'),
//         'Threshold' => $Count,
//         'Class' => 'Liked',
//         'Level' => $Level
//    ));
//
//    $Level++;
//}

// Attendance
//$BadgeModel->Define(array(
//     'Name' => 'Welcome Back',
//     'Slug' => 'day-3',
//     'Type' => 'Attendance',
//     'Body' => 'Third day in a row you stopped by! You&rsquo;ll be a regular in no time.',
//     'Photo' => 'http://badges.vni.la/100/attend.png',
//     'Points' => 2,
//     'Attributes' => array(),
//     'Threshold' => 5,
//     'Class' => 'Attendance',
//     'Level' => 1
//));
//$BadgeModel->Define(array(
//     'Name' => 'Perfect Week',
//     'Slug' => 'day-7',
//     'Type' => 'Attendance',
//     'Body' => 'Visited 7 days in a row. You show up here more than the office.',
//     'Photo' => 'http://badges.vni.la/100/attend-2.png',
//     'Points' => 5,
//     'Attributes' => array(),
//     'Threshold' => 7,
//     'Class' => 'Attendance',
//     'Level' => 2
//));
//$BadgeModel->Define(array(
//     'Name' => 'A Month To Remember',
//     'Slug' => 'day-30',
//     'Type' => 'Attendance',
//     'Body' => 'Mark your calendar: you visited every day for a month.',
//     'Photo' => 'http://badges.vni.la/100/attend-3.png',
//     'Points' => 10,
//     'Attributes' => array(),
//     'Threshold' => 30,
//     'Class' => 'Attendance',
//     'Level' => 3
//));
//$BadgeModel->Define(array(
//     'Name' => 'Seasoned Veteran',
//     'Slug' => 'day-90',
//     'Type' => 'Attendance',
//     'Body' => 'Visited every day for an entire season. If the site were a person, it would file a restraining order.',
//     'Photo' => 'http://badges.vni.la/100/attend-4.png',
//     'Points' => 15,
//     'Attributes' => array(),
//     'Threshold' => 90,
//     'Class' => 'Attendance',
//     'Level' => 4
//));
//$BadgeModel->Define(array(
//     'Name' => 'It Was A Very Good Year',
//     'Slug' => 'day-365',
//     'Type' => 'Attendance',
//     'Body' => 'When you&rsquo;re dedicated enough to visit every day for a year, the leap day is on us.',
//     'Photo' => 'http://badges.vni.la/100/attend-5.png',
//     'Points' => 20,
//     'Attributes' => array(),
//     'Threshold' => 365,
//     'Class' => 'Attendance',
//     'Level' => 5
//));

// Anniversary
$Order = array(1 => 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth');
for ($i = 1; $i < 11; $i++) {
    $BadgeModel->Define(array(
        'Name' => $Order[$i].' Anniversary',
        'Slug' => 'anniversary'.(($i > 1) ? '-'.$i : ''),
        'Type' => 'Custom',
        'Body' => 'Thanks for sticking with us for '.Plural($i, 'a full year.', '%s years.'),
        'Photo' => "http://badges.vni.la/100/anniversary-$i.png",
        'Points' => 5,
        'Attributes' => array(),
        'Threshold' => $i,
        'Class' => 'Anniversary',
        'Level' => $i,
        'CanDelete' => 0
    ));
}
$BadgeModel->Define(array(
     'Name' => 'Ancient Membership',
     'Slug' => 'anniversary-old',
     'Type' => 'Custom',
     'Body' => 'Nobody remembers a time when this person wasn&rsquo;t a member here.',
     'Photo' => 'http://badges.vni.la/100/anniversary-wow.png',
     'Points' => 5,
     'Attributes' => array(),
     'Threshold' => 11,
     'Class' => 'Anniversary',
     'Level' => 11,
     'CanDelete' => 0
));

// Social badges.

$BadgeModel->Define(array(
     'Name' => 'Facebook Connector',
     'Slug' => 'facebook-connect',
     'Type' => 'Connect',
     'Body' => "Let's get social!",
     'Photo' => 'http://badges.vni.la/100/facebook_badge.png',
     'Points' => 10,
     'Attributes' => array('Provider' => 'Facebook'),
     'Level' => 1,
     'CanDelete' => 0
));

$BadgeModel->Define(array(
     'Name' => 'Twitter Connector',
     'Slug' => 'twitter-connect',
     'Type' => 'Connect',
     'Body' => "Let's get social!",
     'Photo' => 'http://badges.vni.la/100/twitter_badge.png',
     'Points' => 10,
     'Attributes' => array('Provider' => 'Twitter'),
     'Level' => 1,
     'CanDelete' => 0
));

// Silly
//$BadgeModel->Define(array(
//     'Name' => 'Say Cheese!',
//     'Slug' => 'cheese',
//     'Type' => 'DiscussionContent',
//     'Body' => 'Cheeeeeeeeese.',
//     'Photo' => 'http://badges.vni.la/100/cheese.png',
//     'Points' => 1,
//     'Attributes' => array('Pattern' => '/cheese/i'),
//     'Threshold' => 1
//));

// Ambassador 
// @see Garden issue #1265
/*$BadgeModel->Define(array(
     'Name' => 'Junior Ambassador',
     'Slug' => 'ambassador',
     'Type' => 'UserCount',
     'Body' => 'Inviting a friend to join the community is the best way to fill it up with awesome people like you.',
     'Photo' => 'http://badges.vni.la/100/users.png',
     'Points' => 10,
     'Attributes' => array(),
     'Threshold' => 1,
     'Class' => 'Ambassador',
     'Level' => 1
));
$BadgeModel->Define(array(
     'Name' => 'Ambassador, First Class',
     'Slug' => 'ambassador-3',
     'Type' => 'UserCount',
     'Body' => 'Recruiting new members is the surest path to a strong community.',
     'Photo' => 'http://badges.vni.la/100/users-2.png',
     'Points' => 20,
     'Attributes' => array(),
     'Threshold' => 3,
     'Class' => 'Ambassador',
     'Level' => 2
));
$BadgeModel->Define(array(
     'Name' => 'Ambassador Extraordinaire',
     'Slug' => 'ambassador-10',
     'Type' => 'UserCount',
     'Body' => 'People like you are the true community builders.',
     'Photo' => 'http://badges.vni.la/100/users-3.png',
     'Points' => 30,
     'Attributes' => array(),
     'Threshold' => 10,
     'Class' => 'Ambassador',
     'Level' => 3
));*/
