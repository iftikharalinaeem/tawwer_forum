<?php if (!defined('APPLICATION')) exit();

class DiscussionExcerptPlugin extends Gdn_Plugin {

	/**
	 * Add a discussion excerpt in each discussion list item.
	 *
	 * @param DiscussionsController $sender
	 */
	public function discussionsController_afterDiscussionTitle_handler($sender) {
		$this->addExcerpt($sender);
	}

	/**
	 * Add a discussion excerpt in each discussion list item.
	 *
	 * @param CategoriesController $sender
	 */
	public function categoriesController_afterDiscussionTitle_handler($sender) {
		$this->addExcerpt($sender);
	}

    /**
     * To be used with the `afterDiscussionTitle` event. Adds a discussion excerpt in each discussion list item.
     *
     * @param Gdn_Controller $sender
     */
	private function addExcerpt($sender) {
		$discussion = GetValue('Discussion', $sender->EventArguments);
		if (is_object($discussion)) {
			echo '<div class="Excerpt">'
				.sliceString(Gdn_Format::plainText($discussion->Body, $discussion->Format), c('Vanilla.DiscussionExcerpt.Length', 100))
				.'</div>';
		}
	}

	public function setup() {
		return true;
	}

	public function onDisable() {
		return true;
	}
}
