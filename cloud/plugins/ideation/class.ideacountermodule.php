<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class IdeaCounterModule
 */
class IdeaCounterModule extends Gdn_Module {

    /**
     * @var string The upvote reaction name.
     */
    protected $ideaUpReactionSlug = IdeationPlugin::REACTION_UP;
    /**
     * @var string The downvote reation name.
     */
    protected $ideaDownReactionSlug = IdeationPlugin::REACTION_DOWN;
    /**
     * @var bool Whether the idea is open.
     */
    protected $isOpen;
    /**
     * @var bool Whether to render a div showing the number of votes.
     */
    protected $showVotes = false;
    /**
     * @var object|array The discussion to render the counter module for.
     */
    protected $discussion;
    /**
     * @var array The compiled data to output in the rendering function.
     */
    protected $counter;
    /**
     * @var bool Whether to render the downvoting options.
     */
    protected $useDownVotes = true;
    /**
     * @var string If the user has voted, should be equal to the $ideaUpReactionSlug or $ideaDownReactionSlug.
     */
    protected $userVote;
    /**
     * @var IdeaCounterModule An instance of this object.
     */
    protected static $instance;

    /**
     * IdeaCounterModule constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Return the singleton instance of this class. Should be used instead of instantiating a new IdeaCounterModule
     * for each discussion.
     *
     * @return IdeaCounterModule
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new IdeaCounterModule();
        }
        return self::$instance;
    }

    /**
     * Set discussion
     *
     * @param object|array $discussion The discussion to render the counter module for.
     * @return IdeaCounterModule $this
     */
    public function setDiscussion($discussion) {
        $this->discussion = $discussion;
        return $this;
    }

    /**
     * @param boolean $showVotes Whether to render a div showing the number of votes.
     */
    public function setShowVotes($showVotes) {
        $this->showVotes = $showVotes;
    }

    /**
     * @return string The user's vote, equal to the $ideaUpReactionSlug or $ideaDownReactionSlug.
     */
    public function getUserVote() {
        return $this->userVote;
    }

    /**
     * @param string $userVote If the user has voted, should be equal to the $ideaUpReactionSlug or $ideaDownReactionSlug.
     */
    public function setUserVote($userVote) {
        $this->userVote = $userVote;
    }

    /**
     * @param boolean $useDownVotes Whether to render the downvoting options.
     */
    public function setUseDownVotes($useDownVotes) {
        $this->useDownVotes = $useDownVotes;
    }

    /**
     * Compiles the data for the counter module.
     *
     * @return bool Whether to render the counter or not.
     */
    public function prepare() {
        if ($this->discussion) {
            if (!$score = val('Score', $this->discussion)) {
                $score = '0';
            }
            $discussionID = val('DiscussionID', $this->discussion);
            $status = StatusModel::instance()->getStatusByDiscussion($discussionID);
            $this->isOpen = (val('State', $status) == 'Open');
            $counter = [];
            $counter['upUrl'] = url('/react/discussion/'.$this->ideaUpReactionSlug.'?id='.$discussionID.'&selfreact=true');
            $counter['score'] = $score;
            $counter['numberVotes'] = $score;
            $counter['status'] = val('Name', $status);
            $counter['state'] = val('State', $status);
            $counter['upCssClass'] = '';

            if ($this->userVote) {
//                $counter['cssClass'] = 'voted voted-'.strtolower($this->userVote);
                $counter['upCssClass'] = ($this->userVote == $this->ideaUpReactionSlug) ? 'uservote' : '';
            }


            if ($this->useDownVotes) {
                $counter['downUrl'] = url('/react/discussion/'.$this->ideaDownReactionSlug.'?id='.$discussionID.'&selfreact=true');
                $counter['downCssClass'] = ($this->userVote == $this->ideaDownReactionSlug) ? 'uservote' : '';
                $counter['numberVotes'] = IdeationPlugin::getTotalVotes($this->discussion);
            }

            $this->counter = $counter;
            return true;
        }
        return false;
    }

    /**
     * Renders the idea counter module.
     */
    public function toString() {
        if ($this->prepare()) {
            ob_start();
            renderCounterBox(
                $this->counter,
                $this->useDownVotes,
                $this->showVotes,
                $this->ideaUpReactionSlug,
                $this->ideaDownReactionSlug,
                $this->discussion->Name
            );
            $box = ob_get_contents();
            ob_end_clean();
            return $box;
        } else {
            return '';
        }
    }
}

/**
 * Outputs a counter that includes voting buttons.
 */
if (!function_exists('renderCounterBox')) {
    /**
     * Outputs a counter that includes voting buttons.
     *
     * @param int $counter
     * @param int $useDownVotes
     * @param bool $showVotes
     * @param string $ideaUpReactionSlug
     * @param string $ideaDownReactionSlug
     * @param string $discussionName
     */
    function renderCounterBox($counter, $useDownVotes, $showVotes, $ideaUpReactionSlug, $ideaDownReactionSlug, $discussionName) {
        ?>
        <div class="idea-counter-module <?php echo val('state', $counter).' '.val('cssClass', $counter); ?>">
            <div class="idea-counter-box">
                <?php echo getScoreHtml(val('score', $counter)); ?>
                <?php if (val('state', $counter) == 'Open') { ?>
                    <div class="vote idea-menu">
                        <span class="idea-buttons">
                            <?php
                            /**
                             * Output reactions
                             */

                            echo getReactionButtonHtml(
                                'ReactButton-'.$ideaUpReactionSlug.' '.val('upCssClass', $counter),
                                val('upUrl', $counter),
                                $ideaUpReactionSlug,
                                strtolower($ideaUpReactionSlug),
                                'data-reaction="'.strtolower($ideaUpReactionSlug).'"',
                                $discussionName,
                                true
                            );
                            if ($useDownVotes) {
                                echo getReactionButtonHtml(
                                    'ReactButton-'.$ideaDownReactionSlug.' '.val('downCssClass', $counter),
                                    val('downUrl', $counter),
                                    $ideaDownReactionSlug,
                                    strtolower($ideaDownReactionSlug),
                                    'data-reaction="'.strtolower($ideaDownReactionSlug).'"',
                                    $discussionName,
                                    true
                                );
                            }
                            ?>
                        </span>
                    </div>
                <?php } ?>
            </div>
            <?php if ($showVotes) {
                echo getVotesHtml(val('numberVotes', $counter));
            } ?>
        </div>
    <?php }
}