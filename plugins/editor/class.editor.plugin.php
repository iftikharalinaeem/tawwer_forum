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
   /// Properties ///
   
   // Must be one of these formats
   protected $Formats = array('Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text', 'TextEx');
   protected $Format;
   protected $AssetPath;
   public $emojiDimension = 20;
   
   /// Methods ///

   public function __construct() {
      parent::__construct();
      $this->AssetPath = Asset('/plugins/editor');      
   }
   
   /**
    * To enable more colors in the dropdown, simply expand the array to 
    * include more human-readable font color names. 
    * 
    * Note: in building the dropdown, each color is styled inline, but it will
    * still be required to add the appropriate post-color-* CSS class selectors 
    * in the external stylesheet, so that when viewing a posted comment, the 
    * color will appear. 
    * 
    * @return array Returns array of font colors to use in dropdown
    */
   protected function getFontColorList() {
      $fontColorList = array(
         'black', 
         'white',
         'gray',
         'silver',
         'maroon',
         'red',
         'purple',
         'green',
         'olive',
         'navy',
         'blue',
         'lime'     
      );
      
      return $fontColorList;
   }
   
   /**
    * Provide this method with the official emoji filename and it will return 
    * the correct path. 
    * 
    * @param string $emojiFileName File name of emoji icon.
    * @return string Root-relative path. 
    */
   protected function buildEmojiFilePath($emojiFileName) {   
      return $this->AssetPath . '/design/images/emoji/' . $emojiFileName;
   }
   
   /**
    * Populate this with any aliases required for plugin, make sure they point 
    * to canonical translation, and plugin will add everything to dropdown that 
    * is listed. To expand, simply define more aliases that corresponded with 
    * canonical list.
    * 
    * @param string $emojiAlias Optional string to return matching translation
    * @return string|array Canonical translation or full alias array
    */
   protected function getEmojiAliasList($emojiAlias = '') {  
     $emojiAliasList = array(
        ':)'      => 'smile',
        ':D'      => 'smiley',
        ':('      => 'disappointed',
        ';)'      => 'wink',
        ':\\'     => 'confused',
        ':o'      => 'open_mouth',
        ':s'      => 'confounded',
        ':p'      => 'stuck_out_tongue',
        ':\'('    => 'cry',
        ':|'      => 'neutral_face',
        'D:'      => 'anguished',
        '8)'      => 'sunglasses',
        'o:)'     => 'innocent',
        '+1'     => '+1',
        '-1'     => '-1',
        '>:)'     => 'smiling_imp', 
        ':#'      => 'grin',
        ':sleeping:'  => 'sleeping',
        '<3'      => 'heart',
        ':triumph:' => 'triumph'
      );
     
     return (!$emojiAlias)
        ? $emojiAliasList
        : $emojiAliasList[$emojiAlias];    
   }
   
   /**
    * This is the canonical, e.g., official, list of emoji names along with 
    * their associatedwith image file name. For an exhaustive list of emoji 
    * names visit http://www.emoji-cheat-sheet.com/ and for the original image 
    * files being used, visit https://github.com/taninamdar/Apple-Color-Emoji
    * 
    * @param type $emojiCanonical Optional string to return matching file name.
    * @return string|array File name or full canonical array
    */
   protected function getEmojiCanonicalList($emojiCanonical = '') {
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
        'triumph'                       => array('737.png'),  
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
        '-1'                           => array('436.png'),
        
        // Custom icons, canonical naming
        'trollface'                    => array('trollface.png')
      );
      
      // Some aliases self-referencing the canonical list
      
      // Vanilla reactions, non-canonical referencing canonical
      $emojiCanonicalList['lol']       = &$emojiCanonicalList['smile'];
      $emojiCanonicalList['wtf']       = &$emojiCanonicalList['dizzy_face'];
      $emojiCanonicalList['agree']     = &$emojiCanonicalList['grinning'];
      $emojiCanonicalList['disagree']  = &$emojiCanonicalList['stuck_out_tongue_closed_eyes'];
      $emojiCanonicalList['awesome']   = &$emojiCanonicalList['heart'];

      // Return first value from canonical array
      return (!$emojiCanonical)
         ? $emojiCanonicalList
         : $this->buildEmojiFilePath(reset($emojiCanonicalList[$emojiCanonical]));
   }
   
   
   
   
   
   
   /**
	 * Thanks to punbb 1.3.5 (GPL License) for this function - ported from their do_smilies function.
	 */
   /*
	public function DoEmoticons($Text) {
		$Text = ' '.$Text.' ';
//		$Emoticons = EmotifyPlugin::GetEmoticons();
		foreach ($Emoticons as $Key => $Replacement) {
			if (strpos($Text, $Key) !== FALSE)
				$Text = preg_replace(
					"#(?<=[>\s])".preg_quote($Key, '#')."(?=\W)#m",
					'<span class="Emoticon Emoticon' . $Replacement . '"><span>' . $Key . '</span></span>',
					$Text
				);
		}

		return substr($Text, 1, -1);
	}
   */
   
   public function translateEmojiAliasesToHtml($Text) {
		$Text = ' '.$Text.' ';
		
      $emojiAliasList = $this->getEmojiAliasList();
      
		foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
         
         $emojiFilePath  = $this->getEmojiCanonicalList($emojiCanonical);
         $emojiDimension = $this->emojiDimension;

                    // echo $emojiAlias . '<br>';

         
         //echo $emojiAlias . '<br />';
			if (strpos($Text, stripslashes($emojiAlias)) !== false) {
            
            // echo $emojiAlias . '<br>';
            
				$Text = preg_replace(
					"/(?<=[\>\s])". preg_quote($emojiAlias) ."(?=[\s\<\W])/m",
					'<img class="post-emoji" src="'. $emojiFilePath .'" title="'. $emojiAlias .'" alt="'. $emojiCanonical .'" width="'. $emojiDimension .'" />',
					$Text
				);
         }
		}

		return substr($Text, 1, -1);
	}

   
   
   /// Event Handlers ///
   
   public function AssetModel_StyleCss_Handler($Sender) {   
      $Sender->AddCssFile('editor.css', 'plugins/editor');
   }
   
   /**
	 * Replace emoticons in comments.
	 */
	public function Base_AfterCommentFormat_Handler($Sender) {
      
		/*if (!C('Garden.Emoji', TRUE))
			return;
*/
      
		$Object = $Sender->EventArguments['Object'];
		$Object->FormatBody = $this->translateEmojiAliasesToHtml($Object->FormatBody);
		$Sender->EventArguments['Object'] = $Object;
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
      
      if (in_array($this->Format, $this->Formats)) {    

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
          * 
          * This is the part of code that will generate all the HTML for the 
          * editor toolbar, including all the pertinent dropdowns associated 
          * with each. For the sake of ease, most dropdowns are built from 
          * protected methods below, which can be extended.
          */

         if ($this->Format != 'Text' && !isset($c->Data['_Toolbar'])) {
            
            /**
             * Build color dropdown from array
             */
            $toolbarDropdownFontColor = array();
            $fontColorList            = $this->getFontColorList();
            foreach ($fontColorList as $fontColor) {
               $editorDataAttr             = '{"action":"color","value":"'. $fontColor .'"}';
               // Use inline style so that list can be modified without having 
               // to touch external CSS files. Nevertheless, color class has 
               // been added in case users want granular customizations. 
               // However, the post-color-* class will still need to be defined 
               // when posting these color changes. 
               $editorStyleInline          = 'background-color: ' . $fontColor;
               $toolbarDropdownFontColor[] = array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'attr' => array('class' => 'color color-'. $fontColor .' editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => $fontColor, 'title' => $fontColor, 'data-editor' => $editorDataAttr, 'style' => $editorStyleInline));
            }
            
            /**
             * Build emoji dropdown from array
             * 
             * TODO consider using CSS background images instead of img tag, 
             * as CSS images are not loaded until actually displayed on page. 
             * display:none prevents browsers from loading the resources.
             */
            $toolbarDropdownEmoji = array();
            $emojiAliasList       = $this->getEmojiAliasList();
            foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
               $emojiFilePath          = $this->getEmojiCanonicalList($emojiCanonical);
               $editorDataAttr         = '{"action":"emoji","value":"'. addslashes($emojiAlias) .'"}';
               $emojiDimension         = $this->emojiDimension;
               $toolbarDropdownEmoji[] = array('edit' => 'media', 'action'=> 'emoji', 'type' => 'button', 'attr' => array('class' => 'editor-action emoji emoji-'. $emojiCanonical. ' editor-dialog-fire-close', 'data-wysihtml5-command' => 'insertHTML', 'data-wysihtml5-command-value' => $emojiAlias, 'title' => $emojiAlias, 'src' => $emojiFilePath, 'width' => $emojiDimension, 'alt' => $emojiCanonical, 'data-editor' => $editorDataAttr));
            }
            
            /**
             * TODO Add loop array for whole toolbar to easily enable/disable 
             * different actions in the editor toolbar.
             */
            
            /**
             * Combine all pieces of toolbar build, then pass to view.
             */
            $toolbar = array(
               // Basic editing (bold, italic, strike, headers+, colors+)
               array('edit' => 'basic', 'action'=> 'bold', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-bold', 'data-wysihtml5-command' => 'bold', 'title' => 'Bold', 'data-editor' => '{"action":"bold","value":""}')),
               array('edit' => 'basic', 'action'=> 'italic', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-italic', 'data-wysihtml5-command' => 'italic', 'title' => 'Italic', 'data-editor' => '{"action":"italic","value":""}')),
               array('edit' => 'basic', 'action'=> 'strike', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-strikethrough', 'data-wysihtml5-command' => 'strikethrough', 'title' => 'Strike', 'data-editor' => '{"action":"strike","value":""}')),
               
               // Font color dropdown
               //array('edit' => 'basic', 'action'=> 'color', 'type' => $toolbarDropdownFontColor, 'attr' => array('class' => 'icon icon-font hidden-xs', 'data-wysihtml5-command-group' => 'foreColor', 'title' => 'Color', 'data-editor' => '{"action":"color","value":""}')),
               
               array('edit' => 'format', 'action'=> 'orderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ol hidden-xs', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => 'Ordered list', 'data-editor' => '{"action":"orderedlist","value":""}')),
               array('edit' => 'format', 'action'=> 'unorderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ul hidden-xs', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => 'Unordered list', 'data-editor' => '{"action":"unorderedlist","value":""}')),

               // Unique / heading editing (headings, quotation, code, spoilers)
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-headers hidden-xs')),              
               array('edit' => 'headers', 'action'=> 'headers', 'type' => array(
                   array('edit' => 'headers', 'action'=> 'heading1', 'type' => 'button', 'text' => 'Heading 1', 'attr' => array('class' => 'editor-action editor-action-h1 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h1', 'title' => 'Heading 1', 'data-editor' => '{"action":"heading1","value":""}')),
                   array('edit' => 'headers', 'action'=> 'heading2', 'type' => 'button', 'text' => 'Heading 2', 'attr' => array('class' => 'editor-action editor-action-h2 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h2', 'title' => 'Heading 2', 'data-editor' => '{"action":"heading2","value":""}')),
                   array('edit' => 'headers', 'action'=> 'quote', 'type' => 'button',    'text' => 'Quote', 'attr' => array('class' => 'editor-action editor-action-quote editor-dialog-fire-close', 'data-wysihtml5-command' => 'blockquote', 'title' => 'Quote', 'data-editor' => '{"action":"quote","value":""}')),
                   array('edit' => 'headers', 'action'=> 'code', 'type' => 'button',     'text' => 'Code', 'attr' => array('class' => 'editor-action editor-action-code editor-dialog-fire-close', 'data-wysihtml5-command' => 'code', 'title' => 'Code', 'data-editor' => '{"action":"code","value":""}')),
                   array('edit' => 'headers', 'action'=> 'spoiler', 'type' => 'button', 'text' => 'Spoiler', 'attr' => array('class' => 'editor-action editor-action-spoiler editor-dialog-fire-close', 'data-wysihtml5-command' => 'spoiler', 'title' => 'Spoiler', 'data-editor' => '{"action":"spoiler","value":""}')),
               ), 'attr' => array('class' => 'icon icon-edit', 'title' => 'Headers', 'data-editor' => '{"action":"headers","value":""}')),
                
               // Media editing (links, images)
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-media hidden-xs')),
               // Emoji dropdown
               array('edit' => 'media', 'action'=> 'emoji', 'type' => $toolbarDropdownEmoji, 'attr' => array('class' => 'editor-action icon icon-smile', 'data-wysihtml5-command' => '', 'title' => 'Emoji', 'data-editor' => '{"action":"emoji","value":""}')), 
               array('edit' => 'media', 'action'=> 'link', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-link', 'data-wysihtml5-command' => 'createLink', 'title' => 'Url', 'data-editor' => '{"action":"url","value":""}')), 
               array('edit' => 'media', 'action'=> 'image', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-picture', 'data-wysihtml5-command' => 'insertImage', 'title' => 'Image', 'data-editor' => '{"action":"image","value":""}')), 

               // Format editing (justify, list)             
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-format hidden-xs')),
               array('edit' => 'format', 'action'=> 'alignleft', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-left hidden-xs', 'data-wysihtml5-command' => 'justifyLeft', 'title' => 'Align left', 'data-editor' => '{"action":"alignleft","value":""}')),
               array('edit' => 'format', 'action'=> 'aligncenter', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-center hidden-xs', 'data-wysihtml5-command' => 'justifyCenter', 'title' => 'Align center', 'data-editor' => '{"action":"aligncenter","value":""}')),
               array('edit' => 'format', 'action'=> 'alignright', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-right hidden-xs', 'data-wysihtml5-command' => 'justifyRight', 'title' => 'Align right', 'data-editor' => '{"action":"alignright","value":""}')),
   
               // Editor switches (toggle source, fullpage)
               array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-switches hidden-xs')),
               array('edit' => 'switches', 'action'=> 'togglehtml', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-source editor-toggle-source hidden-xs', 'data-wysihtml5-action' => 'change_view', 'title' => 'Toggle HTML view', 'data-editor' => '{"action":"togglehtml","value":""}')),
               array('edit' => 'switches', 'action'=> 'fullpage', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-resize-full editor-toggle-fullpage-button', 'title' => 'Toggle full page', 'data-editor' => '{"action":"fullpage","value":""}')),
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
	 * Replace emoticons in comment preview.
	 */
	public function PostController_AfterCommentPreviewFormat_Handler($Sender) {
		/*if (!C('Garden.Emoji', TRUE))
			return;
		
		$Sender->Comment->Body = $this->DoEmoticons($Sender->Comment->Body);
       
      */
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