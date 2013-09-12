<?php if(!defined('APPLICATION')) die();

$PluginInfo['editor'] = array(
   'Name' => 'editor',
   'Description' => 'Enables advanced editing of posts in several formats, including WYSIWYG, simple HTML, Markdown, and BBCode.',
   'Version' => '1.0.0',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => false, 
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => false,
   'SettingsPermission' => false
);

class EditorPlugin extends Gdn_Plugin {
   
   /**
    * 
    * Properties
    * 
    */
   
   /**
    *
    * @var array List of possible formats the editor supports. 
    */
   protected $Formats = array('Wysiwyg', 'Html', 'Markdown', 'BBCode', 'Text', 'TextEx');
   
   /**
    *
    * @var string Default format being used for current rendering. Can be one of 
    *             the formats listed in $Formats array above.
    */
   protected $Format;
   
   /**
    *
    * @var string Asset path for this plugin, set in Gdn_Form_BeforeBodyBox_Handler. 
    *      TODO check how to set it at runtime in constructor.
    */
   protected $AssetPath;
   
   /**
    *
    * @var int The width and height of emoji icons are set to 20px each side. 
    */
   protected $emojiDimension = 20;
   
   /**
    *
    * @var bool Setting to true will allow editor to interpret emoji aliases as 
    *           Html equivalent markup.  
    */
   protected $emojiInterpretAllow = true;
   
   /**
    *
    * @var bool Same as above, except interpret all hidden aliases as well. This 
    *           var will have no affect if the above is set to false. 
    */
   protected $emojiInterpretAllowHidden = true;
   
   
   /**
    * 
    * Methods
    * 
    */
   
   /**
    * Setup some variables for instance.
    */
   public function __construct() {
      parent::__construct();
      $this->AssetPath = Asset('/plugins/editor');  
   }
   
   /**
    * Set the editor actions to true or false to enable or disable the action 
    * from displaying in the editor toolbar. This will also let you toggle 
    * the separators from appearing between the loosely grouped actions.
    * 
    * @return array List of allowed editor actions
    */
   public function getAllowedEditorActions($name = null, $value = null) {
      static $allowedEditorActions = array(
          'bold' => true, 
          'italic' => true, 
          'strike' => true, 
          'color' => false, 
          'orderedlist' => true, 
          'unorderedlist' => true,
          
          'sep-format' => true, // separator
          'format' => true, 
          
          'sep-media' => true, // separator
          'emoji' => true, 
          'links' => true, 
          'images' => true, 
          
          'sep-align' => true, // separator
          'alignleft' => true, 
          'aligncenter' => true, 
          'alignright' => true, 
          
          'sep-switches' => true, // separator
          'togglehtml' => true, 
          'fullpage' => true 
      );
      
      if ($name !== null) {
         if (is_array($name)) {
            $allowedEditorActions = $name;
         } elseif ($value !== null) {
            $allowedEditorActions[$name] = $value;
         } else {
            return getValue($name, $allowedEditorActions, null);
         }
      } else {
         return $allowedEditorActions;
      }
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
    * Populate this with any aliases required for plugin, make sure they point 
    * to canonical translation, and plugin will add everything to dropdown that 
    * is listed. To expand, simply define more aliases that corresponded with 
    * canonical list.
    * 
    * Note: some aliases require htmlentities filtering, which is done directly 
    * before output in the dropdown, and while searching for the string to 
    * replace in the regex, NOT here. The reason for this is so the alias 
    * list does not get littered with characters entity encodings like &lt;, 
    * which makes it difficult to immediately know what the aliases do. Also, 
    * htmlentities would have to be revered in areas such as title attributes, 
    * which counteracts the usefulness of having it done here. 
    * 
    * @param string $emojiAlias Optional string to return matching translation
    * @return string|array Canonical translation or full alias array
    */
   protected function getEmojiAliasList($emojiAlias = '') {  
      $emojiAliasList = array(
         ':)'          => 'smile',
         ':D'          => 'smiley',
         ':('          => 'disappointed',
         ';)'          => 'wink',
         ':\\'         => 'confused',
         ':o'          => 'open_mouth',
         ':s'          => 'confounded',
         ':p'          => 'stuck_out_tongue',
         ":'("        => 'cry',
         ':|'          => 'neutral_face',
         'D:'          => 'anguished',
         '8)'          => 'sunglasses',
         'o:)'         => 'innocent',
         ':+1:'        => '+1',
         ':-1:'        => '-1',
         '>:)'         => 'smiling_imp', 
         ':#'          => 'grin',
         ':sleeping:'  => 'sleeping',
         '<3'          => 'heart',
         ':triumph:'   => 'triumph', 
          '(*)' => 'star'
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
    * Note: every canonical emoji name points to an array of strings. This 
    * string is ordered CurrentName, OriginalName. Due to the reset() 
    * before returning the filename, the first element in the array will be 
    * returned, so in this instance CurrentName will be returned. The second, 
    * OriginalName, does not have to be written. If ever integrating more emoji 
    * files from Apple-Color-Emoji, and wanting to rename them from numbered 
    * files, use emojirename.php located in design/images/emoji/.
    * 
    * @param type $emojiCanonical Optional string to return matching file name.
    * @return string|array File name or full canonical array
    */
   protected function getEmojiCanonicalList($emojiCanonical = '') {
      $emojiCanonicalList = array(
        // Smileys
        'relaxed'                      => array('relaxed', '50'),  
        'grinning'                     => array('grinning', '701'),  
        'grin'                         => array('grin', '702'),  
        'joy'                          => array('joy', '703'),  
        'smiley'                       => array('smiley', '704'),  
        'smile'                        => array('smile', '705'),  
        'sweat_smile'                  => array('sweat_smile', '706'),  
        'satisfied'                    => array('satisfied', '707'),  
        'innocent'                     => array('innocent', '708'),  
        'smiling_imp'                  => array('smiling_imp', '709'),  
        'wink'                         => array('wink', '710'),  
        'blush'                        => array('blush', '711'),  
        'yum'                          => array('yum', '712'),  
        'relieved'                     => array('relieved', '713'),  
        'heart_eyes'                   => array('heart_eyes', '714'),  
        'sunglasses'                   => array('sunglasses', '715'),  
        'smirk'                        => array('smirk', '716'),  
        'neutral_face'                 => array('neutral_face', '717'),  
        'expressionless'               => array('expressionless', '718'),  
        'unamused'                     => array('unamused', '719'),  
        'sweat'                        => array('sweat', '720'),  
        'pensive'                      => array('pensive', '721'),  
        'confused'                     => array('confused', '722'),  
        'confounded'                   => array('confounded', '723'),  
        'kissing'                      => array('kissing', '724'),  
        'kissing_heart'                => array('kissing_heart', '725'),  
        'kissing_smiling_eyes'         => array('kissing_smiling_eyes', '726'),  
        'kissing_closed_eyes'          => array('kissing_closed_eyes', '727'),  
        'stuck_out_tongue'             => array('stuck_out_tongue', '728'),  
        'stuck_out_tongue_winking_eye' => array('stuck_out_tongue_winking_eye', '729'),  
        'stuck_out_tongue_closed_eyes' => array('stuck_out_tongue_closed_eyes', '730'),  
        'disappointed'                 => array('disappointed', '731'),  
        'worried'                      => array('worried', '732'),  
        'angry'                        => array('angry', '733'),  
        'rage'                         => array('rage', '734'),  
        'cry'                          => array('cry', '735'),  
        'persevere'                    => array('persevere', '736'),  
        'triumph'                      => array('triumph', '737'),  
        'disapponted_relieved'         => array('disappointed_relieved', '738'),  
        'frowning'                     => array('frowning', '739'),  
        'anguished'                    => array('anguished', '740'),  
        'fearful'                      => array('fearful', '741'),  
        'weary'                        => array('weary', '742'),  
        'sleepy'                       => array('sleepy', '743'),  
        'tired_face'                   => array('tired_face', '744'),  
        'grimacing'                    => array('grimacing', '745'),  
        'sob'                          => array('sob', '746'),  
        'open_mouth'                   => array('open_mouth', '747'),  
        'hushed'                       => array('hushed', '748'),  
        'cold_sweat'                   => array('cold_sweat', '749'),  
        'scream'                       => array('scream', '750'),  
        'astonished'                   => array('astonished', '751'),  
        'flushed'                      => array('flushed', '752'),  
        'sleeping'                     => array('sleeping', '753'),  
        'dizzy_face'                   => array('dizzy_face', '754'),  
        'no_mouth'                     => array('no_mouth', '755'),  
        'mask'                         => array('mask', '756'),  

        // Love
        'heart'                        => array('heart', '109'),  
        'broken_heart'                 => array('broken_heart', '506'),  
        'kiss'                         => array('kiss', '497'),

        // Hand gestures
        '+1'                           => array('+1', '435'),  
        '-1'                           => array('-1', '436'),
        
        // Custom icons, canonical naming
        'trollface'                    => array('trollface', 'trollface')
      );
      
      // Some aliases self-referencing the canonical list. Use this syntax. 
      
      // Vanilla reactions, non-canonical referencing canonical
      $emojiCanonicalList['lol']       = &$emojiCanonicalList['smile'];
      $emojiCanonicalList['wtf']       = &$emojiCanonicalList['dizzy_face'];
      $emojiCanonicalList['agree']     = &$emojiCanonicalList['grinning'];
      $emojiCanonicalList['disagree']  = &$emojiCanonicalList['stuck_out_tongue_closed_eyes'];
      $emojiCanonicalList['awesome']   = &$emojiCanonicalList['heart'];

      $emojiFileSuffix = '.png';
      
      // Return first value from canonical array
      return (!$emojiCanonical)
         ? $emojiCanonicalList
         : $this->buildEmojiFilePath(reset($emojiCanonicalList[$emojiCanonical]) . $emojiFileSuffix);
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
    * This is in case you want to merge the alias list with the canonical list 
    * and easily loop through the entire possible set of translations to 
    * perform in, for example, the translateEmojiAliasesToHtml() method, which 
    * loops through all the visible emojis, and the hidden canonical ones. 
    * 
    * @return array Returns array of alias list and canonical list, easily 
    *               loopable. 
    */
   protected function mergeAliasAndCanonicalList() {
      return array_merge($this->getEmojiAliasList(), $this->buildHiddenAliasListFromCanonicalList());
   }
   
   /**
    * This is to easily match the array of the visible alias list that all 
    * users will be able to select from. Call the mergeAliasAndCanonicalList() 
    * method to merge this array with the alias list, which will then be easy 
    * to loop through all the possible emoji displayable in the forum. 
    * 
    * An alias is [:)]=>[smile], and canonical alias is [:smile:]=>[smile]
    * 
    * @return array Returns array that matches format of original alias list
    */
   protected function buildHiddenAliasListFromCanonicalList() {
      $caonicalListEmojiNamesCanonical = array_keys($this->getEmojiCanonicalList());
      $caonicalListEmojiNamesAliases = $caonicalListEmojiNamesCanonical;      
      array_walk($caonicalListEmojiNamesAliases, array($this, 'buildAliasFormat'));
      return array_combine($caonicalListEmojiNamesAliases, $caonicalListEmojiNamesCanonical);
   }
   
   /**
    * Callback method for buildHiddenAliasListFromCanonicalList.
    * 
    * Array passed as reference, to be used in above method, 
    * buildHiddenAliasListFromCanonicalLi, when calling array_walk withthis 
    * callback, which requires that the method as callback also specify object 
    * it belongs to. 
    * 
    * @param string $val Reference to passed array value
    * @param string $key Reference to passed array key
    */
   protected function buildAliasFormat(&$val, $key) {
      $val = ":$val:";
   }

   /**
    * Translate all emoji aliases to their corresponding Html image tags. 
    * 
    * Thanks to punbb 1.3.5 (GPL License) for function, which was largely 
    * inspired from their do_smilies function.
    * 
    * @param string $Text The actual user-submitted post
    * @return string Return the emoji-formatted post
    */
   public function translateEmojiAliasesToHtml($Text) {
		$Text = ' '. $Text .' ';
      
      // Determine if hidden emoji aliases are allowed, i.e., the emojis that 
      // are not listed in the official alias list array.
      $emojiAliasList = ($this->emojiInterpretAllowHidden)
              ? $this->mergeAliasAndCanonicalList()
              : $this->getEmojiAliasList();

      // Loop through and apply changes to all visible aliases from dropdown
		foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
         $emojiFilePath  = $this->getEmojiCanonicalList($emojiCanonical);
         $emojiDimension = $this->emojiDimension;

			if (strpos($Text, htmlentities($emojiAlias)) !== false) {
				$Text = preg_replace(
               '/(?<=[>\s])'.preg_quote(htmlentities($emojiAlias)).'(?=\W)/m',
               ' <img class="emoji" src="'. $emojiFilePath .'" title="'. $emojiAlias .'" alt=":'. $emojiCanonical .':" width="'. $emojiDimension .'" /> ',
					$Text
				);
         }
		}

		return substr($Text, 1, -1);
	}
   
   /**
    * This method will grab the permissions array from getAllowedEditorActions, 
    * build the "kitchen sink" editor toolbar, then filter out the allowed 
    * ones and return it. 
    * 
    * @param array $editorToolbar Holds the final copy of allowed editor actions
    * @param array $editorToolbarAll Holds the "kitchen sink" of editor actions
    * @return array Returns the array of allowed editor toolbar actions
    */
   protected function getEditorToolbar() {
      $editorToolbar        = array();
      $editorToolbarAll     = array();
      $allowedEditorActions = $this->getAllowedEditorActions();
      
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
       * Using CSS background images instead of img tag, because CSS images 
       * do not download until actually displayed on page. display:none 
       * prevents browsers from loading the resources.
       */
      $toolbarDropdownEmoji = array();
      $emojiAliasList       = $this->getEmojiAliasList();
      foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
         $emojiFilePath          = $this->getEmojiCanonicalList($emojiCanonical);
         //$editorDataAttr         = '{"action":"emoji","value":"'. htmlentities($emojiAlias) .'"}';
         $editorDataAttr         = '{"action":"emoji","value":"'. $emojiAlias .'"}';
         $emojiDimension         = $this->emojiDimension;
         $emojiStyle             = 'background-image: url('. $emojiFilePath .'); background-size: '. $emojiDimension .'px; width: '.$emojiDimension .'px; height:'. $emojiDimension .'px;';
         $toolbarDropdownEmoji[] = array('edit' => 'media', 'action'=> 'emoji', 'type' => 'button', 'attr' => array('class' => 'editor-action emoji emoji-'. $emojiCanonical. ' editor-dialog-fire-close', 'data-wysihtml5-command' => 'insertHTML', 'data-wysihtml5-command-value' => ' '. $emojiAlias .' ', 'title' => $emojiAlias, 'data-editor' => $editorDataAttr, 'style' => $emojiStyle));
      }      

      /**
       * Compile whole list of editor actions into single $editorToolbarAll 
       * array. Once complete, loop through allowedEditorActions and filter 
       * out the actions that will not be allowed.
       */
      $editorToolbarAll['bold'] = array('edit' => 'basic', 'action'=> 'bold', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-bold', 'data-wysihtml5-command' => 'bold', 'title' => 'Bold', 'data-editor' => '{"action":"bold","value":""}'));
      $editorToolbarAll['italic'] = array('edit' => 'basic', 'action'=> 'italic', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-italic', 'data-wysihtml5-command' => 'italic', 'title' => 'Italic', 'data-editor' => '{"action":"italic","value":""}'));
      $editorToolbarAll['strike'] = array('edit' => 'basic', 'action'=> 'strike', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-strikethrough', 'data-wysihtml5-command' => 'strikethrough', 'title' => 'Strike', 'data-editor' => '{"action":"strike","value":""}'));
      $editorToolbarAll['color'] = array('edit' => 'basic', 'action'=> 'color', 'type' => $toolbarDropdownFontColor, 'attr' => array('class' => 'icon icon-font editor-dd-color hidden-xs', 'data-wysihtml5-command-group' => 'foreColor', 'title' => 'Color', 'data-editor' => '{"action":"color","value":""}'));
      $editorToolbarAll['orderedlist'] = array('edit' => 'format', 'action'=> 'orderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ol hidden-xs', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => 'Ordered list', 'data-editor' => '{"action":"orderedlist","value":""}'));
      $editorToolbarAll['unorderedlist'] = array('edit' => 'format', 'action'=> 'unorderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ul hidden-xs', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => 'Unordered list', 'data-editor' => '{"action":"unorderedlist","value":""}'));
      
      $editorToolbarAll['sep-format'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-headers hidden-xs'));
      $editorToolbarAll['format'] = array('edit' => 'format', 'action'=> 'headers', 'type' => array(
             array('edit' => 'format', 'action'=> 'heading1', 'type' => 'button', 'text' => 'Heading 1', 'attr' => array('class' => 'editor-action editor-action-h1 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h1', 'title' => 'Heading 1', 'data-editor' => '{"action":"heading1","value":""}')),
             array('edit' => 'format', 'action'=> 'heading2', 'type' => 'button', 'text' => 'Heading 2', 'attr' => array('class' => 'editor-action editor-action-h2 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h2', 'title' => 'Heading 2', 'data-editor' => '{"action":"heading2","value":""}')),
             array('edit' => 'format', 'action'=> 'quote', 'type' => 'button',    'text' => 'Quote', 'attr' => array('class' => 'editor-action editor-action-quote editor-dialog-fire-close', 'data-wysihtml5-command' => 'blockquote', 'title' => 'Quote', 'data-editor' => '{"action":"quote","value":""}')),
             array('edit' => 'format', 'action'=> 'code', 'type' => 'button',     'text' => 'Code', 'attr' => array('class' => 'editor-action editor-action-code editor-dialog-fire-close', 'data-wysihtml5-command' => 'code', 'title' => 'Code', 'data-editor' => '{"action":"code","value":""}')),
             array('edit' => 'format', 'action'=> 'spoiler', 'type' => 'button', 'text' => 'Spoiler', 'attr' => array('class' => 'editor-action editor-action-spoiler editor-dialog-fire-close', 'data-wysihtml5-command' => 'spoiler', 'title' => 'Spoiler', 'data-editor' => '{"action":"spoiler","value":""}')),
         ), 'attr' => array('class' => 'icon icon-edit editor-dd-format', 'title' => 'Format', 'data-editor' => '{"action":"format","value":""}'));
      
      $editorToolbarAll['sep-media'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-media hidden-xs'));
      $editorToolbarAll['emoji'] = array('edit' => 'media', 'action'=> 'emoji', 'type' => $toolbarDropdownEmoji, 'attr' => array('class' => 'editor-action icon icon-smile editor-dd-emoji', 'data-wysihtml5-command' => '', 'title' => 'Emoji'));
      $editorToolbarAll['links'] = array('edit' => 'media', 'action'=> 'link', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-link editor-dd-link', 'data-wysihtml5-command' => 'createLink', 'title' => 'Url', 'data-editor' => '{"action":"url","value":""}'));
      $editorToolbarAll['images'] = array('edit' => 'media', 'action'=> 'image', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-picture editor-dd-image', 'data-wysihtml5-command' => 'insertImage', 'title' => 'Image', 'data-editor' => '{"action":"image","value":""}'));

      $editorToolbarAll['sep-align'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-align hidden-xs'));
      $editorToolbarAll['alignleft'] = array('edit' => 'format', 'action'=> 'alignleft', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-left hidden-xs', 'data-wysihtml5-command' => 'justifyLeft', 'title' => 'Align left', 'data-editor' => '{"action":"alignleft","value":""}'));
      $editorToolbarAll['aligncenter'] = array('edit' => 'format', 'action'=> 'aligncenter', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-center hidden-xs', 'data-wysihtml5-command' => 'justifyCenter', 'title' => 'Align center', 'data-editor' => '{"action":"aligncenter","value":""}'));
      $editorToolbarAll['alignright'] = array('edit' => 'format', 'action'=> 'alignright', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-right hidden-xs', 'data-wysihtml5-command' => 'justifyRight', 'title' => 'Align right', 'data-editor' => '{"action":"alignright","value":""}'));
      
      $editorToolbarAll['sep-switches'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-switches hidden-xs'));     
      $editorToolbarAll['togglehtml'] = array('edit' => 'switches', 'action'=> 'togglehtml', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-source editor-toggle-source hidden-xs', 'data-wysihtml5-action' => 'change_view', 'title' => 'Toggle HTML view', 'data-editor' => '{"action":"togglehtml","value":""}'));
      $editorToolbarAll['fullpage'] = array('edit' => 'switches', 'action'=> 'fullpage', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-resize-full editor-toggle-fullpage-button', 'title' => 'Toggle full page', 'data-editor' => '{"action":"fullpage","value":""}'));

      // Filter out disallowed editor actions 
      foreach ($allowedEditorActions as $editorAction => $allowed) {
         if ($allowed) {
            $editorToolbar[$editorAction] = $editorToolbarAll[$editorAction];
         }
      }
      
      return $editorToolbar;
   }


   /**
    * 
    * Vanilla event handlers
    * 
    */
   
   /**
    * Load CSS into head for editor
    */
   public function AssetModel_StyleCss_Handler($Sender) {   
      $Sender->AddCssFile('editor.css', 'plugins/editor');
   }
   
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
      
   /**
    * Attach editor anywhere 'BodyBox' is used. It is not being used for 
    * editing a posted reply, so find another event to hook into.
    * 
    * @param Gdn_Form $Sender 
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender) 
   {
      // TODO move this property to constructor
      $this->Format = $Sender->GetValue('Format', C('Garden.InputFormatter','Html'));
      
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
          * Get the generated editor toolbar from getEditorToolbar, and assign 
          * it data object for view.
          */
         if ($this->Format != 'Text' && !isset($c->Data['_EditorToolbar'])) {
            
            $editorToolbar = $this->getEditorToolbar();
            $this->EventArguments['EditorToolbar'] =& $editorToolbar;
            $this->FireEvent('InitEditorToolbar');
            
            // Set data for view
            $c->SetData('_EditorToolbar', $editorToolbar);
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