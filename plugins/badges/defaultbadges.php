<?php if (!defined('APPLICATION')) exit();

if (!function_exists('DefaultPoints')) {
    /**
     *
     *
     * @param $threshold
     * @return int
     */
    function defaultPoints($threshold) {
        if ($threshold < 10) {
            return 2;
        }
        if ($threshold <= 250) {
            return 5;
        }
        if ($threshold < 1000) {
            return 10;
        }
        return 20;
    }
}

// Insert some default badges
$BadgeModel = new BadgeModel();

/**
 * Badge Types: Manual, Custom, UserCount, DiscussionContent.
 */

// Getting Started
$BadgeModel->define([
     'Name' => 'Photogenic',
     'Slug' => 'photogenic',
     'Type' => 'Custom',
     'Body' => 'Little things like uploading a profile picture make the community a better place. Thanks!',
     'Photo' => 'https://badges.v-cdn.net/svg/photogenic.svg',
     'Points' => 10,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => 'Name Dropper',
     'Slug' => 'name-dropper',
     'Type' => 'Custom',
     'Body' => 'Mentioning someone in a discussion (like this: @Name) is a great way to encourage dialog and let them know who you&rsquo;re talking to.',
     'Photo' => 'https://badges.v-cdn.net/svg/address-book.svg',
     'Points' => 5,
     'CanDelete' => 0
]);

$BadgeModel->define([
     'Name' => 'Combo Breaker',
     'Slug' => 'combo',
     'Type' => 'Timeout',
     'Body' => 'Earned badges for 5 different things in one day (now you can say it was 6!).', // 1 per class
     'Photo' => 'https://badges.v-cdn.net/svg/combo-breaker.svg',
     'Points' => 5,
     'Attributes' => ['Timeout' => 86400], // 24 hours
     'Threshold' => 5,
     'CanDelete' => 0
]);

// Comment Counts
$BadgeModel->define([
     'Name' => 'First Comment',
     'Slug' => 'comment',
     'Type' => 'UserCount',
     'Body' => 'Commenting is the best way to get involved. Jump in the fray!',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-1.svg',
     'Points' => 2,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 1,
     'Class' => 'Commenter',
     'Level' => 1,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '10 Comments',
     'Slug' => 'comment-10',
     'Type' => 'UserCount',
     'Body' => 'No longer a one-hit wonder! It looks like you&rsquo;re going places.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-2.svg',
     'Points' => 5,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 10,
     'Class' => 'Commenter',
     'Level' => 2,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '100 Comments',
     'Slug' => 'comment-100',
     'Type' => 'UserCount',
     'Body' => 'Getting this far requires gumption, something you have in spades.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-3.svg',
     'Points' => 10,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 100,
     'Class' => 'Commenter',
     'Level' => 3,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '500 Comments',
     'Slug' => 'comment-500',
     'Type' => 'UserCount',
     'Body' => 'Settled in, saw the sights, learned the territory, and most importantly: gave back.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-4.svg',
     'Points' => 15,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 500,
     'Class' => 'Commenter',
     'Level' => 4,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '1000 Comments',
     'Slug' => 'comment-1000',
     'Type' => 'UserCount',
     'Body' => 'You&rsquo;re practically family.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-5.svg',
     'Points' => 20,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 1000,
     'Class' => 'Commenter',
     'Level' => 5,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '2500 Comments',
     'Slug' => 'comment-2500',
     'Type' => 'UserCount',
     'Body' => 'Another day, another comment, another badge.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-6.svg',
     'Points' => 20,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 2500,
     'Class' => 'Commenter',
     'Level' => 6,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '5000 Comments',
     'Slug' => 'comment-5000',
     'Type' => 'UserCount',
     'Body' => 'Eat, sleep, comment. We like your style.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-7.svg',
     'Points' => 20,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 5000,
     'Class' => 'Commenter',
     'Level' => 7,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '10000 Comments',
     'Slug' => 'comment-10000',
     'Type' => 'UserCount',
     'Body' => 'You are a comment-making machine.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-8.svg',
     'Points' => 20,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 10000,
     'Class' => 'Commenter',
     'Level' => 8,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '25000 Comments',
     'Slug' => 'comment-25000',
     'Type' => 'UserCount',
     'Body' => 'Whose house? Your house.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-9.svg',
     'Points' => 20,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 25000,
     'Class' => 'Commenter',
     'Level' => 9,
     'CanDelete' => 0
]);
$BadgeModel->define([
     'Name' => '50000 Comments',
     'Slug' => 'comment-50000',
     'Type' => 'UserCount',
     'Body' => 'Some people are beginning to wonder if you&rsquo;re the owner.',
     'Photo' => 'https://badges.v-cdn.net/svg/comment-10.svg',
     'Points' => 20,
     'Attributes' => ['Column' => 'CountComments'],
     'Threshold' => 50000,
     'Class' => 'Commenter',
     'Level' => 10,
     'CanDelete' => 0
]);

// Anniversary
$Order = [1 => 'First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth'];
for ($i = 1; $i < 11; $i++) {
    $BadgeModel->define([
        'Name' => $Order[$i].' Anniversary',
        'Slug' => 'anniversary'.(($i > 1) ? '-'.$i : ''),
        'Type' => 'Custom',
        'Body' => 'Thanks for sticking with us for'.' '.plural($i, 'a full year.', '%s years.'),
        'Photo' => "https://badges.v-cdn.net/svg/anniversary-$i.svg",
        'Points' => 5,
        'Attributes' => [],
        'Threshold' => $i,
        'Class' => 'Anniversary',
        'Level' => $i,
        'CanDelete' => 0
    ]);
}
$BadgeModel->define([
     'Name' => 'Ancient Membership',
     'Slug' => 'anniversary-old',
     'Type' => 'Custom',
     'Body' => 'Nobody remembers a time when this person wasn&rsquo;t a member here.',
     'Photo' => 'https://badges.v-cdn.net/svg/anniversary-wow.svg',
     'Points' => 5,
     'Attributes' => [],
     'Threshold' => 11,
     'Class' => 'Anniversary',
     'Level' => 11,
     'CanDelete' => 0
]);

// Social badges.
$BadgeModel->define([
     'Name' => 'Facebook Connector',
     'Slug' => 'facebook-connect',
     'Type' => 'Connect',
     'Body' => "Let's get social!",
     'Photo' => 'https://badges.v-cdn.net/svg/facebook_badge.svg',
     'Points' => 10,
     'Attributes' => ['Provider' => 'Facebook'],
     'Level' => 1,
     'CanDelete' => 0
]);

$BadgeModel->define([
     'Name' => 'Twitter Connector',
     'Slug' => 'twitter-connect',
     'Type' => 'Connect',
     'Body' => "Let's get social!",
     'Photo' => 'https://badges.v-cdn.net/svg/twitter_badge.svg',
     'Points' => 10,
     'Attributes' => ['Provider' => 'Twitter'],
     'Level' => 1,
     'CanDelete' => 0
]);
