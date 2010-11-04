<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Emotify'] = array(
	'Name' => 'Emotify :)',
	'Description' => 'Replaces emoticons in forum comments with images.',
	'Version' 	=>	 '1.0',
	'Author' 	=>	 "Mark O'Sullivan",
	'AuthorEmail' => 'mark@vanillaforums.com',
	'AuthorUrl' =>	 'http://vanillaforums.org',
	'License' => 'GPL v2'
);

class EmotifyPlugin implements Gdn_IPlugin {
	
	public function PostController_Render_Before($Sender) {
		$this->_Emotify($Sender);
	}
	
	public function DiscussionController_Render_Before($Sender) {
		$this->_Emotify($Sender);
	}
	
	private function _Emotify($Sender) {
		$Sender->AddJsFile('plugins/Emotify/emotify.js');
		$Sender->AddCssFile('plugins/Emotify/emotify.css');
	}
	
	public function Setup() { }
	
}