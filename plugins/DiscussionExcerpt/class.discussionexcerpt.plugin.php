<?php if (!defined('APPLICATION')) exit();

$PluginInfo['DiscussionExcerpt'] = array(
   'Description' => 'Adds an excerpt from the first comment in a discussion to the discussion list.',
   'Version' => '1.1',
   'RequiredApplications' => NULL,
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com/profile/mark'
);

class DiscussionExcerptPlugin extends Gdn_Plugin {

	/**
	 * Add a discussion excerpt in each discussion list item.
	 */
	public function DiscussionsController_AfterDiscussionTitle_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		if (is_object($Discussion))
			echo '<div class="Excerpt">'.SliceString(Gdn_Format::Text($Discussion->Body, FALSE), 100).'</div>';
	}

   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
}