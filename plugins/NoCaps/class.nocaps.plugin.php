<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['NoCaps'] = array(
	'Description' => 'Prevents ALL CAPS discussion titles',
	'Version' => '1.0.0',
	'RequiredApplications' => array('Vanilla' => '2.1'),
	'RequiredTheme' => FALSE,
	'RequiredPlugins' => FALSE,
	'HasLocale' => FALSE,
	'MobileFriendly' => TRUE,
	'Author' => "James Ducker",
	'AuthorEmail' => 'james.ducker@gmail.com',
	'AuthorUrl' => 'http://www.ozvolvo.org'
);

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