<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['CustomCSS'] = array(
   'Name' => 'Custom CSS',
   'Description' => 'Allows administrators to add custom CSS definitions.',
   'Version' => '1',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

class CustomCSSPlugin implements Gdn_IPlugin {
   
   /**
    * Adds "Custom CSS" menu option to the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
		$Menu->AddItem('Appearance', 'Appearance');
      $Menu->AddLink('Appearance', 'Custom CSS', 'plugin/customcss', 'Garden.AdminUser.Only');
   }
	
   public function Base_Render_Before(&$Sender) {
		// If we are using the default master view, and in preview mode, add the custom css file
		$Session = Gdn::Session();
		$Preview = $Session->GetPreference('PreviewCustomCSS', FALSE);
		$Live = Gdn::Config('Plugins.CustomCSS.Enabled');
		$CurrentTheme = Gdn::Config('Garden.Theme', 'default');
		$FileName = Gdn::Config('Plugins.CustomCSS.PreviewFile', '');
		if (($Sender->MasterView == 'default' || $Sender->MasterView == '')) {
			if ($FileName != '' && $Preview)
				$Sender->AddCSSFile('/cache/CustomCSS/'.$CurrentTheme.'/'.$FileName);
			else if ($Live)
				$Sender->AddCSSFile('/cache/CustomCSS/'.$CurrentTheme.'/custom.css');
		}
		if ($Preview) {
			$Sender->AddAsset('Content', $Sender->FetchView(PATH_PLUGINS . DS . 'CustomCSS' . DS . 'views' . DS . 'preview.php'));
			$Sender->AddCSSFile('previewtheme.css', 'garden');
		}
	}
	
   public function PluginController_CustomCSS_Create(&$Sender, $EventArguments) {
		require_once('kses.php');
		$Session = Gdn::Session();
      $Sender->Permission('Garden.AdminUser.Only');
      $Sender->Title('Custom CSS');
      $Sender->AddSideMenu('plugin/customcss');
      $Sender->Form = new Gdn_Form();
		$Sender->AddJsFile('/js/library/jquery.autogrow.js');
		
		$CurrentTheme = Gdn::Config('Garden.Theme', '');
		$Folder = PATH_CACHE . DS . 'CustomCSS';

		// Create the CustomCSS cache folder
		if (!file_exists($Folder))
			@mkdir($Folder);
			
		$Folder .= DS . $CurrentTheme;
		if (!file_exists($Folder))
			@mkdir($Folder);

		$FileName = Gdn::Config('Plugins.CustomCSS.PreviewFile', 'rev_0.css');
		if (!file_exists($Folder . DS . $FileName))
			$FileName = 'rev_0.css';
			
		$CurrentRevision = str_replace(array('rev_', '.css'), array('', ''), $FileName);
		$CurrentRevision = is_numeric($CurrentRevision) ? $CurrentRevision : 1;
		$Contents = $FileName == 'rev_0.css' ? '/*
Custom CSS
==========

CSS (Cascading Stylesheets) allow you to change the colors, fonts, images, and
layout of your forum. 

If you are unfamiliar with CSS, learning can be fun and easy. Here is a tutorial
for css beginners to help you get started: http://htmldog.com/guides/cssbeginner

If you *are* familiar with CSS, here are some things you should know before you
begin:

1. Any definitions you enter here will be related to the currently enabled
   theme. If you change to a different theme, these definitions will no longer
   be included and will not be visible here. If you change back to this theme,
   you will see these definitions again.
	
2. Your custom css definitions will be included *after* the theme css files. So,
   your definitions will take precedence over the theme ones.

3. You can choose to not include the theme css files at all, and instead only
   include your custom css definitions.
	
4. We strip comments, invalid code, expressions, @imports, and unsafe or
   potentially malicious code from your css before saving it.
	
5. When you preview your definitions, you are the only one who can see the
   preview.
	
Have fun!!
*/' : file_get_contents($Folder . DS . $FileName);
		if (!$Sender->Form->AuthenticatedPostBack()) {
			$Sender->Form->SetFormValue('CustomCSS', $Contents);
		} else {
			$IsSave = $Sender->Form->GetFormValue('Save') == 'Save' ? TRUE : FALSE;
			$IsApply = $Sender->Form->GetFormValue('Apply_Changes') == 'Apply Changes' ? TRUE : FALSE;
			$IsPreview = $Sender->Form->GetFormValue('Preview') == 'Preview' ? TRUE : FALSE;
			$IsExitPreview = $Sender->Form->GetFormValue('Exit_Preview') == 'Exit Preview' ? TRUE : FALSE;
			if ($IsApply) {
				file_put_contents($Folder . DS . 'custom.css', $Contents); // <-- This is the file that gets included.
				$Sender->Form->SetFormValue('CustomCSS', $Contents);
			} else if ($IsPreview || $IsSave) {
				$NewCSS = $Sender->Form->GetFormValue('CustomCSS', '');
				
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
				$NewCSS = wp_kses_split($Prev = $NewCSS, array(), array());
				$NewCSS = str_replace( '&gt;', '>', $NewCSS); // kses replaces lone '>' with &gt;
				// Why both KSES and strip_tags?  Because we just added some '>'.
				$NewCSS = strip_tags($NewCSS);
		
				// if ($NewCSS != $Prev) $warnings[] = 'kses found stuff';
	
				$csstidy->parse($NewCSS);
				$NewCSS = $csstidy->print->plain();
				
				if ($IsSave)
					file_put_contents($Folder . DS . 'custom.css', $NewCSS); // <-- This is the file that gets included.

				if ($Contents != $NewCSS) {
					$FileName = 'rev_'.($CurrentRevision + 1).'.css';

					file_put_contents($Folder . DS . $FileName, $NewCSS);
					SaveToConfig('Plugins.CustomCSS.PreviewFile', $FileName);
				
					// Only keep the last 20 revs
					@unlink(PATH_CACHE . DS . 'CustomCSS' . DS . $CurrentTheme. DS . 'rev_' . ($CurrentRevision - 4) . '.css');
				}
			}

			$UserModel = Gdn::UserModel();
			if ($IsPreview) {
				$UserModel->SavePreference($Session->UserID, 'PreviewCustomCSS', TRUE);
				Redirect('/');
			} else if ($IsExitPreview) {
				$UserModel->SavePreference($Session->UserID, 'PreviewCustomCSS', FALSE);
				Redirect('/plugin/customcss');
			}
		}
      $Sender->Render(PATH_PLUGINS . DS . 'CustomCSS' . DS . 'views' . DS . 'customcss.php');
   }
	
   public function Setup() {
      // No setup required.
   }

}



function safecss_class() {
	// Wrapped so we don't need the parent class just to load the plugin
	if ( class_exists('safecss') )
		return;
	require_once('csstidy-1.3/class.csstidy.php');
	class safecss extends csstidy_optimise {
		var $tales = array();
		var $props_w_urls = array('background', 'background-image', 'list-style', 'list-style-image');
		var $allowed_protocols = array('http');

		function safecss(&$css) {
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