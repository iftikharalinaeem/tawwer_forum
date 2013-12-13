<?php if(!defined('APPLICATION')) die();

$PluginInfo['editor'] = array(
   'Name' => 'Advanced Editor',
   'Description' => 'Enables advanced editing of posts in several formats, including WYSIWYG, simple HTML, Markdown, and BBCode.',
   'Version' => '1.0.49',
   'Author' => "Dane MacMillan",
   'AuthorEmail' => 'dane@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/dane',
   'RequiredApplications' => array('Vanilla' => '>=2.2'),
   'RequiredTheme' => false,
   'RequiredPlugins' => false,
   'HasLocale' => false,
   'RegisterPermissions' => false,
   'SettingsUrl' => '/settings/editor',
   'SettingsPermission' => 'Garden.Setttings.Manage'
);

class EditorPlugin extends Gdn_Plugin {

   /**
    *
    * Properties
    *
    */

   /**
    *
    * @var array Give class access to PluginInfo
    */
   protected $pluginInfo = array();

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
    */
   public $ForceWysiwyg = 0;

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
      $this->pluginInfo = Gdn::PluginManager()->GetPluginInfo('editor', Gdn_PluginManager::ACCESS_PLUGINNAME);
      $this->ForceWysiwyg = C('Plugins.editor.ForceWysiwyg', false);
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
          'fullpage' => true,
          'lights' => true
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
         $toolbarDropdownFontColor[] = array('edit' => 'basic', 'action'=> 'color', 'type' => 'button', 'html_tag' => 'span', 'attr' => array('class' => 'color color-'. $fontColor .' editor-dialog-fire-close', 'data-wysihtml5-command' => 'foreColor', 'data-wysihtml5-command-value' => $fontColor, 'title' => $fontColor, 'data-editor' => $editorDataAttr, 'style' => $editorStyleInline));
      }

      /**
       * Build emoji dropdown from array
       *
       * Using CSS background images instead of img tag, because CSS images
       * do not download until actually displayed on page. display:none
       * prevents browsers from loading the resources.
       */
      $toolbarDropdownEmoji = array();
      $emoji = Emoji::instance();
      $emojiAliasList       = $emoji->getEmojiEditorList();
      foreach ($emojiAliasList as $emojiAlias => $emojiCanonical) {
         $emojiFilePath          = $emoji->getEmoji($emojiCanonical);
         //$editorDataAttr         = '{"action":"emoji","value":"'. htmlentities($emojiAlias) .'"}';
         $editorDataAttr         = '{"action":"emoji","value":"'. addslashes($emojiAlias) .'"}';
         //$emojiStyle           = 'background-image: url('. $emojiFilePath .'); background-size: '. $emojiDimension .'px; width: '.$emojiDimension .'px; height:'. $emojiDimension .'px;';

         // In case user creates an alias that does not match a canonical
         // emoji, let them know.
         $emojiTitle             = (strpos($emojiFilePath, 'grey_question') === false)
                                      ? $emojiAlias
                                      : "Alias '$emojiCanonical' not found in canonical list.";

         $toolbarDropdownEmoji[] = array(
            'edit' => 'media',
            'action'=> 'emoji',
            'type' => 'button',
            'html_tag' => 'span',
            'text' => $emoji->img($emojiFilePath, $emojiAlias, $editorDataAttr),
            'attr' => array(
               'class' => 'editor-action emoji-'. $emojiCanonical. ' editor-dialog-fire-close',
               'data-wysihtml5-command' => 'insertHTML',
               'data-wysihtml5-command-value' => ' '.$emojiAlias .' ',
               'title' => $emojiTitle,
               'data-editor' => $editorDataAttr));
      }

      /**
       * Compile whole list of editor actions into single $editorToolbarAll
       * array. Once complete, loop through allowedEditorActions and filter
       * out the actions that will not be allowed.
       */
      $editorToolbarAll['bold'] = array('edit' => 'basic', 'action'=> 'bold', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-bold editor-dialog-fire-close', 'data-wysihtml5-command' => 'bold', 'title' => 'Bold', 'data-editor' => '{"action":"bold","value":""}'));
      $editorToolbarAll['italic'] = array('edit' => 'basic', 'action'=> 'italic', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-italic editor-dialog-fire-close', 'data-wysihtml5-command' => 'italic', 'title' => 'Italic', 'data-editor' => '{"action":"italic","value":""}'));
      $editorToolbarAll['strike'] = array('edit' => 'basic', 'action'=> 'strike', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-strikethrough editor-dialog-fire-close hidden-xs', 'data-wysihtml5-command' => 'strikethrough', 'title' => 'Strike', 'data-editor' => '{"action":"strike","value":""}'));
      $editorToolbarAll['color'] = array('edit' => 'basic', 'action'=> 'color', 'type' => $toolbarDropdownFontColor, 'attr' => array('class' => 'icon icon-font editor-dd-color hidden-xs', 'data-wysihtml5-command-group' => 'foreColor', 'title' => 'Color', 'data-editor' => '{"action":"color","value":""}'));
      $editorToolbarAll['orderedlist'] = array('edit' => 'format', 'action'=> 'orderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ol editor-dialog-fire-close hidden-xs', 'data-wysihtml5-command' => 'insertOrderedList', 'title' => 'Ordered list', 'data-editor' => '{"action":"orderedlist","value":""}'));
      $editorToolbarAll['unorderedlist'] = array('edit' => 'format', 'action'=> 'unorderedlist', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-list-ul editor-dialog-fire-close hidden-xs', 'data-wysihtml5-command' => 'insertUnorderedList', 'title' => 'Unordered list', 'data-editor' => '{"action":"unorderedlist","value":""}'));

      $editorToolbarAll['sep-format'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-headers hidden-xs'));
      $editorToolbarAll['format'] = array('edit' => 'format', 'action'=> 'headers', 'type' => array(
             array('edit' => 'format', 'action'=> 'heading1', 'type' => 'button', 'text' => 'Heading 1', 'html_tag' => 'a', 'attr' => array('class' => 'editor-action editor-action-h1 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h1', 'title' => 'Heading 1', 'data-editor' => '{"action":"heading1","value":""}')),
             array('edit' => 'format', 'action'=> 'heading2', 'type' => 'button', 'text' => 'Heading 2', 'html_tag' => 'a', 'attr' => array('class' => 'editor-action editor-action-h2 editor-dialog-fire-close', 'data-wysihtml5-command' => 'formatBlock', 'data-wysihtml5-command-value' => 'h2', 'title' => 'Heading 2', 'data-editor' => '{"action":"heading2","value":""}')),
             array('edit' => 'format', 'action'=> 'quote', 'type' => 'button',    'text' => 'Quote', 'html_tag' => 'a', 'attr' => array('class' => 'editor-action editor-action-quote editor-dialog-fire-close', 'data-wysihtml5-command' => 'blockquote', 'title' => 'Quote', 'data-editor' => '{"action":"quote","value":""}')),
             array('edit' => 'format', 'action'=> 'code', 'type' => 'button',     'text' => 'Code', 'html_tag' => 'a', 'attr' => array('class' => 'editor-action editor-action-code editor-dialog-fire-close', 'data-wysihtml5-command' => 'code', 'title' => 'Code', 'data-editor' => '{"action":"code","value":""}')),
             array('edit' => 'format', 'action'=> 'spoiler', 'type' => 'button', 'text' => 'Spoiler', 'html_tag' => 'a', 'attr' => array('class' => 'editor-action editor-action-spoiler editor-dialog-fire-close', 'data-wysihtml5-command' => 'spoiler', 'title' => 'Spoiler', 'data-editor' => '{"action":"spoiler","value":""}')),
         ), 'attr' => array('class' => 'editor-action icon icon-paragraph editor-dd-format', 'title' => 'Format', 'data-editor' => '{"action":"format","value":""}'));

      $editorToolbarAll['sep-media'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-media hidden-xs'));
      $editorToolbarAll['emoji'] = array('edit' => 'media', 'action'=> 'emoji', 'type' => $toolbarDropdownEmoji, 'attr' => array('class' => 'editor-action icon icon-smile editor-dd-emoji', 'data-wysihtml5-command' => '', 'title' => 'Emoji', 'data-editor' => '{"action":"emoji","value":""}'));
      $editorToolbarAll['links'] = array('edit' => 'media', 'action'=> 'link', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-link editor-dd-link', 'data-wysihtml5-command' => 'createLink', 'title' => 'Url', 'data-editor' => '{"action":"url","value":""}'));
      $editorToolbarAll['images'] = array('edit' => 'media', 'action'=> 'image', 'type' => array(), 'attr' => array('class' => 'editor-action icon icon-picture editor-dd-image', 'data-wysihtml5-command' => 'insertImage', 'title' => 'Image', 'data-editor' => '{"action":"image","value":""}'));

      $editorToolbarAll['sep-align'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-align hidden-xs'));
      $editorToolbarAll['alignleft'] = array('edit' => 'format', 'action'=> 'alignleft', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-left editor-dialog-fire-close hidden-xs', 'data-wysihtml5-command' => 'justifyLeft', 'title' => 'Align left', 'data-editor' => '{"action":"alignleft","value":""}'));
      $editorToolbarAll['aligncenter'] = array('edit' => 'format', 'action'=> 'aligncenter', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-center editor-dialog-fire-close hidden-xs', 'data-wysihtml5-command' => 'justifyCenter', 'title' => 'Align center', 'data-editor' => '{"action":"aligncenter","value":""}'));
      $editorToolbarAll['alignright'] = array('edit' => 'format', 'action'=> 'alignright', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-align-right editor-dialog-fire-close hidden-xs', 'data-wysihtml5-command' => 'justifyRight', 'title' => 'Align right', 'data-editor' => '{"action":"alignright","value":""}'));

      $editorToolbarAll['sep-switches'] = array('type' => 'separator', 'attr' => array('class' => 'editor-sep sep-switches hidden-xs'));
      $editorToolbarAll['togglehtml'] = array('edit' => 'switches', 'action'=> 'togglehtml', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-source editor-toggle-source editor-dialog-fire-close hidden-xs', 'data-wysihtml5-action' => 'change_view', 'title' => 'Toggle HTML view', 'data-editor' => '{"action":"togglehtml","value":""}'));
      $editorToolbarAll['fullpage'] = array('edit' => 'switches', 'action'=> 'fullpage', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-resize-full editor-toggle-fullpage-button editor-dialog-fire-close', 'title' => 'Toggle full page', 'data-editor' => '{"action":"fullpage","value":""}'));
      $editorToolbarAll['lights'] = array('edit' => 'switches', 'action'=> 'lights', 'type' => 'button', 'attr' => array('class' => 'editor-action icon icon-adjust editor-toggle-lights-button editor-dialog-fire-close hidden-xs', 'title' => 'Toggle lights', 'data-editor' => '{"action":"lights","value":""}'));

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
      $Sender->AddCssFile('vanillicon.css', 'static');
      $Sender->AddCssFile('editor.css', 'plugins/editor');
   }

   /**
	 * Replace emoticons in comment preview.
	 */
	public function PostController_AfterCommentPreviewFormat_Handler($Sender) {
		if (Emoji::instance()->enabled) {
         $Sender->Comment->Body = Emoji::instance()->translateToHtml($Sender->Comment->Body);
      }
	}

   /**
	 * Replace emoticons in comments.
	 */
	public function Base_AfterCommentFormat_Handler($Sender) {
		if (Emoji::instance()->enabled) {
         $Object = $Sender->EventArguments['Object'];
         $Object->FormatBody = Emoji::instance()->translateToHtml($Object->FormatBody);
         $Sender->EventArguments['Object'] = $Object;
      }
	}


   /**
    * Placed these components everywhere due to some Web sites loading the
    * editor in some areas where the values were not yet injected into HTML.
    */
   public function Base_Render_Before(&$Sender) {
      $c = Gdn::Controller();

      // If user wants to modify styling of Wysiwyg content in editor,
      // they can override the styles with this file.
      $CssInfo = AssetModel::CssPath('wysiwyg.css', 'plugins/editor');
      if ($CssInfo) {
        $CssPath = Asset($CssInfo[1]);
      }

      // Load JavaScript
      $c->AddJsFile('editor.js', 'plugins/editor');

      // Set definitions for JavaScript to read
      $c->AddDefinition('editorVersion',      $this->pluginInfo['Version']);
      $c->AddDefinition('editorInputFormat',  $this->Format);
      $c->AddDefinition('editorPluginAssets', $this->AssetPath);
      $c->AddDefinition('wysiwygHelpText',    T('editor.WysiwygHelpText', 'You are using <a href="https://en.wikipedia.org/wiki/WYSIWYG" target="_new">Wysiwyg</a> in your post.'));
      $c->AddDefinition('bbcodeHelpText',     T('editor.BBCodeHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a> in your post.'));
      $c->AddDefinition('htmlHelpText',       T('editor.HtmlHelpText', 'You can use <a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a> in your post.'));
      $c->AddDefinition('markdownHelpText',   T('editor.MarkdownHelpText', 'You can use <a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a> in your post.'));
      $c->AddDefinition('textHelpText',       T('editor.TextHelpText', 'You are using plain text in your post.'));
      $c->AddDefinition('editorWysiwygCSS',   $CssPath);
   }

   /**
    * Attach editor anywhere 'BodyBox' is used. It is not being used for
    * editing a posted reply, so find another event to hook into.
    *
    * @param Gdn_Form $Sender
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender, $Args) {
      // TODO move this property to constructor
      $this->Format = $Sender->GetValue('Format');

      // Make sure we have some sort of format.
      if (!$this->Format) {
         $this->Format = C('Garden.InputFormatter','Html');
         $Sender->SetValue('Format', $this->Format);
      }

      // If force Wysiwyg enabled in settings
      if (C('Garden.InputFormatter','Wysiwyg') == 'Wysiwyg'
      //&& strcasecmp($this->Format, 'wysiwyg') != 0
      && $this->ForceWysiwyg == true) {

         $wysiwygBody = Gdn_Format::To($Sender->GetValue('Body'), $this->Format);
         $Sender->SetValue('Body', $wysiwygBody);

         $this->Format = 'Wysiwyg';
         $Sender->SetValue('Format', $this->Format);
      }

      if (in_array(strtolower($this->Format), array_map('strtolower', $this->Formats))) {
         $c = Gdn::Controller();

         // Set minor data for view
         $c->SetData('_EditorInputFormat', $this->Format);

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

         $Args['BodyBox'] .= $View;
      }
   }

   /**
    *
    * @param SettingsController $Sender
    * @param array $Args
    */
   public function SettingsController_Editor_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Cf = new ConfigurationModule($Sender);

      $Formats = array_combine($this->Formats, $this->Formats);

      $Cf->Initialize(array(
          'Garden.InputFormatter' => array('LabelCode' => 'Post Format', 'Control' => 'DropDown', 'Description' => '<p>Select the default format of the editor for posts in the community.</p> <p><small><strong>Note:</strong> the editor will auto-detect the format of old posts when emending them and load their original formatting rules. Aside from this exception, the selected post format below will take precedence.</small></p>', 'Items' => $Formats),
          'Plugins.editor.ForceWysiwyg' => array('LabelCode' => 'Reinterpret All Posts As Wysiwyg', 'Control' => 'Checkbox', 'Description' => '<p>Check the below option to tell the editor to reinterpret all old posts as Wysiwyg.</p> <p><small><strong>Note:</strong> This setting will only take affect if Wysiwyg was chosen as the Post Format above.</p>'),
          'Garden.MobileInputFormatter' => array('LabelCode' => 'Mobile Format', 'Control' => 'DropDown', 'Description' => '<p>Some mobile devices cannot interpret more advanced editing features; providing a fallback like `Text` is recommended.</p>', 'Items' => $Formats, 'DefaultValue' => C('Garden.InputFormatter'))
      ));

      // Add some JS and CSS to blur out option when Wysiwyg not chosen.
      $c = Gdn::Controller();
      $c->AddJsFile('settings.js', 'plugins/editor');
      $Sender->AddCssFile('settings.css', 'plugins/editor');

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Advanced Editor Settings'));
      $Cf->RenderAll();
      //$Sender->Cf = $Cf;
      //$Sender->Render('settings', '', 'plugins/editor');
   }

   /*
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Appearance', T('Appearance'));
      $Menu->AddLink('Appearance', 'Advanced Editor', 'settings/editor', 'Garden.Settings.Manage');
   }
   */


   /**
	 * Every time editor plugin is enabled, disable other known editors that
    * may clash with this one. If editor is loaded, then thes other
    * editors loaded after, there are CSS rules that hide them. This way,
    * the editor plugin always takes precedence.
	 */
	public function Setup() {
      $pluginEditors = array(
          'cleditor',
          'ButtonBar',
          'Emotify'
      );

      foreach ($pluginEditors as $pluginName) {
         Gdn::PluginManager()->DisablePlugin($pluginName);
      }

      SaveToConfig(array(
         'Plugins.editor.ForceWysiwyg' => false
      ));
	}

   public function OnDisable() {
		//RemoveFromConfig('Plugin.editor.DefaultView');
	}

   public function CleanUp() {
		//RemoveFromConfig('Plugin.editor.DefaultView');
	}
}
