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



    // Empty constructor so we don't call parent::construct.
    // We don't need all that extra data floating around.
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
            if ($this->isOpen) {
                $this->renderOpenCounterBox();
            } else {
                $this->renderClosedCounterBox();
            }
        } else {
            echo '';
        }
    }

    /**
     * Outputs a counter that includes voting buttons.
     */
    protected function renderOpenCounterBox() { ?>
        <div class="DateTile idea-counter-module <?php echo val('status', $this->counter); ?>">
            <div class="idea-counter-box">
                <div class="score"><?php echo val('score', $this->counter) ?></div>
                <div class="vote idea-menu">
                    <span class="idea-buttons">
                        <?php
                        echo IdeationPlugin::getReactionButtonHtml('ReactButton-'.$this->ideaUpReactionSlug.' '.val('upCssClass', $this->counter), val('upUrl', $this->counter), 'Up', 'data-reaction="'.strtolower($this->ideaUpReactionSlug).'"');
                        if ($this->useDownVotes) {
                            echo IdeationPlugin::getReactionButtonHtml('ReactButton-'.$this->ideaDownReactionSlug.' '.val('downCssClass', $this->counter), val('downUrl', $this->counter), 'Down', 'data-reaction="'.strtolower($this->ideaDownReactionSlug).'"');
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php if ($this->showVotes) { ?>
            <div class="votes meta"><?php echo sprintf(t('%s votes'), val('numberVotes', $this->counter)); ?></div>
            <?php } ?>
        </div>
    <?php }

    /**
     * Outputs a counter without voting buttons.
     */
    protected function renderClosedCounterBox() { ?>
        <div class="DateTile idea-counter-module <?php echo val('status', $this->counter).' '.val('cssClass', $this->counter) ?>">
            <div class="idea-counter">
                <div class="score"><?php echo val('score', $this->counter) ?></div>
            </div>
            <?php if ($this->showClosedStageTag) { ?>
            <div class="Tag tag-stage"><?php echo val('stage', $this->counter) ?></div>
            <?php } ?>
            <?php if ($this->showVotes) { ?>
            <div class="votes meta"><?php echo sprintf(t('%s votes'), val('numberVotes', $this->counter)); ?></div>
            <?php } ?>
        </div>
    <?php }
}
