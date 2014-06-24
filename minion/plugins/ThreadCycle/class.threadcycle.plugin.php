<?php

/**
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 */

$PluginInfo['ThreadCycle'] = array(
    'Name' => 'Minion: ThreadCycle',
    'Description' => "Provide command to automatically cycle a thread after N pages.",
    'Version' => '1.3',
    'RequiredApplications' => array(
        'Vanilla' => '2.1a'
    ),
    'RequiredPlugins' => array(
        'Minion' => '1.16',
        'Online' => '1.6.3'
    ),
    'MobileFriendly' => true,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://vanillaforums.com'
);

/**
 * ThreadCycle Plugin
 *
 * This plugin uses Minion to automatically close threads after N pages.
 *
 * Changes:
 *  1.0     Release
 *  1.1     Improve new thread creator choices
 *  1.2     Further improve new thread creator choices
 *  1.3     Add speeds!
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package internal
 */
class ThreadCyclePlugin extends Gdn_Plugin {

    /**
     * Cycle this thread
     *
     * @param array $discussion
     */
    public function cycleThread($discussion) {

        // Determine speed
        $startTime = strtotime(val('DateInserted', $discussion));
        $endTime = time();
        $elapsed = $endTime - $startTime;
        $rate = (val('CountComments', $discussion) / $elapsed) * 60;
        $speedBoost = C('Minion.ThreadCycle.Boost', 2.5);
        $rate = $rate * $speedBoost;

        // Define known speeds and their characteristics
        $engines = array(
            'thrusters' => array(
                'min' => 0,
                'max' => 0.1,
                'format' => '{verb} at {speed} {scale}',
                'divisions' => 4,
                'divtype' => 'fractions',
                'replace' => ['1/1' => 'full'],
                'verbs' => array('moseying by', 'puttering along')
            ),
            'impulse' => array(
                'min' => 0.1,
                'max' => 0.4,
                'format' => '{verb} at {speed} {scale}',
                'divisions' => 4,
                'divtype' => 'fractions',
                'replace' => ['1/1' => 'full'],
                'verbs' => array('travelling', 'moving', 'scooting past')
            ),
            'warp' => array(
                'min' => 0.4,
                'max' => 10,
                'format' => '{verb} at {scale} {speed}',
                'divisions' => 10,
                'divtype' => 'decimal',
                'round' => 1,
                'verbs' => array('zooming by', 'blasting along', 'careening by', 'speeding through')
            ),
            'transwarp' => array(
                'min' => 10,
                'max' => null,
                'format' => '{verb} at transwarp',
                'divisions' => 1,
                'divtype' => 'const',
                'verbs' => array('hurtling by', 'streaking past')
            )
        );

        // Determine which engine was in use, and the speed
        $speed = null;
        $realSpeed = 0;
        $speedcontext = array(
            'cpm' => $rate,
            'rate' => $rate
        );
        foreach ($engines as $engine => $engineInfo) {
            $engineMin = $engineInfo['min'];
            $engineMax = $engineInfo['max'];

            if ($rate >= $engineMin && $rate < $engineMax) {
                $speedcontext['format'] = val('format', $engineInfo, '{scale}');
                $speedcontext['scale'] = $engine;

                $rangedRate = $rate - $engineMin;

                $divisions = val('divisions', $engineInfo, null);
                if ($divisions && $engineMax) {
                    $divType = val('divtype', $engineInfo, 'decimal');
                    switch ($divType) {
                        case 'fractions':
                            $range = $engineMax - $engineMin;
                            $bucketsize = $range / $divisions;
                            $fraction = round($rangedRate / $bucketsize);
                            $gcd = self::gcd($fraction, $divisions);
                            $num = $fraction / $gcd;
                            $den = $divisions / $gcd;
                            $speed = "{$num}/{$den}";
                            $realSpeed = $num/$den;
                            break;

                        case 'decimal':
                            $range = $engineMax - $engineMin;
                            $bucketsize = $range / $divisions;
                            $round = val('round', $engineInfo, 1);
                            $realSpeed = $speed = round($rangedRate / $bucketsize, $round);
                            break;

                        case 'const':
                            $realSpeed = $speed = 1;
                            break;

                        default:
                            break;
                    }
                }

                if (key_exists('replace', $engineInfo) && key_exists($speed, $engineInfo['replace'])) {
                    $speed = val($speed, $engineInfo['replace']);
                }
                if (!$realSpeed) {
                    $speedcontext['format'] = 'drifing in space';
                }
                if (key_exists('verbs', $engineInfo)) {
                    $verb = array_rand($engineInfo['verbs']);
                    $speedcontext['verb'] = $verb;
                }

                $speedcontext['speed'] = $speed;
                break;
            }
        }

        $discussionID = val('DiscussionID', $discussion);

        // Close the thread
        $discussionModel = new DiscussionModel();
        $discussionModel->setField($discussionID, 'Closed', true);

        // Find the last page of commenters
        $commentsPerPage = c('Vanilla.Comments.PerPage', 40);
        $commenters = Gdn::SQL()->Select('InsertUserID', 'DISTINCT', 'UserID')
                        ->From('Comment')
                        ->Where('DiscussionID', $discussionID)
                        ->OrderBy('DateInserted', 'desc')
                        ->Limit($commentsPerPage)
                        ->Get()->ResultArray();

        Gdn::UserModel()->JoinUsers($commenters, array('UserID'), array(
            'Join' => array('UserID', 'Name', 'Email', 'Photo', 'Punished', 'Banned', 'Points')
        ));

        // Weed out jailed and offline people
        $eligible = array();
        foreach ($commenters as $commenter) {
            // No jailed users
            if ($commenter['Punished']) {
                continue;
            }

            // No banned users
            if ($commenter['Banned']) {
                continue;
            }

            // No offline users
            $userOnline = OnlinePlugin::Instance()->GetUser($commenter['UserID']);
            if (!$userOnline) {
                continue;
            }

            $commenter['LastOnline'] = time() - strtotime($userOnline['Timestamp']);
            $eligible[] = $commenter;
        }
        unset($commenters);

        // Sort by online, ascending
        usort($eligible, array('ThreadCyclePlugin', 'compareUsersByLastOnline'));

        // Get the top 10 by online, and choose the top 5 by points
        $eligible = array_slice($eligible, 0, 10);
        usort($eligible, array('ThreadCyclePlugin', 'compareUsersByPoints'));
        $eligible = array_slice($eligible, 0, 5);

        // Shuffle
        shuffle($eligible);

        // Get the top 2 users
        $primary = val(0, $eligible, array());
        $secondary = Getvalue(1, $eligible, array());

        // Build user alert message
        $message = T("This thread is no longer active, and will be recycled.\n");
        if ($speed) {
            $message .= sprintf(T("On average, this thread was %s\n"), formatString($speedcontext['format'], $speedcontext));
        }
        $message .= "\n";
        $acknowledge = T("Thread has been recycled.\n");

        $options = array(
            'Primary' => &$primary,
            'Secondary' => &$secondary
        );

        if (sizeof($primary)) {
            $message .= $primaryMessage = T(" {Primary.Mention} will create the new thread\n");
            $acknowledge .= str_replace('.Mention', '.Anchor', $primaryMessage);

            $primary['Mention'] = "@\"{$primary['Name']}\"";
            $primary['Anchor'] = userAnchor($primary);
        }

        if (sizeof($secondary)) {
            $message .= $secondaryMessage = T(" {Secondary.Mention} is backup\n");
            $acknowledge .= str_replace('.Mention', '.Anchor', $secondaryMessage);

            $secondary['Mention'] = "@\"{$secondary['Name']}\"";
            $secondary['Anchor'] = userAnchor($secondary);
        }

        // Post in the thread for the users to see
        $message = formatString($message, $options);
        MinionPlugin::instance()->message($primary, $discussion, $message, false);

        // Log that this happened
        $acknowledged = formatString($acknowledge, $options);
        MinionPlugin::instance()->log($acknowledged, $discussion);

        // Stop caring about posts in here
        MinionPlugin::instance()->monitor($discussion, array(
            'ThreadCycle' => null
        ));
    }

    /**
     * Calculate GCD
     *
     * @param integer $a
     * @param integer $b
     * @return integer
     */
    protected static function gcd($a,$b) {
        $a = abs($a); $b = abs($b);
        if( $a < $b) list($b,$a) = Array($a,$b);
        if( $b == 0) return $a;
        $r = $a % $b;
        while($r > 0) {
            $a = $b;
            $b = $r;
            $r = $a % $b;
        }
        return $b;
    }

    public static function compareUsersByPoints($a, $b) {
        return $b['Points'] - $a['Points'];
    }

    public static function compareUsersByLastOnline($a, $b) {
        return $a['LastOnline'] - $b['LastOnline'];
    }

    /*
     * MINION INTERFACE
     */

    /**
     * Parse a token from the current state
     *
     * @param MinionPlugin $sender
     */
    public function MinionPlugin_Token_Handler($sender) {
        $state = &$sender->EventArguments['State'];

        if (!$state['Method'] && in_array($state['CompareToken'], array('recycle'))) {
            $sender->consume($state, 'Method', 'threadcycle');
        }

        // Gather
        if (val('Method', $state) == 'threadcycle' && in_array($state['CompareToken'], array('pages', 'page'))) {

            // Do a quick lookbehind
            if (is_numeric($state['LastToken'])) {
                $state['Targets']['Page'] = $state['LastToken'];
                $sender->consume($state);
            } else {
                $sender->consume($state, 'Gather', array(
                    'Node' => 'Page',
                    'Delta' => ''
                ));
            }
        }
    }

    /**
     * Parse custom minion commands
     *
     * @param MinionPlugin $sender
     */
    public function MinionPlugin_Command_Handler($sender) {
        $actions = &$sender->EventArguments['Actions'];
        $state = &$sender->EventArguments['State'];

        switch ($state['Method']) {
            case 'threadcycle':

                $state['Targets']['Discussion'] = $state['Sources']['Discussion'];
                $actions[] = array('threadcycle', c('Minion.Access.Recycle','Garden.Moderation.Manage'), $state);
                break;
        }
    }

    /**
     * Perform custom minion actions
     *
     * @param MinionPlugin $sender
     */
    public function MinionPlugin_Action_Handler($sender) {
        $action = $sender->EventArguments['Action'];
        $state = $sender->EventArguments['State'];

        switch ($action) {

            case 'threadcycle':

                if (!array_key_exists('Discussion', $state['Targets'])) {
                    return;
                }

                $discussion = $state['Targets']['Discussion'];
                $threadCycle = $sender->monitoring($discussion, 'ThreadCycle', false);

                // Trying to call off a threadcycle
                if ($state['Toggle'] == 'off') {
                    if (!$threadCycle) {
                        return;
                    }

                    // Call off the hunt
                    $sender->monitor($discussion, array(
                        'ThreadCycle' => null
                    ));

                    $sender->acknowledge($state['Sources']['Discussion'], FormatString(T("This thread will not be automatically recycled."), array(
                        'Discussion' => $discussion
                    )));

                    // Trying start a threadcycle
                } else {

                    $cyclePage = val('Page', $state['Targets'], false);
                    if ($cyclePage) {

                        // Pick somewhere to end the discussion
                        $commentsPerPage = C('Vanilla.Comments.PerPage', 40);
                        $minComments = ($cyclePage - 1) * $commentsPerPage;
                        $commentNumber = $minComments + mt_rand(1, $commentsPerPage - 1);

                        // Monitor the thread
                        $sender->monitor($discussion, array(
                            'ThreadCycle' => array(
                                'Started' => time(),
                                'Page' => $cyclePage,
                                'Comment' => $commentNumber
                            )
                        ));

                        $acknowledge = T("Thread will be recycled after {Page}.");
                        $acknowledged = formatString($acknowledge, array(
                            'Page' => sprintf(Plural($cyclePage, '%d page', '%d pages'), $cyclePage),
                            'Discussion' => $state['Targets']['Discussion']
                        ));

                        $sender->acknowledge($state['Sources']['Discussion'], $acknowledged);
                        $sender->log($acknowledged, $state['Targets']['Discussion'], $state['Sources']['User']);
                    } else {
                        // Cycle immediately
                        $this->cycleThread($discussion);
                    }
                }

                break;
        }
    }

    /**
     * Determine if we're at the comment that should trigger recycling
     *
     * @param MinionPlugin $sender
     */
    public function MinionPlugin_Monitor_Handler($sender) {
        $discussion = $sender->EventArguments['Discussion'];
        $threadCycle = $sender->monitoring($discussion, 'ThreadCycle', false);
        if (!$threadCycle) {
            return;
        }

        $cycleCommentNumber = val('Comment', $threadCycle);
        $comments = val('CountComments', $discussion);
        if ($comments == $cycleCommentNumber) {
            $this->cycleThread($discussion);
        }
    }

    /**
     * Add to rules
     *
     * @param MinionPlugin $sender
     */
    public function MinionPlugin_Sanctions_Handler($sender) {

        // Don't care about the rule bar

        $type = val('Type', $sender->EventArguments, 'rules');
        if ($type == 'bar') {
            return;
        }

        // Show a warning if there are rules in effect

        $threadCycle = $sender->monitoring($sender->EventArguments['Discussion'], 'ThreadCycle', null);

        // Nothing happening?
        if (!$threadCycle) {
            return;
        }

        $rules = &$sender->EventArguments['Rules'];

        // Thread is queued for recycled
        $page = val('Page', $threadCycle);
        $rules[] = Wrap("<b>Thread Recycle</b>: page {$page}", 'span', array('class' => 'MinionRule'));
    }

}
