<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['CustomTheme'] = array(
   'Name' => 'Custom Theme',
   'Description' => 'Allows administrators to customize the HTML & CSS on their forums.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class CustomThemePlugin implements Gdn_IPlugin {
   
   public function Base_Render_Before(&$Sender) {
		// If we are using the default master view, and in preview mode, add the custom css file
		if (Gdn::Session()->GetPreference('PreviewCustomTheme')) {
			$Sender->AddAsset('Content', $Sender->FetchView(PATH_PLUGINS . DS . 'CustomTheme' . DS . 'views' . DS . 'preview.php'));
			$Sender->AddCssFile('previewtheme.css', 'dashboard');
		}
	}
	
	public function SettingsController_AfterCurrentTheme_Handler($Sender) {
		/*
		$ThemeManager = new Gdn_ThemeManager();
		$ThemeInfo = $ThemeManager->EnabledThemeInfo();
		if (GetValue('IsCustom', $ThemeInfo)) {
		*/
			echo Wrap(Anchor('Customize', 'settings/customtheme', 'SmallButton'), 'div', array('style' => 'padding: 10px 0 0;'));
		// }
	}

   public function Base_BeforeAddCss_Handler($Sender) {
		// If we are using the default master view, and in preview mode, use custom css & html files
		$DoPreview = Gdn::Session()->GetPreference('PreviewCustomTheme', FALSE);
		$CSSFile = C('Plugins.CustomTheme.PreviewCSS', '');
		if ($CSSFile != '' && $DoPreview && ($Sender->MasterView == 'default' || $Sender->MasterView == '')) {
			$CssFiles = GetValue('CssFiles', $Sender->EventArguments);
			// If there is custom css, and we are not supposed to include theme-based css files...
			if (C('Plugins.CustomTheme.IncludeThemeCSS', 'Yes') == 'No') {
				foreach ($CssFiles as $k => $v) {
					if (GetValue('FileName', $v) != 'previewtheme.css') {
						unset($CssFiles[$k]);
						array_merge($CssFiles);
					}
				}
			} else {
				foreach ($CssFiles as $k => $v) {
					if (GetValue('FileName', $v) == 'custom.css') {
						unset($CssFiles[$k]);
						array_merge($CssFiles);
					}
				}
			}
			$CssFiles[] = array('FileName' => $CSSFile, 'AppFolder' => 'turds');
			$Sender->EventArguments['CssFiles'] = $CssFiles;
		}
	}
	
   public function Base_BeforeFetchMaster_Handler(&$Sender) {
		// If we are using the default master view, and in preview mode, use custom css & html files
		$DoPreview = Gdn::Session()->GetPreference('PreviewCustomTheme', FALSE);
		$HtmlFile = C('Plugins.CustomTheme.PreviewHtml', '');
		if ($HtmlFile != '' && $DoPreview && ($Sender->MasterView == 'default' || $Sender->MasterView == '')) {
			$MasterViewPath = GetValue('MasterViewPath', $Sender->EventArguments, '');
			$Sender->EventArguments['MasterViewPath'] = PATH_THEMES . DS . C('Garden.Theme') . DS . 'views' . DS . $HtmlFile;
		}
	}
	
   public function SettingsController_CustomThemeUpgrade_Create(&$Sender, $EventArguments) {
		$Sender->Render(PATH_PLUGINS . DS . 'CustomTheme' . DS . 'views' . DS . 'upgrade.php');
	}
	
   public function SettingsController_CustomTheme_Create(&$Sender, $EventArguments) {
		require_once('kses.php');
		$Session = Gdn::Session();
		$UserModel = Gdn::UserModel();
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Custom Html');
      $Sender->AddSideMenu('settings/customtheme');
      $Sender->Form = new Gdn_Form();
		if ($Sender->Form->GetFormValue('ExitPreview') ? TRUE : FALSE) {
			$UserModel->SavePreference($Session->UserID, 'PreviewCustomTheme', FALSE);
			Redirect('/settings/customtheme');
		}		
		
		$Sender->CurrentTab = $Sender->Form->GetFormValue('CurrentTab', 'Html');
		$Sender->AddJsFile('/js/library/jquery.autogrow.js');
		$Sender->AddJsFile('/plugins/CustomTheme/customtheme.js');
		$Sender->AddCssFile('/plugins/CustomTheme/customtheme.css');
		
		$ThemeManager = new Gdn_ThemeManager();
		$Sender->CurrentThemeInfo = $ThemeManager->EnabledThemeInfo();
		$CurrentThemeFolder = GetValue('Folder', $Sender->CurrentThemeInfo);
		$IsCustomTheme = GetValue('IsCustom', $Sender->CurrentThemeInfo);
		$Folder = PATH_THEMES . DS . $CurrentThemeFolder;
		
		$PreviewCSSFile = C('Plugins.CustomTheme.PreviewCSS', 'custom_0.css');
		$PreviewHtmlFile = C('Plugins.CustomTheme.PreviewHtml', 'custom_0.tpl');
		
		// Are we switching back to a previous revision (css OR html)?
		if (!$Sender->Form->AuthenticatedPostBack()) {
			$LoadFile = GetValue(0, $Sender->RequestArgs);
			if ($LoadFile !== FALSE && strpos($LoadFile, 'custom_') !== FALSE) {
				if (file_exists($Folder . DS . 'design' . DS . $LoadFile)) {
					$Sender->CurrentTab = 'Css';
					$PreviewCSSFile = $LoadFile;
				}
				
				if (file_exists($Folder . DS . 'views' . DS . $LoadFile)) {
					$Sender->CurrentTab = 'Html';
					$PreviewHtmlFile = $LoadFile;
				}
			}
		}
			
		if (!file_exists($Folder . DS . 'design' . DS . $PreviewCSSFile))
			$PreviewCSSFile = 'custom_0.css';

		if (!file_exists($Folder . DS . 'views' . DS . $PreviewHtmlFile))
			$PreviewHtmlFile = 'custom_0.tpl';
			
		$CurrentCSSRevision = str_replace(array('custom_', '.css'), array('', ''), $PreviewCSSFile);
		$CurrentCSSRevision = is_numeric($CurrentCSSRevision) ? $CurrentCSSRevision : 1;
		$CurrentHtmlRevision = str_replace(array('custom_', '.tpl'), array('', ''), $PreviewHtmlFile);
		$CurrentHtmlRevision = is_numeric($CurrentHtmlRevision) ? $CurrentHtmlRevision : 1;
		$CSSContents = $PreviewCSSFile == 'custom_0.css' ? '/* ---- Edit CSS ----

If you are unfamiliar with CSS, there are some learning resources in the help
section on the right-hand side of the page.

Here are some things you should know before you begin:

1. Any definitions you enter here will be related to your custom theme. If you
   change to a different theme, these definitions will no longer be visible on
   this page. If you switch back to your custom theme, your changes will be
   present.
	
2. By default, your custom css definitions will be included *after* the theme
   css files. So, your definitions will take precedence over the theme ones.

3. You can choose to not include the theme css files at all, and instead only
   include your custom css definitions with the "Revision Options" to the
   top-right.
	
4. We strip comments, invalid code, expressions, @imports, and unsafe or
   potentially malicious code from your css before saving it.
	
5. When you preview your changes, you are the only one who can see the preview.

*/' : file_get_contents($Folder . DS . 'design' . DS . $PreviewCSSFile);
		$HtmlContents = '';
		if (file_exists($Folder . DS . 'views' . DS . $PreviewHtmlFile))
			$HtmlContents = file_get_contents($Folder . DS . 'views' . DS . $PreviewHtmlFile);
		else if (file_exists($Folder . DS . 'views' . DS . 'default.master.tpl'))
			$HtmlContents = file_get_contents ($Folder . DS . 'views' . DS . 'default.master.tpl');
			
		// If viewing the form for the first time
		if (!$Sender->Form->AuthenticatedPostBack()) {
			// Make this a "copy" of the current non-custom theme
			$ThemeName = GetValue('Name', $Sender->CurrentThemeInfo, $CurrentThemeFolder);
			if (!$IsCustomTheme)
				$ThemeName .= ' (custom)';

			$Sender->Form->SetFormValue('ThemeName', $ThemeName);
			$Sender->Form->SetFormValue('CustomCSS', $CSSContents);
			$Sender->Form->SetFormValue('CustomHtml', $HtmlContents);
			$Sender->Form->SetFormValue('IncludeThemeCSS', C('Plugins.CustomTheme.IncludeThemeCSS'));
		} else {
			// If saving the form
			$IsApply = $Sender->Form->GetFormValue('Apply') ? TRUE : FALSE;
			$IsPreview = $Sender->Form->GetFormValue('Preview') ? TRUE : FALSE;
			$IncludeThemeCSS = $Sender->Form->GetFormValue('IncludeThemeCSS');
			$IsApplyPreview = $Sender->Form->GetFormValue('ApplyChanges') ? TRUE : FALSE;
			// If applying the changes from a preview
			if ($IsApplyPreview) {
				$UserModel->SavePreference($Session->UserID, 'PreviewCustomTheme', FALSE);
				$ThemeName = GetValue('Name', $Sender->CurrentThemeInfo, $CurrentThemeFolder);
				$Sender->Form->SetFormValue('ThemeName', $ThemeName);
				$Sender->Form->SetFormValue('CustomCSS', $CSSContents);
				$Sender->Form->SetFormValue('CustomHtml', $HtmlContents);
				$Sender->Form->SetFormValue('IncludeThemeCSS', C('Plugins.CustomTheme.IncludeThemeCSS'));
			}
			
			// If this is not a custom theme, create the custom theme folder from the currently selected theme, and enable it.
			if (!$IsCustomTheme) {
				$i = 2;
				$DestFolder = $TmpName = $CurrentThemeFolder.'_custom';
				while(file_exists(PATH_THEMES . DS . $DestFolder)) {
					$DestFolder = $TmpName . $i;
					++$i;
				}
				$CurrentThemeFolder = $DestFolder;
				$DestFolder = PATH_THEMES . DS . $DestFolder;
				recurse_copy($Folder, $DestFolder);
				$Folder = $DestFolder;
				SaveToConfig('Garden.Theme', $CurrentThemeFolder);
			}
				
			// Create the revision files:
			// 1. CSS
			$NewCSS = $this->_CleanCSS($Sender->Form->GetFormValue('CustomCSS', ''));
			if ($CSSContents != $NewCSS) {
				$FileName = 'custom_'.($CurrentCSSRevision + 1).'.css';

				file_put_contents($Folder . DS . 'design' . DS . $FileName, $NewCSS);
				SaveToConfig('Plugins.CustomTheme.PreviewCSS', $FileName);
			}
			
			// 2. Html
			$NewHtml = $Sender->Form->GetFormValue('CustomHtml', '');
			if ($HtmlContents != $NewHtml) {
				$FileName = 'custom_'.($CurrentHtmlRevision + 1).'.tpl';

				file_put_contents($Folder . DS . 'views' . DS . $FileName, $NewHtml);
				SaveToConfig('Plugins.CustomTheme.PreviewHtml', $FileName);
			}
			
			// Save the setting to include theme's style.css or not
			if (in_array($IncludeThemeCSS, array('Yes', 'No'))) 
				SaveToConfig('Plugins.CustomTheme.IncludeThemeCSS', $IncludeThemeCSS);
			
			// If we are applying the changes, copy the current revs over the custom.css & default.master.tpl			
			if ($IsApply || $IsApplyPreview) {
				file_put_contents($Folder . DS . 'design'. DS . 'custom.css', $NewCSS);
				file_put_contents($Folder . DS . 'views'. DS . 'default.master.tpl', $NewHtml);
				SaveToConfig('Plugins.CustomTheme.EnabledCSS', $PreviewCSSFile);
				SaveToConfig('Plugins.CustomTheme.EnabledHtml', $PreviewHtmlFile);
			}
			
			// Save the about file (to get any theme name changes implemented)
			save_about_file($CurrentThemeFolder, $Sender->Form->GetFormValue('ThemeName'));

			// Only keep the last 20 revs
			clean_revisions(PATH_THEMES . DS . $CurrentThemeFolder, 'css');
			clean_revisions(PATH_THEMES . DS . $CurrentThemeFolder, 'html');

			// TODO: HANDLE PREVIEWS
			if ($IsPreview) {
				// Not sure how to handle previews yet...
				$UserModel->SavePreference($Session->UserID, 'PreviewCustomTheme', TRUE);
				Redirect('/');
			}

			$Sender->StatusMessage = "Your changes have been applied.";
		}
      $Sender->Render(PATH_PLUGINS . DS . 'CustomTheme' . DS . 'views' . DS . 'customtheme.php');
   }
	
   public function Setup() {
      // No setup required.
   }
	
	private function _CleanCSS($FormCSS) {
		// Clean the css
		$NewCSS = $FormCSS;
		safecss_class();
		$csstidy = new csstidy();
		$csstidy->optimise = new safecss($csstidy);
		$csstidy->set_cfg('remove_bslash', FALSE);
		$csstidy->set_cfg('compress_colors', FALSE);
		$csstidy->set_cfg('compress_font-weight', FALSE);
		$csstidy->set_cfg('discard_invalid_properties', TRUE);
		$csstidy->set_cfg('merge_selectors', TRUE);

		$NewCSS = stripslashes($NewCSS);
		$NewCSS = preg_replace('/\\\\([0-9a-fA-F]{4})/', '\\\\\\\\$1', $Prev = $NewCSS);

		// if ($NewCSS != $Prev) $warnings[] = 'preg_replace found stuff';

		// Some people put weird stuff in their CSS, KSES tends to be greedy
		$NewCSS = str_replace('<=', '&lt;=', $NewCSS);
		// Why KSES instead of strip_tags?  Who knows?
		$NewCSS = wp_kses_split($Prev = $NewCSS, array(), array());
		$NewCSS = str_replace( '&gt;', '>', $NewCSS); // kses replaces lone '>' with &gt;
		// Why both KSES and strip_tags?  Because we just added some '>'.
		$NewCSS = strip_tags($NewCSS);

		// if ($NewCSS != $Prev) $warnings[] = 'kses found stuff';

		$csstidy->parse($NewCSS);
		return $csstidy->print->plain();
	}

}

function safecss_class() {
	// Wrapped so we don't need the parent class just to load the plugin
	if (class_exists('safecss'))
		return;

	require_once('csstidy/class.csstidy.php');
	class safecss extends csstidy_optimise {
		var $tales = array();
		var $props_w_urls = array('background', 'background-image', 'list-style', 'list-style-image');
		var $allowed_protocols = array('http');

		function __construct(&$css) {
			return $this->csstidy_optimise($css);
		}

		function postparse() {
			if ( !empty($this->parser->import) ) {
				$this->tattle("Import attempt:\n".print_r($this->parser->import,1));
				$this->parser->import = array();
			}
			if ( !empty($this->parser->charset) ) {
				$this->tattle("Charset attempt:\n".print_r($this->parser->charset,1));
				$this->parser->charset = array();
			}
			return parent::postparse();
		}

		function subvalue() {
			$this->sub_value = trim($this->sub_value);

			// Send any urls through our filter
			if ( preg_match('!^\\s*url\\s*(?:\\(|\\\\0028)(.*)(?:\\)|\\\\0029).*$!Dis', $this->sub_value, $matches) )
				$this->sub_value = $this->clean_url($matches[1]);

			// Strip any expressions
			if ( preg_match('!^\\s*expression!Dis', $this->sub_value) ) {
				$this->tattle("Expression attempt: $this->sub_value");
				$this->sub_value = '';
			}

			return parent::subvalue();
		}

		function clean_url($url) {
			// Clean up the string
			$url = trim($url, "' \" \r \n");

			// Check against whitelist for properties allowed to have URL values
			if ( ! in_array($this->property, $this->props_w_urls) ) {
				$this->tattle('URL in illegal property ' . $this->property . ":\n$url");
				return '';
			}

			$url = wp_kses_bad_protocol_once($url, $this->allowed_protocols);

			if ( empty($url) ) {
				$this->tattle('URL empty');
				return '';
			}

			return "url('$url')";
		}

		function tattle($msg, $send=false) {
			if ( $msg )
				$this->tales [] = $msg;

			if ( $send && $this->tales ) {
				try {
					$SiteID = Gdn::Config('VanillaForums.SiteID', '');
					$Session = Gdn::Session();
					$E = new Gdn_Email();
					$E->Subject = '[Custom CSS]';
					$E->Message = 'SiteID: '.$SiteID."\nUserID: ".$Session->UserID."\n".implode("\n", $this->tales);
					$E->To = 'support@vanillaforums.com';
					$E->From('customcss@vanillaforums.com', 'Custom CSS Attack');
					$E->Send();
				} catch(Exception $ex) {
					// do nothing
				}
			}
		}
	}
}

function recurse_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

function clean_revisions($Folder, $Type = 'css') {
	$Extension = $Type == 'css' ? '.css' : '.tpl';
	$SubFolder = $Type == 'css' ? 'design' : 'views';
	$Suffix = $Type == 'css' ? 'CSS' : 'Html';
	$PreviewRev = str_replace(array('custom_', $Extension), '', C('Plugins.CustomTheme.Preview'.$Suffix, ''));
	$PreviewRev = is_numeric($PreviewRev) ? $PreviewRev : 0;
	$ActiveRev = str_replace(array('custom_', $Extension), '', C('Plugins.CustomTheme.Enabled'.$Suffix, ''));
	$ActiveRev = is_numeric($ActiveRev) ? $ActiveRev : 0;
	if (file_exists($Folder.DS.$SubFolder)) {
		if ($Handle = opendir($Folder.DS.$SubFolder)) {
			while (FALSE !== ($File = readdir($Handle))) {
				if (substr($File, 0, 7) == 'custom_') {
					$Rev = str_replace(array('custom_', $Extension), '', $File);
					if (is_numeric($Rev) && $Rev != $ActiveRev && $Rev < $PreviewRev - 19) {
						@unlink($Folder . DS . $SubFolder . DS . 'custom_' . $Rev . $Extension);	
					}
				}
			}
		}
	}
}

function save_about_file($Folder, $Name) {
	file_put_contents(
		PATH_THEMES . DS . $Folder . DS . 'about.php',
		"<?php if (!defined('APPLICATION')) exit();
\$ThemeInfo['".str_replace("'", '', $Folder)."'] = array(
	'Name' => '".str_replace("'", '', clean_theme_name($Name))."',
	'Description' => 'A custom theme.',
   'Author' => '".Gdn::Session()->User->Name."',
	'IsCustom' => TRUE
);");
}

function clean_theme_name($Mixed) {
	$Mixed = preg_replace('/[^A-Za-z0-9 ]/', '', urldecode($Mixed));
	$Mixed = preg_replace('/ +/', ' ', trim($Mixed));
	return $Mixed;
}