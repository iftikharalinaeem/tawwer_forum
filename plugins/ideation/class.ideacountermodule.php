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
    protected $ideaUpReactionSlug = 'IdeaUp';
    /**
     * @var string
     */
    protected $ideaDownReactionSlug = 'IdeaDown';
    /**
     * @var boolean
     */
    protected $isOpen;
    /**
     * @var object
     */
    public $discussion;
    /**
     * @var array
     */
    public $counter;
    /**
     * @var IdeaCounterModule
     */
    protected static $instance;


    function __construct() {

    }

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

    protected function calculateVotes() {
        if (val('Attributes', $this->discussion) && $reactions = val('React', $this->discussion->Attributes)) {
            $noUp = val('IdeaUp', $reactions, 0);
            $noDown = val('IdeaDown', $reactions, 0);
            return $noUp + $noDown;
        }
        return 0;
    }

    public function prepare() {
        if ($this->discussion) {
            $discussionID = val('DiscussionID', $this->discussion);
            $stage = StageModel::getStageByDiscussion($discussionID);
            $this->isOpen = (val('Status', $stage) == 'Open');
            $counter = array();
            $counter['upUrl'] = url('/react/discussion/'.$this->ideaUpReactionSlug.'?id='.$discussionID);
            $counter['downUrl'] = url('/react/discussion/'.$this->ideaDownReactionSlug.'?id='.$discussionID);
            $counter['score'] = val('Score', $this->discussion);
            $counter['numberVotes'] = $this->calculateVotes();
            $counter['stage'] = val('Name', $stage);
            $counter['status'] = val('Status', $stage);

            $this->counter = $counter;
            return true;
        }
        return false;
    }

    public function toString() {
        if ($this->prepare()) {
            if ($this->isOpen) {
                $this->renderOpenCounterBox();
            } else {
                $this->renderClosedCounterBox();
            }
        }
    }

    protected function renderOpenCounterBox() { ?>
        <div class="idea-counter-module <?php echo val('status', $this->counter) ?>">
            <div class="score"><h1><?php echo val('score', $this->counter) ?></h1></div>
            <div class="vote idea-menu">
                <span class="idea-buttons">
                    <a class="up Hijack ReactButton-IdeaUp" href="<?php echo val('upUrl', $this->counter) ?>" title="Up" data-reaction="ideaup" rel="nofollow"><span class="icon icon-arrow-up"></span> <span class="idea-label">Up</span></a>
                    <a class="down Hijack ReactButton-IdeaDown" href="<?php echo val('downUrl', $this->counter) ?>" title="Down" data-reaction="ideadown" rel="nofollow"><span class="icon icon-arrow-down"></span> <span class="idea-label">Down</span></a>
                </span>
            </div>
            <div class="number-votes meta"><?php echo sprintf(t('Vote count: %s'), val('numberVotes', $this->counter)); ?></div>
        </div>
    <?php }

    protected function renderClosedCounterBox() { ?>
        <div class="idea-counter-module <?php echo val('status', $this->counter) ?>">
            <div class="score"><h1><?php echo val('score', $this->counter) ?></h1></div>
            <span class="Tag tag-stage"><?php echo val('stage', $this->counter) ?></span>
            <div class="number-votes meta"><?php echo sprintf(t('Vote count: %s'), val('numberVotes', $this->counter)); ?></div>
        </div>
    <?php }
}
