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
     * @var string
     */
    protected $ideaUpReactionSlug = IdeationPlugin::REACTION_UP;
    /**
     * @var string
     */
    protected $ideaDownReactionSlug = IdeationPlugin::REACTION_DOWN;
    /**
     * @var bool
     */
    protected $isOpen;
    /**
     * @var bool
     */
    protected $showClosedStageTag = false;
    /**
     * @var bool
     */
    protected $showVotes = false;
    /**
     * @var object
     */
    protected $discussion;
    /**
     * @var array
     */
    protected $counter;
    /**
     * @var bool
     */
    protected $useDownVotes = true;
    /**
     * @var string
     */
    protected $userVote;
    /**
     * @var IdeaCounterModule
     */
    protected static $instance;

    function __construct() {}

    /**
     * Return the singleton instance of this class.
     *
     * @return IdeaCounterModule
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new IdeaCounterModule();
        }
        return self::$instance;
    }

    public function setDiscussion($discussion) {
        $this->discussion = $discussion;
        return $this;
    }

    /**
     * @param boolean $showClosedStageTag
     */
    public function setShowClosedStageTag($showClosedStageTag) {
        $this->showClosedStageTag = $showClosedStageTag;
    }

    /**
     * @param boolean $showVotes
     */
    public function setShowVotes($showVotes) {
        $this->showVotes = $showVotes;
    }


    /**
     * @return string
     */
    public function getUserVote() {
        return $this->userVote;
    }

    /**
     * @param string $userVote
     */
    public function setUserVote($userVote) {
        $this->userVote = $userVote;
    }

    /**
     * @param boolean $useDownVotes
     */
    public function setUseDownVotes($useDownVotes) {
        $this->useDownVotes = $useDownVotes;
    }

    /**
     * Compiles the data for the counter module for the discussion property.
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
