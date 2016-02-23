<?php
/**
 * Idea Counter Module
 */

/**
 * Class IdeaCounterModule
 *
 *
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

    function __construct() {}

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
            $stage = StageModel::getStageByDiscussion($discussionID);
            $this->isOpen = (val('Status', $stage) == 'Open');
            $counter = array();
            $counter['upUrl'] = url('/react/discussion/'.$this->ideaUpReactionSlug.'?id='.$discussionID.'&selfreact=true');
            $counter['score'] = $score;
            $counter['numberVotes'] = $score;
            $counter['stage'] = val('Name', $stage);
            $counter['status'] = val('Status', $stage);
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
            renderCounterBox($this->counter, $this->useDownVotes, $this->showVotes, $this->ideaUpReactionSlug, $this->ideaDownReactionSlug);
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
    function renderCounterBox($counter, $useDownVotes, $showVotes, $ideaUpReactionSlug, $ideaDownReactionSlug) { ?>
        <div class="idea-counter-module <?php echo val('status', $counter).' '.val('cssClass', $counter); ?>">
            <div class="idea-counter-box">
                <?php echo getScoreHtml(val('score', $counter)); ?>
                <?php if (val('status', $counter) == 'Open') { ?>
                    <div class="vote idea-menu">
                        <span class="idea-buttons">
                            <?php
                            echo getReactionButtonHtml('ReactButton-'.$ideaUpReactionSlug.' '.val('upCssClass', $counter), val('upUrl', $counter), 'Up', 'data-reaction="'.strtolower($ideaUpReactionSlug).'"');
                            if ($useDownVotes) {
                                echo getReactionButtonHtml('ReactButton-'.$ideaDownReactionSlug.' '.val('downCssClass', $counter), val('downUrl', $counter), 'Down', 'data-reaction="'.strtolower($ideaDownReactionSlug).'"');
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
