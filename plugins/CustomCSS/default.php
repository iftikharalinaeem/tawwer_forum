<?php if (!defined('APPLICATION')) exit();

class CustomCSSPlugin implements Gdn_IPlugin {

   /**
    * Adds "Custom CSS" menu option to the dashboard.
   public function base_GetAppSettingsMenuItems_Handler($sender) {
      $Menu = &$sender->EventArguments['SideMenu'];
		$Menu->addItem('Appearance', 'Appearance');
      $Menu->addLink('Appearance', 'Custom CSS', 'plugin/customcss', 'Garden.AdminUser.Only');
   }
    */

   public function base_render_before($sender) {
		// If we are using the default master view, and in preview mode, add the custom css file
		$session = Gdn::session();
		$preview = $session->getPreference('PreviewCustomCSS', FALSE);
		$live = Gdn::config('Plugins.CustomCSS.Enabled');
		$currentTheme = Gdn::config('Garden.Theme', 'default');
		$customFile = Gdn::config('Plugins.CustomCSS.File', '');
		$previewFile = Gdn::config('Plugins.CustomCSS.PreviewFile', '');
		if (($sender->MasterView == 'default' || $sender->MasterView == '')) {
			if (($previewFile != '' && $preview) || $live) {
				// If there is custom css, and we are not supposed to include theme-based css files...
				if (Gdn::config('Plugins.CustomCSS.IncludeTheme', 'Yes') == 'No') {
					// ... remove them
					$sender->clearCssFiles();
				}
			}

			if ($previewFile != '' && $preview)
				$sender->addCssFile('/cache/CustomCSS/'.$currentTheme.'/'.$previewFile);
			else if ($customFile != '' && $live)
				$sender->addCssFile('/cache/CustomCSS/'.$currentTheme.'/'.$customFile);
		}
		if ($preview) {
			$sender->addAsset('Content', $sender->fetchView(PATH_PLUGINS . DS . 'CustomCSS' . DS . 'views' . DS . 'preview.php'));
			$sender->addCssFile('previewtheme.css', 'dashboard');
		}
	}

   public function pluginController_customCSS_create($Sender, $EventArguments = []) {
		require_once('kses.php');
		$Session = Gdn::session();
		$UserModel = Gdn::userModel();
      $Sender->permission('Garden.AdminUser.Only');
      $Sender->title('Custom CSS');
      $Sender->addSideMenu('plugin/customcss');
      $Sender->Form = new Gdn_Form();
		$Sender->addJsFile('jquery.autogrow.js');
		$Sender->addCssFile('customcss.css', 'plugins/CustomCSS');

		$CurrentTheme = Gdn::config('Garden.Theme', '');
		$ThemeManager = Gdn::themeManager();
		$Sender->CurrentThemeInfo = $ThemeManager->enabledThemeInfo();
		$Folder = PATH_CACHE . DS . 'CustomCSS';

		// Create the CustomCSS cache folder
		if (!file_exists($Folder))
			@mkdir($Folder);

		$Folder .= DS . $CurrentTheme;
		if (!file_exists($Folder))
			@mkdir($Folder);

		$FileName = Gdn::config('Plugins.CustomCSS.PreviewFile', 'rev_0.css');
		if (!$Sender->Form->authenticatedPostBack()) {
			$LoadFile = arrayValue(0, $Sender->RequestArgs);
			if ($LoadFile !== FALSE && strpos($LoadFile, 'rev_') !== FALSE) {
				$LoadFile .= '.css';
				if (file_exists($Folder . DS . $LoadFile))
					$FileName = $LoadFile;
			}
		}

		if (!file_exists($Folder . DS . $FileName))
			$FileName = 'rev_0.css';

		$CurrentRevision = str_replace(['rev_', '.css'], ['', ''], $FileName);
		$CurrentRevision = is_numeric($CurrentRevision) ? $CurrentRevision : 1;
		$Contents = $FileName == 'rev_0.css' ? '/* ---- Custom CSS ----

If you are unfamiliar with CSS, learning can be fun and easy. There are some
learning resources in the help section on the right-hand side of the page.

Here are some things you should know before you begin:

1. Any definitions you enter here will be related to the currently enabled
   theme. If you change to a different theme, these definitions will no longer
   be included and will not be visible here. If you change back to this theme,
   you will see these definitions again.
	
2. By default, your custom css definitions will be included *after* the theme
   css files. So, your definitions will take precedence over the theme ones.

3. You can choose to not include the theme css files at all, and instead only
   include your custom css definitions with the "Revision Options" to the
   top-right.
	
4. We strip comments, invalid code, expressions, @imports, and unsafe or
   potentially malicious code from your css before saving it.
	
5. When you preview your definitions, you are the only one who can see the
   preview.
	
Have fun!!
*/' : file_get_contents($Folder . DS . $FileName);
		if (!$Sender->Form->authenticatedPostBack()) {
			$Sender->Form->setFormValue('CustomCSS', $Contents);
		} else {
			$IsSave = $Sender->Form->getFormValue('Apply') ? TRUE : FALSE;
			$IsApply = $Sender->Form->getFormValue('ApplyChanges') ? TRUE : FALSE;
			$IsPreview = $Sender->Form->getFormValue('Preview') ? TRUE : FALSE;
			$IsExitPreview = $Sender->Form->getFormValue('ExitPreview') ? TRUE : FALSE;
			$IncludeTheme = $Sender->Form->getFormValue('IncludeTheme');

			if ($IsApply || $IsSave)
				$Sender->informMessage("Your changes have been applied.");

			if ($IsApply) {
				saveToConfig('Plugins.CustomCSS.File', $FileName);
				$Sender->Form->setFormValue('CustomCSS', $Contents);
				$UserModel->savePreference($Session->UserID, 'PreviewCustomCSS', FALSE);
			} else if ($IsPreview || $IsSave) {
				$NewCSS = $Sender->Form->getFormValue('CustomCSS', '');

				// Clean the css
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
				$NewCSS = wp_kses_split($Prev = $NewCSS, [], []);
				$NewCSS = str_replace( '&gt;', '>', $NewCSS); // kses replaces lone '>' with &gt;
				// Why both KSES and strip_tags?  Because we just added some '>'.
				$NewCSS = strip_tags($NewCSS);

				// if ($NewCSS != $Prev) $warnings[] = 'kses found stuff';

				$csstidy->parse($NewCSS);
				$NewCSS = $csstidy->print->plain();

				if (in_array($IncludeTheme, ['Yes', 'No']))
					saveToConfig('Plugins.CustomCSS.IncludeTheme', $IncludeTheme);

				if ($Contents != $NewCSS) {
					$FileName = 'rev_'.($CurrentRevision + 1).'.css';

					file_put_contents($Folder . DS . $FileName, $NewCSS);
					saveToConfig('Plugins.CustomCSS.PreviewFile', $FileName);

					// Only keep the last 20 revs
					$ActiveRevision = str_replace(['rev_', '.css'], '', Gdn::config('Plugins.CustomCSS.File', ''));
					$ActiveRevision = is_numeric($ActiveRevision) ? $ActiveRevision : 0;
					if ($Handle = opendir($Folder)) {
                  while (FALSE !== ($File = readdir($Handle))) {
                     if (substr($File, 0, 4) == 'rev_') {
								$Revision = str_replace(['rev_', '.css'], '', $File);
								if (is_numeric($Revision) && $Revision != $ActiveRevision && $Revision < $CurrentRevision - 19) {
									@unlink(PATH_CACHE . DS . 'CustomCSS' . DS . $CurrentTheme. DS . 'rev_' . $Revision . '.css');
								}
							}
						}
					}
				}

				if ($IsSave)
					saveToConfig('Plugins.CustomCSS.File', $FileName); // <-- Update the name of the file that will be included.

				$Sender->Form->setFormValue('CustomCSS', $NewCSS);
				$Sender->Form->setFormValue('IncludeTheme', $IncludeTheme);
			}

			if ($IsPreview) {
				$UserModel->savePreference($Session->UserID, 'PreviewCustomCSS', TRUE);
				redirectTo('/');
			} else if ($IsExitPreview) {
				$UserModel->savePreference($Session->UserID, 'PreviewCustomCSS', FALSE);
				redirectTo('/plugin/customcss');
			}
		}
      $Sender->render(PATH_PLUGINS . DS . 'CustomCSS' . DS . 'views' . DS . 'customcss.php');
   }

   public function setup() {
      // No setup required.
   }

}


if (!function_exists('safecss_class')) {
	function safecss_class() {
		// Wrapped so we don't need the parent class just to load the plugin
		if (class_exists('safecss'))
			return;

		require_once('csstidy/class.csstidy.php');
		class safecss extends csstidy_optimise {
			var $tales = [];
			var $props_w_urls = ['background', 'background-image', 'list-style', 'list-style-image'];
			var $allowed_protocols = ['http'];

			function __construct(&$css) {
				return $this->csstidy_optimise($css);
			}

			function postparse() {
				if ( !empty($this->parser->import) ) {
					$this->tattle("Import attempt:\n".print_r($this->parser->import,1));
					$this->parser->import = [];
				}
				if ( !empty($this->parser->charset) ) {
					$this->tattle("Charset attempt:\n".print_r($this->parser->charset,1));
					$this->parser->charset = [];
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
						$SiteID = Gdn::config('VanillaForums.SiteID', '');
						$Session = Gdn::session();
						$E = new Gdn_Email();
						$E->Subject = '[Custom CSS]';
						$E->Message = 'SiteID: '.$SiteID."\nUserID: ".$Session->UserID."\n".implode("\n", $this->tales);
						$E->To = 'support@vanillaforums.com';
						$E->from('customcss@vanillaforums.com', 'Custom CSS Attack');
						$E->send();
					} catch(Exception $ex) {
						// do nothing
					}
				}
			}
		}
	}
}
