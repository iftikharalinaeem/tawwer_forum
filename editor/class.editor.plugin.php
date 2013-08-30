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
   protected $Formats = array('Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text', 'TextEx');
   
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
      
      // Grab default format, and if none, set to Html
      $Format = C('Garden.InputFormatter','Html');
      
      if (in_array($Format, $this->Formats)) {    

         // For developing, manually set format to toggle between views. 
         // Q: activating plugin on old edited content should do what?
         $Format = 'Wysiwyg';
         
         $c = Gdn::Controller();
         
         // This js file will asynchronously load the assets of each editor 
         // view when required. This will prevent unnecessary requests.
         $c->AddJsFile('editor.js', 'plugins/editor');
         
         
         // Set definitions for JavaScript
         $c->AddDefinition('editorInputFormat', $Format);
         $c->AddDefinition('editorLinkUrlText', T('editor.LinkUrlText', 'Type the URL:'));
         $c->AddDefinition('editorImageUrlText', T('editor.ImageUrlText', 'Type the image URL:'));
         $c->AddDefinition('editorWysiwygHelpText', T('editor.BBCodeHelpText', 'You can use <b><a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a></b> in your post.'));
         $c->AddDefinition('editorBBCodeHelpText', T('editor.BBCodeHelpText', 'You can use <b><a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a></b> in your post.'));
         $c->AddDefinition('editorHtmlHelpText', T('editor.HtmlHelpText', 'You can use <b><a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a></b> in your post.'));
         $c->AddDefinition('editorMarkdownHelpText', T('editor.MarkdownHelpText', 'You can use <b><a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a></b> in your post.'));
         $c->AddDefinition('editorPluginAssets', Url('/plugins/editor/'));
         
         
         
         
         // Set data for view
         
         //$
         
         // Basic editing (bold, italic, strike, headers+, colors+)
         
         
         
         
         
         $c->SetData('_Toolbar', array('format' => $Format));
         
         /*
         if (!isset($c->Data['_Toolbar'])) {
            $toolbar = array(
                array('type' => 'link', 'class' => "icon icon-bold", 'data-wysihtml5-command' => "bold", 'title' => "Bold")
            );
            
            $this->EventArguments['Toolbar'] =& $toolbar;
            $this->FireEvent('InitToolbar');
            $c->SetData('_Toolbar', $toolbar);
         }
          
          */
         //$c->SetData($Format);
         
         
         // Determine which controller (post or discussion) is invoking this.
         $View = $c->FetchView('editor', '', 'plugins/editor');
         if ($c instanceof PostController) {
            echo Wrap($View, 'div', array('class' => 'P'));
         } else {
            echo $View;
         }
      }
      
   }
   
}