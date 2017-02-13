<?php if (!defined('APPLICATION')) exit();

$PluginInfo['DiscussionExcerpt'] = [
   'Description' => 'Adds an excerpt from the first comment in a discussion to the discussion list.',
   'Version' => '1.1',
   'RequiredApplications' => null,
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'Icon' => 'discussion_excerpt.png',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com/profile/mark'
];

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
