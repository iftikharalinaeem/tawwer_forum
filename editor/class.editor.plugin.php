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
         // TODO remove this so it can be overriden by site owners
         // This is only for testing
         $Format = 'Wysiwyg';
         
         $c = Gdn::Controller();
         
         // This js file will asynchronously load the assets of each editor 
         // view when required. This will prevent unnecessary requests.
         $c->AddJsFile('editor.js', 'plugins/editor');
         
         // Set definitions for JavaScript
         $c->AddDefinition('editorInputFormat',       $Format);
         $c->AddDefinition('editorPluginAssets',      Asset('/plugins/editor'));         
         $c->AddDefinition('editorButtonBarLinkUrl',  T('editor.LinkUrlText', 'Enter URL:'));
         $c->AddDefinition('editorButtonBarImageUrl', T('editor.ImageUrlText', 'Enter image URL:'));
         $c->AddDefinition('editorWysiwygHelpText',   T('editor.WysiwygHelpText', 'You are using <a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a> in your post.'));
         $c->AddDefinition('editorBBCodeHelpText',    T('editor.BBCodeHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a> in your post.'));
         $c->AddDefinition('editorHtmlHelpText',      T('editor.HtmlHelpText', 'You can use <a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a> in your post.'));
         $c->AddDefinition('editorMarkdownHelpText',  T('editor.MarkdownHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a> in your post.'));

         /**
          * Build view data
          */
         
         if (!isset($c->Data['_Toolbar'])) {

            $toolbar = array(
               // Basic editing (bold, italic, strike, headers+, colors+)
               array('edit' => 'basic', 'action'=> 'bold', 'type' => 'button', 'attr' => array('class' => 'icon icon-bold', 'data-wysihtml5-command' => 'bold', 'title' => 'Bold')),
               array('edit' => 'basic', 'action'=> 'italic', 'type' => 'button', 'attr' => array('class' => 'icon icon-italic', 'data-wysihtml5-command' => 'italic', 'title' => 'Italic')),
               array('edit' => 'basic', 'action'=> 'strike', 'type' => 'button', 'attr' => array('class' => 'icon icon-strikethrough', 'data-wysihtml5-command' => 'strikethrough', 'title' => 'Strike')),
               /*array('edit' => 'basic', 'action'=> 'color', 'type' => array(
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-black', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'black', 'title' => 'Black')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-white', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'white', 'title' => 'White')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-gray', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'gray', 'title' => 'Gray')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-silver', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'silver', 'title' => 'silver')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-maroon', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'maroon', 'title' => 'Maroon')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-red', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'red', 'title' => 'Red')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-purple', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'purple', 'title' => 'Purple')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-green', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'green', 'title' => 'Green')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-olive', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'olive', 'title' => 'Olive')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-navy', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'navy', 'title' => 'Navy')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-blue', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'blue', 'title' => 'Blue')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-lime', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'lime', 'title' => 'Lime')),
               ), 'attr' => array('class' => 'icon icon-font hidden-xs', 'data-wysihtml5-command-group' => 'foreColor', 'title' => 'Color')),
               */
               array('edit' => 'format', 'action'=> 'orderedlist', 'type' => 'button', 'attr' => array('class' => 'icon icon-list-ol hidden-xs', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => 'Insert ordered list')),
               array('edit' => 'format', 'action'=> 'unorderedlist', 'type' => 'button', 'attr' => array('class' => 'icon icon-list-ul hidden-xs', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => 'Insert uniordered list')),

               // Unique editing (quotation, code, spoilers)
               array('type' => 'separator'),
               array('edit' => 'unique', 'action'=> 'quote', 'type' => 'button', 'attr' => array('class' => 'icon icon-quote hidden-xs', 'data-wysihtml5-command' => 'blockquote', 'title' => 'Quote')),
               array('edit' => 'unique', 'action'=> 'code', 'type' => 'button', 'attr' => array('class' => 'icon icon-code hidden-xs', 'data-wysihtml5-command' => 'code', 'title' => 'Code')),
               array('edit' => 'unique', 'action'=> 'spoiler', 'type' => 'button', 'attr' => array('class' => 'icon icon-ellipsis hidden-xs', 'data-wysihtml5-command' => 'spoiler', 'title' => 'Spoiler')),

               // Media editing (links, images)
               array('type' => 'separator'),
               array('edit' => 'media', 'action'=> 'link', 'type' => array(), 'attr' => array('class' => 'icon icon-link', 'data-wysihtml5-command' => 'createLink', 'title' => 'Url')), 
               array('edit' => 'media', 'action'=> 'image', 'type' => array(), 'attr' => array('class' => 'icon icon-picture', 'data-wysihtml5-command' => 'insertImage', 'title' => 'Image')), 

               // Format editing (justify, list)             
               array('type' => 'separator'),
               array('edit' => 'format', 'action'=> 'alignleft', 'type' => 'button', 'attr' => array('class' => 'icon icon-align-left hidden-xs', 'data-wysihtml5-command' => 'justifyLeft', 'title' => 'Align left')),
               array('edit' => 'format', 'action'=> 'aligncenter', 'type' => 'button', 'attr' => array('class' => 'icon icon-align-center hidden-xs', 'data-wysihtml5-command' => 'justifyCenter', 'title' => 'Align center')),
               array('edit' => 'format', 'action'=> 'alignright', 'type' => 'button', 'attr' => array('class' => 'icon icon-align-right hidden-xs', 'data-wysihtml5-command' => 'justifyRight', 'title' => 'Align right')),
   
               // Editor switches (toggle source, fullpage)
               array('type' => 'separator'),
               array('edit' => 'switches', 'action'=> 'togglehtml', 'type' => 'button', 'attr' => array('class' => 'icon icon-source editor-toggle-source hidden-xs', 'data-wysihtml5-action' => 'change_view', 'title' => 'Toggle HTML view')),
               array('edit' => 'switches', 'action'=> 'fullpage', 'type' => 'button', 'attr' => array('class' => 'icon icon-resize-full editor-toggle-fullpage-button', 'title' => 'Toggle full page')),
            );

            $this->EventArguments['Toolbar'] =& $toolbar;
            $this->FireEvent('InitToolbar');
            $c->SetData('_Toolbar', $toolbar);
            $c->SetData('_EditorInputFormat', $Format);
         }         
         
         // Determine which controller (post or discussion) is invoking this.
         $View = $c->FetchView('editor', '', 'plugins/editor');
         if ($c instanceof PostController) {
            echo $View;
         } else {
            echo $View;
         }
      }
      
   }
   
   /**
	 * Every time editor plugin is enabled, disable other known editors that 
    * may clash with this one. If editor is loaded, then these two other 
    * editors loaded after, there are CSS rules that hide them. This way, 
    * the editor plugin always takes precedence.
	 */
	public function Setup() 
	{        
      $pluginEditors = array(
          'cleditor', 
          'ButtonBar'
      );
      
      foreach ($pluginEditors as $pluginName) {
         Gdn::PluginManager()->DisablePlugin($pluginName); 
      }

      SaveToConfig('Plugin.editor.DefaultView', 'Wysiwyg');
	}
   
   public function OnDisable() 
	{
		//RemoveFromConfig('Plugin.editor.DefaultView');
	}

   public function CleanUp()
	{
		RemoveFromConfig('Plugin.editor.DefaultView');
	}
   
}