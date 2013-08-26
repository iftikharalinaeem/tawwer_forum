<?php if(!defined('APPLICATION')) die();

$PluginInfo['editor'] = array(
   'Name' => 'editor',
   'Description' => 'Enables advanced editing of posts in several formats, including WYSIWYG, simple HTML, Markdown, and BBCode.',
   'Version' => '1.0.0',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => FALSE,
   'SettingsUrl' => FALSE,
   'SettingsPermission' => FALSE
);

class EditorPlugin extends Gdn_Plugin {
   
   // Must be one of these formats
   protected $Formats = array('Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text');

   /**
    * Insert ButtonBar resource files on every page so they are available
    * to any new uses of BodyBox in plugins and applications.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Base_Render_Before($Sender) {
      
      //$this->LoadEditorResources($Sender, C('Garden.InputFormatter','Html'));
      
      $Formatter = C('Garden.InputFormatter','Html');
         
      if (in_array($Formatter, $this->Formats)) {
         $Sender->AddJsFile('wysihtml5.js', 'plugins/editor');
         $Sender->AddJsFile('advanced.js', 'plugins/editor');
         $Sender->AddJsFile('jquery.wysihtml5_size_matters.js', 'plugins/editor');
         $Sender->AddJsFile('editor.js', 'plugins/editor');

         // When manipulating textarea, store the editor format for reference.
         $Sender->AddDefinition('InputFormat', $Formatter);

         $Sender->AddDefinition('editorLinkUrlText', T('editor.LinkUrlText', 'Type the URL:'));
         $Sender->AddDefinition('editorImageUrlText', T('editor.ImageUrlText', 'Type the image URL:'));
         $Sender->AddDefinition('editorWysiwygHelpText', T('editor.BBCodeHelpText', 'You can use <b><a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a></b> in your post.'));
         $Sender->AddDefinition('editorBBCodeHelpText', T('editor.BBCodeHelpText', 'You can use <b><a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a></b> in your post.'));
         $Sender->AddDefinition('editorHtmlHelpText', T('editor.HtmlHelpText', 'You can use <b><a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a></b> in your post.'));
         $Sender->AddDefinition('editorMarkdownHelpText', T('editor.MarkdownHelpText', 'You can use <b><a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a></b> in your post.'));
      }
   }
   
   public function AssetModel_StyleCss_Handler($Sender) {
      
      $Sender->AddCssFile('editor.css', 'plugins/editor');
   }
      
   /**
    * Attach editor anywhere 'BodyBox' is used.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender) {
      
      if (in_array(C('Garden.InputFormatter','Html'), $this->Formats)) {
         $View = Gdn::Controller()->FetchView('editor','','plugins/editor');

         // Determine which controller (post or discussion) is invoking this.
         if (Gdn::Controller() instanceof PostController) {
            echo Wrap($View, 'div', array('class' => 'P'));
         } else {
            echo $View;
         }
      }
      
   }
   
   /**
    * Load editor resources
    * 
    * This method is abstracted because it is invoked by multiple controllers.
    * 
    * @param Gdn_Controller $Sender 
    */
   protected function LoadEditorResources($Sender, $Formatter) {
   
   }
   
}