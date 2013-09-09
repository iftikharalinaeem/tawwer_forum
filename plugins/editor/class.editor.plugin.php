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

class EditorPlugin extends Gdn_Plugin 
{   
   // Must be one of these formats
   protected $Formats = array('Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text', 'TextEx');
   protected $Format;
   protected $AssetPath;
   
   public function AssetModel_StyleCss_Handler($Sender) 
   {   
      $Sender->AddCssFile('editor.css', 'plugins/editor');
   }
      
   /**
    * Attach editor anywhere 'BodyBox' is used. It is not being used for 
    * editing a posted reply, so find another event to hook into.
    * 
    * @param Gdn_Form $Sender 
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender) 
   {   
      // Grab default format, and if none, set to Html
      $this->Format    = $Sender->GetValue('Format', C('Garden.InputFormatter','Html'));
      $this->AssetPath = Asset('/plugins/editor');
      
      if (in_array($this->Format, $this->Formats)) {    

         // This is only for testing
         $this->Format = 'Html';
         
         $c = Gdn::Controller();
         
         // This js file will asynchronously load the assets of each editor 
         // view when required. This will prevent unnecessary requests.
         $c->AddJsFile('editor.js', 'plugins/editor');
         
         // Set minor data for view
         $c->SetData('_EditorInputFormat', $this->Format);
         
         // Set definitions for JavaScript
         $c->AddDefinition('editorInputFormat',       $this->Format);
         $c->AddDefinition('editorPluginAssets',      $this->AssetPath);         
         $c->AddDefinition('editorButtonBarLinkUrl',  T('editor.LinkUrlText', 'Enter URL:'));
         $c->AddDefinition('editorButtonBarImageUrl', T('editor.ImageUrlText', 'Enter image URL:'));
         $c->AddDefinition('editorWysiwygHelpText',   T('editor.WysiwygHelpText', 'You are using <a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a> in your post.'));
         $c->AddDefinition('editorBBCodeHelpText',    T('editor.BBCodeHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a> in your post.'));
         $c->AddDefinition('editorHtmlHelpText',      T('editor.HtmlHelpText', 'You can use <a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a> in your post.'));
         $c->AddDefinition('editorMarkdownHelpText',  T('editor.MarkdownHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a> in your post.'));
         $c->AddDefinition('editorTextHelpText',      T('editor.TextHelpText', 'You are using plain text in your post.'));

         /**
          * Build view data
          */
         
         if ($this->Format != 'Text' && !isset($c->Data['_Toolbar'])) {
            
            // TODO build all dropdowns like this, and clean up view.
            // Build emoji dropdown
            $emojiToolbarDropdown = array();
            $emojiAliasList = $this->getEmojiAliasList();
            
            foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
               $emojiFilePath          = $this->getEmojiCanonicalList($emojiCanonical);
               $emojiWidth             = 20;
               $emojiHeight            = 20;
                              
               $emojiToolbarDropdown[] = array('edit' => 'media', 'action'=> 'emoji', 'type' => 'button', 'attr' => array('class' => 'editor-action emoji emoji-'. $emojiCanonical. ' editor-dialog-fire-close', 'data-wysihtml5-command' => 'insertHTML', 'data-wysihtml5-command-value' => $emojiAlias, 'title' => $emojiAlias, 'src' => $emojiFilePath, 'width' => $emojiWidth, 'height' => $emojiHeight, 'alt' => $emojiCanonical));
            }

            $toolbar = array(
               // Basic editing (bold, italic, strike, headers+, colors+)
               array('edit' => 'basic', 'action'=> 'bold', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-bold', 'data-wysihtml5-command' => 'bold', 'title' => 'Bold')),
               array('edit' => 'basic', 'action'=> 'italic', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-italic', 'data-wysihtml5-command' => 'italic', 'title' => 'Italic')),
               array('edit' => 'basic', 'action'=> 'strike', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-strikethrough', 'data-wysihtml5-command' => 'strikethrough', 'title' => 'Strike')),
               /*
               // todo create by loop against array of colors
               // TODO build like emoji dropdown.
               array('edit' => 'basic', 'action'=> 'color', 'type' => array(
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-black editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'black', 'title' => 'Black')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-white editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'white', 'title' => 'White')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-gray editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'gray', 'title' => 'Gray')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-silver editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'silver', 'title' => 'silver')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-maroon editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'maroon', 'title' => 'Maroon')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-red editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'red', 'title' => 'Red')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-purple editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'purple', 'title' => 'Purple')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-green editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'green', 'title' => 'Green')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-olive editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'olive', 'title' => 'Olive')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-navy editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'navy', 'title' => 'Navy')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-blue editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'blue', 'title' => 'Blue')),
                   array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-lime editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => 'lime', 'title' => 'Lime')),
               ), 'attr' => array('class' => 'icon icon-font hidden-xs', 'data-wysihtml5-command-group' => 'foreColor', 'title' => 'Color')),
               */
               array('edit' => 'format', 'action'=> 'orderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ol hidden-xs', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => 'Ordered list')),
               array('edit' => 'format', 'action'=> 'unorderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ul hidden-xs', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => 'Unordered list')),

               // Unique / heading editing (quotation, code, spoilers)
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-headers hidden-xs')),              
               array('edit' => 'headers', 'action'=> 'headers', 'type' => array(
                   array('edit' => 'headers', 'action'=> 'color', 'type' => 'button', 'text' => 'Heading 1', 'attr' => array('class' => 'editor-action editor-action-h1 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h1', 'title' => 'Header 1')),
                   array('edit' => 'headers', 'action'=> 'color', 'type' => 'button', 'text' => 'Heading 2', 'attr' => array('class' => 'editor-action editor-action-h2 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h2', 'title' => 'Header 2')),
                   array('edit' => 'headers', 'action'=> 'quote', 'type' => 'button', 'text' => 'Quote', 'attr' => array('class' => 'editor-action editor-action-quote editor-dialog-fire-close', 'data-wysihtml5-command' => 'blockquote', 'title' => 'Quote')),
                   array('edit' => 'headers', 'action'=> 'code', 'type' => 'button', 'text' => 'Code', 'attr' => array('class' => 'editor-action editor-action-code editor-dialog-fire-close', 'data-wysihtml5-command' => 'code', 'title' => 'Code')),
                   array('edit' => 'headers', 'action'=> 'spoiler', 'type' => 'button', 'text' => 'Spoiler', 'attr' => array('class' => 'editor-action editor-action-spoiler editor-dialog-fire-close', 'data-wysihtml5-command' => 'spoiler', 'title' => 'Spoiler')),
               ), 'attr' => array('class' => 'icon icon-edit', 'title' => 'Headers')),
                             
                
               // Media editing (links, images)
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-media hidden-xs')),
               array('edit' => 'media', 'action'=> 'emoji', 'type' => $emojiToolbarDropdown, 'attr' => array('class' => 'editor-action icon icon-smile', 'data-wysihtml5-command' => '', 'title' => 'Emoji')), 
               array('edit' => 'media', 'action'=> 'link', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-link', 'data-wysihtml5-command' => 'createLink', 'title' => 'Url')), 
               array('edit' => 'media', 'action'=> 'image', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-picture', 'data-wysihtml5-command' => 'insertImage', 'title' => 'Image')), 

               // Format editing (justify, list)             
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-format hidden-xs')),
               array('edit' => 'format', 'action'=> 'alignleft', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-left hidden-xs', 'data-wysihtml5-command' => 'justifyLeft', 'title' => 'Align left')),
               array('edit' => 'format', 'action'=> 'aligncenter', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-center hidden-xs', 'data-wysihtml5-command' => 'justifyCenter', 'title' => 'Align center')),
               array('edit' => 'format', 'action'=> 'alignright', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-right hidden-xs', 'data-wysihtml5-command' => 'justifyRight', 'title' => 'Align right')),
   
               // Editor switches (toggle source, fullpage)
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-switches hidden-xs')),
               array('edit' => 'switches', 'action'=> 'togglehtml', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-source editor-toggle-source hidden-xs', 'data-wysihtml5-action' => 'change_view', 'title' => 'Toggle HTML view')),
               array('edit' => 'switches', 'action'=> 'fullpage', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-resize-full editor-toggle-fullpage-button', 'title' => 'Toggle full page')),
            );

            $this->EventArguments['Toolbar'] =& $toolbar;
            $this->FireEvent('InitToolbar');
            // Set data for view
            $c->SetData('_Toolbar', $toolbar);
         }
         
         // Determine which controller (post or discussion) is invoking this.
         // At the moment they're both the same, but in future you may want 
         // to know this information to modify it accordingly.
         $View = $c->FetchView('editor', '', 'plugins/editor');
         if ($c instanceof PostController) {
            echo $View;
         } else {
            echo $View;
         }
      }
      
   }
   
   /**
    * 
    * @param string $emojiFileName File name of emoji icon.
    * @return string Root-relative path. 
    */
   public function buildEmojiFilePath($emojiFileName) 
   {   
      return $this->AssetPath . '/design/images/emoji/' . $emojiFileName;
   }
   
   /**
    * Populate this with any aliases required for plugin, make sure they point 
    * to canonical translation, and plugin will add everything to dropdown that 
    * is listed. 
    * 
    * @param string $emojiAlias Optional string to return matching translation
    * @return string|array Canonical translation or full alias array
    */
   public function getEmojiAliasList($emojiAlias = '') 
   {  
     $emojiAliasList = array(
        ':)'   => 'smile',
        ':D'   => 'smiley',
        ':('   => 'disappointed',
        ';)'   => 'wink',
        ':\\'  => 'confused',
        ':o'   => 'open_mouth',
        ':s'   => 'confounded',
        ':p'   => 'stuck_out_tongue',
        ':\'(' => 'cry',
        ':|'   => 'neutral_face',
        'D:'   => 'anguished',
        '>:)'  => 'smiling_imp',
        'o:)'  => 'innocent',
        '8)'   => 'sunglasses',
        '(y)'  => '+1',
        '(n)'  => 'thumbsdown'
      );
     
     return (!$emojiAlias)
        ? $emojiAliasList
        : $emojiAliasList[$emojiAlias];    
   }
   
   /**
    * 
    * @param type $emojiCanonical Optional string to return matching file name.
    * @return string|array File name or full canonical array
    */
   public function getEmojiCanonicalList($emojiCanonical = '') 
   {
      $emojiCanonicalList = array(
        // Smileys
        'relaxed'                      => array('50.png'),  
        'grinning'                     => array('701.png'),  
        'grin'                         => array('702.png'),  
        'joy'                          => array('703.png'),  
        'smiley'                       => array('704.png'),  
        'smile'                        => array('705.png'),  
        'sweat_smile'                  => array('706.png'),  
        'satisfied'                    => array('707.png'),  
        'innocent'                     => array('708.png'),  
        'smiling_imp'                  => array('709.png'),  
        'wink'                         => array('710.png'),  
        'blush'                        => array('711.png'),  
        'yum'                          => array('712.png'),  
        'relieved'                     => array('713.png'),  
        'heart_eyes'                   => array('714.png'),  
        'sunglasses'                   => array('715.png'),  
        'smirk'                        => array('716.png'),  
        'neutral_face'                 => array('717.png'),  
        'expressionless'               => array('718.png'),  
        'unamused'                     => array('719.png'),  
        'sweat'                        => array('720.png'),  
        'pensive'                      => array('721.png'),  
        'confused'                     => array('722.png'),  
        'confounded'                   => array('723.png'),  
        'kissing'                      => array('724.png'),  
        'kissing_heart'                => array('725.png'),  
        'kissing_smiling_eyes'         => array('726.png'),  
        'kissing_closed_eyes'          => array('727.png'),  
        'stuck_out_tongue'             => array('728.png'),  
        'stuck_out_tongue_winking_eye' => array('729.png'),  
        'stuck_out_tongue_closed_eyes' => array('730.png'),  
        'disappointed'                 => array('731.png'),  
        'worried'                      => array('732.png'),  
        'angry'                        => array('733.png'),  
        'rage'                         => array('734.png'),  
        'cry'                          => array('735.png'),  
        'persevere'                    => array('736.png'),  
        'triump'                       => array('737.png'),  
        'disapponted_relieved'         => array('738.png'),  
        'frowning'                     => array('739.png'),  
        'anguished'                    => array('740.png'),  
        'fearful'                      => array('741.png'),  
        'weary'                        => array('742.png'),  
        'sleepy'                       => array('743.png'),  
        'tired_face'                   => array('744.png'),  
        'grimacing'                    => array('745.png'),  
        'sob'                          => array('746.png'),  
        'open_mouth'                   => array('747.png'),  
        'hushed'                       => array('748.png'),  
        'cold_sweat'                   => array('749.png'),  
        'scream'                       => array('750.png'),  
        'astonished'                   => array('751.png'),  
        'flushed'                      => array('752.png'),  
        'sleeping'                     => array('753.png'),  
        'dizzy_face'                   => array('754.png'),  
        'no_mouth'                     => array('755.png'),  
        'mask'                         => array('756.png'),  

        // Love
        'heart'                        => array('109.png'),  
        'broken_heart'                 => array('506.png'),  
        'kiss'                         => array('497.png'),

        // Hand gestures
        '+1'                           => array('435.png'),  
        'thumbsdown'                   => array('436.png')
      );

      // Return first value from canonical array
      return (!$emojiCanonical)
         ? $emojiCanonicalList
         : $this->buildEmojiFilePath(reset($emojiCanonicalList[$emojiCanonical]));
   }
   
   /**
	 * Every time editor plugin is enabled, disable other known editors that 
    * may clash with this one. If editor is loaded, then these two other 
    * editors loaded after, there are CSS rules that hide them. This way, 
    * the editor plugin always takes precedence.
	 */
	public function Setup() {        
      $pluginEditors = array(
          'cleditor', 
          'ButtonBar'
      );
      
      foreach ($pluginEditors as $pluginName) {
         Gdn::PluginManager()->DisablePlugin($pluginName); 
      }

      SaveToConfig('Plugin.editor.DefaultView', 'Wysiwyg');
	}
   
   public function OnDisable() {
		//RemoveFromConfig('Plugin.editor.DefaultView');
	}

   public function CleanUp() {
		RemoveFromConfig('Plugin.editor.DefaultView');
	}
   
}