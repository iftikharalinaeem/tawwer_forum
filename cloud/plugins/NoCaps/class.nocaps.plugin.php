<?php if (!defined('APPLICATION')) exit();

class NoCapsPlugin extends Gdn_Plugin {

	public function discussionModel_beforeSaveDiscussion_handler($sender) {

		$formPostValues = $sender->EventArguments['FormPostValues'];

		if ($formPostValues['Name'] == mb_strtoupper($formPostValues['Name'], 'utf-8')) {

			$formPostValues['Name'] = ucwords(strtolower($formPostValues['Name']));
			$sender->EventArguments['FormPostValues'] = $formPostValues;
		}
	}

	public function setup() { /* Nothing to setup */ }
}

?>
