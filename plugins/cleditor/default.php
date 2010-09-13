<?php
if(!defined('APPLICATION')) die();

/*
Plugin adds CLEditor (http://premiumsoftware.net/cleditor/) jQuery WYSIWYG to Vanilla 2

Included files:
1. jquery.cleditor.min.js (as v.1.2.2 - unchanged)
2. jquery.cleditor.css (as v.1.2.2 - unchanged)
3. images/toolbar.gif (as v.1.2.2 - unchanged)
4. images/buttons.gif (as v.1.2.2 - unchanged)

Changelog:
v0.1: 25AUG2010 - Initial release. 
- Known bugs: 
-- 1. Both HTML and WYSIWYG view are visible in 'Write comment' view. Quick fix: click HTML view button twice to toggle on/off.

Optional: Edit line 19 of jquery.cleditor.min.js to remove extra toolbar buttons.
*/

$PluginInfo['cleditor'] = array(
   'Name' => 'CLEditor jQuery WYSIWYG',
   'Description' => '<a href="http://premiumsoftware.net/cleditor/" target="_blank">CLEditor</a> jQuery WYSIWYG plugin for Vanilla 2.',
   'Version' => '0.1',
   'Author' => "Mirabilia Media",
   'AuthorEmail' => 'info@mirabiliamedia.com',
   'AuthorUrl' => 'http://mirabiliamedia.com',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => FALSE,
   'SettingsUrl' => FALSE,
   'SettingsPermission' => FALSE
);

class cleditorPlugin extends Gdn_Plugin {

	public function Base_Render_Before(&$Sender) {     

      $Sender->AddJsFile($this->GetResource('jquery.cleditor.min.js', FALSE, FALSE));
      $Sender->AddCssFile($this->GetResource('jquery.cleditor.css', FALSE, FALSE));
	  $Sender->Head->AddString('<script type="text/javascript">$(function() {$("#Form_Body").cleditor({width:"100%", height:"100%"});});</script>');

   }

	public function Setup(){}

}

?>