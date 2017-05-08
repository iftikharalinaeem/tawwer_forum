<?php if (!defined('APPLICATION')) exit();

class NoCapsPlugin extends Gdn_Plugin {

	public function DiscussionModel_BeforeSaveDiscussion_Handler($Sender) {

		$FormPostValues = $Sender->EventArguments['FormPostValues'];

		if ($FormPostValues['Name'] == mb_strtoupper($FormPostValues['Name'], 'utf-8')) {

			$FormPostValues['Name'] = ucwords(strtolower($FormPostValues['Name']));
			$Sender->EventArguments['FormPostValues'] = $FormPostValues;
		}
	}

	public function Setup() { /* Nothing to setup */ }
}

?>
