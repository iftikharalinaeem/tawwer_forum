<?php if (!defined('APPLICATION')) exit();

$PluginInfo['GoogleTranslateComments'] = array(
   'Name' => 'Google Translate Comments',
   'Description' => 'Adds a Google Translate widget to discussion pages so comments can be translated from other languages.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class GoogleTranslateCommentsPlugin implements Gdn_IPlugin {

   public function Base_Render_Before($Sender) {
      $Sender->Head->AddString("<script>
function googleSectionalElementInit() {
  new google.translate.SectionalElement({
    sectionalNodeClassName: 'Message',
    controlNodeClassName: 'GoogleTranslateControl',
    background: '#ffeeaa'
  }, 'google_sectional_element');
}
</script>
<script src=\"//translate.google.com/translate_a/element.js?cb=googleSectionalElementInit&ug=section&hl=en\"></script>");
   }
   
   public function Base_AfterCommentFormat_Handler($Sender) {
      $Object = $Sender->EventArguments['Object'];
		$Object->FormatBody = '<div class="GoogleTranslateControl"></div>' . $Object->FormatBody;
   }
   
   public function Setup() {
      // No setup required.
   }
}