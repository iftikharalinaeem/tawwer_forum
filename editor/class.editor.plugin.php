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
   
   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('editor.css', 'plugins/editor');
   }
      
   /**
    * Attach editor anywhere 'BodyBox' is used. It is not being used for 
    * editing a posted reply, so find another event to hook into.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender) {
      if (in_array(C('Garden.InputFormatter','Html'), $this->Formats)) {         
         Gdn::Controller()->AddJsFile('wysihtml5.js', 'plugins/editor');
         Gdn::Controller()->AddJsFile('advanced.js', 'plugins/editor');
         Gdn::Controller()->AddJsFile('jquery.wysihtml5_size_matters.js', 'plugins/editor');
         Gdn::Controller()->AddJsFile('editor.js', 'plugins/editor');
         
         // When manipulating textarea, store the editor format for reference.
         Gdn::Controller()->AddDefinition('InputFormat', $Formatter);
         Gdn::Controller()->AddDefinition('editorLinkUrlText', T('editor.LinkUrlText', 'Type the URL:'));
         Gdn::Controller()->AddDefinition('editorImageUrlText', T('editor.ImageUrlText', 'Type the image URL:'));
         Gdn::Controller()->AddDefinition('editorWysiwygHelpText', T('editor.BBCodeHelpText', 'You can use <b><a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a></b> in your post.'));
         Gdn::Controller()->AddDefinition('editorBBCodeHelpText', T('editor.BBCodeHelpText', 'You can use <b><a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a></b> in your post.'));
         Gdn::Controller()->AddDefinition('editorHtmlHelpText', T('editor.HtmlHelpText', 'You can use <b><a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a></b> in your post.'));
         Gdn::Controller()->AddDefinition('editorMarkdownHelpText', T('editor.MarkdownHelpText', 'You can use <b><a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a></b> in your post.'));
         
         // Called in JS
         Gdn::Controller()->AddDefinition('editorPluginAssets', Url('/plugins/editor/'));
         
         // Determine which controller (post or discussion) is invoking this.
         $View = Gdn::Controller()->FetchView('editor','','plugins/editor');
         if (Gdn::Controller() instanceof PostController) {
            echo Wrap($View, 'div', array('class' => 'P'));
         } else {
            echo $View;
         }
      }
      
   }
   
}