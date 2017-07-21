<?php if (!defined('APPLICATION')) exit();

class GoogleTranslateCommentsPlugin implements Gdn_IPlugin {

   public function Base_Render_Before($sender) {
      $sender->Head->AddString("<script>
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
   
   public function Base_AfterCommentFormat_Handler($sender) {
      $object = $sender->EventArguments['Object'];
		$object->FormatBody = '<div class="GoogleTranslateControl"></div>' . $object->FormatBody;
   }
   
   public function Setup() {
      // No setup required.
   }
}
