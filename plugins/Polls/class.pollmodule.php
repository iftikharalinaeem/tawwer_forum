<?php if (!defined('APPLICATION')) exit();
class PollModule extends Gdn_Module {

	public function __construct(&$Sender = '') {
		parent::__construct($Sender);
	}

	public function AssetTarget() {
		return 'Panel';
	}

	public function ToString() {
		$String = '';
		ob_start();
      include(PATH_PLUGINS.'/Polls/views/poll.php');
		$String = ob_get_contents();
		@ob_end_clean();
		return $String;
	}
}
