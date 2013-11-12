<?php if(!defined('APPLICATION')) die();

$PluginInfo['readless'] = array(
   'Name' => 'Read Less',
   'Description' => 'Shortens posts in a discussion to a given length, but allows the full text to be read by clicking the Read More button.',
   'Version' => '1.0.0',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2.2'),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/readless',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class ReadLess extends Gdn_Plugin {


   /**
	 * Replace emoticons in comment preview.
	 */
	public function PostController_AfterCommentPreviewFormat_Handler($Sender) {
		if ($this->emojiInterpretAllow) {
         $Sender->Comment->Body = $this->translateEmojiAliasesToHtml($Sender->Comment->Body);
      }
	}

   /**
	 * Replace emoticons in comments.
	 */
	public function Base_AfterCommentFormat_Handler($Sender) {
		if ($this->emojiInterpretAllow) {
         $Object = $Sender->EventArguments['Object'];
         $Object->FormatBody = $this->translateEmojiAliasesToHtml($Object->FormatBody);
         $Sender->EventArguments['Object'] = $Object;
      }
	}

}
