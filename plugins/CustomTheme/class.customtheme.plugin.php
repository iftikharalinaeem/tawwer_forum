<?php if (!defined('APPLICATION')) exit();

// 2.1 - Added a fix so that css files are in a more unique url so it works with autostatic (which is not forum-specific).

// Define the plugin:
$PluginInfo['CustomTheme'] = array(
   'Name' => 'Custom Theme',
   'Description' => 'Allows administrators to customize the CSS & master HTML template of the currently enabled theme.',
   'Version' => '2.1.4',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
	'SettingsUrl' => '/settings/customtheme'
);

class CustomThemePlugin implements Gdn_IPlugin {
   
   public static function GetRevisionID($Key) {
      if (StringEndsWith($Key, 'ID'))
         $Value = C("Plugins.CustomTheme.$Key", '0');
      else
         $Value = $Key;
      
      $Parts = explode('_', $Value, 2);
      return $Parts[0];
   }
   
   public static function SetRevisionID($RevisionID, $Time) {
      return $RevisionID.'_'.str_replace(array(' ', ':'), '-', $Time);
   }
   
   /// Event Handlers ///
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		if (!$this->_CanCustomizeTheme())
			return;
		
		$Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Appearance', 'Customize Theme', 'settings/customtheme', 'Garden.Settings.Manage');
	}

   public function Base_Render_Before(&$Sender) {
		if (IsMobile())
			return;
		
		// If we are in preview mode...
		if (Gdn::Session()->GetPreference('PreviewCustomTheme')) {
			// Add the css file that styles the preview inform message buttons
			$Sender->AddCssFile('previewtheme.css', 'plugins/CustomTheme');
			
			// Inform the user of the preview status
		   $Form = new Gdn_Form();
			$Message = 'You are previewing your custom theme revisions.'
				.$Form->Open(array('action' => Url('settings/customtheme')))
				.'<div><strong>Options:</strong> ';
			if (C('Plugins.CustomTheme.Enabled'))
				$Message .= $Form->Button('Apply Changes', array('class' => 'PreviewThemeButton'));
			
			$Message .=  $Form->Button('Exit Preview', array('class' => 'PreviewThemeButton'))
				.'</div>'
				.$Form->Close();
				
			$Sender->InformMessage($Message, 'NoDismiss');
		}
	}
	
	/**
	 * Can the current theme be customized?
	 */
	private function _CanCustomizeTheme() {
		$ThemeManager = new Gdn_ThemeManager();
		$ThemeInfo = $ThemeManager->EnabledThemeInfo();
      
		// Make sure the current theme uses a smarty master template instead of php
		return $ThemeInfo['Index'] == 'default' || file_exists(PATH_THEMES.'/'.GetValue('Folder', $ThemeInfo, '').'/views/default.master.tpl');
	}
	
	/**
	 * Add the theme CSS customizations.
	 */
   public function Base_BeforeAddCss_Handler($Sender) {
		if (IsMobile())
			return;
		
		// If we are using the default master view, and in preview mode, use custom css & html files
		$DoPreview = Gdn::Session()->GetPreference('PreviewCustomTheme', FALSE);
		$IsDefaultMaster = $Sender->MasterView == 'default' || $Sender->MasterView == '';
		$WorkingRevisionID = C('Plugins.CustomTheme.WorkingRevisionID', 0);
		$LiveRevisionID = C('Plugins.CustomTheme.LiveRevisionID', 0);
		// $WorkingIncludeThemeCSS = C('Plugins.CustomTheme.WorkingIncludeThemeCSS', 'Yes') == 'Yes' ? TRUE : FALSE;
		// $LiveIncludeThemeCSS = C('Plugins.CustomTheme.LiveIncludeThemeCSS', 'Yes') == 'Yes' ? TRUE : FALSE;
		
		// Wipe out master css?
		// if ($IsDefaultMaster && (!$LiveIncludeThemeCSS || ($DoPreview && !$WorkingIncludeThemeCSS)))
		//	$Sender->ClearCSSFiles();
		
		if ($IsDefaultMaster && $WorkingRevisionID == 0 && $LiveRevisionID == 0) {
			// Fallbacks to old method
			$CssFiles = GetValue('CssFiles', $Sender->EventArguments);
			$PreviewCSSFile = C('Plugins.CustomTheme.PreviewCSS', '');
			if ($PreviewCSSFile != '' && $DoPreview)
				$CssFiles[] = array('FileName' => $PreviewCSSFile, 'AppFolder' => 'false');
			else
				$CssFiles[] = array('FileName' => 'customtheme.css', 'AppFolder' => 'false');

			$Sender->EventArguments['CssFiles'] = $CssFiles;
		}
	}
	/**
	 * New method of adding css to page (from database).
	 * And handle changing the master view.
	 */
   public function Base_BeforeFetchMaster_Handler($Sender) {
		if (IsMobile())
			return;
		
		$this->_Construct();

		// If we are using the default master view, and in preview mode, use custom css & html files
		$DoPreview = Gdn::Session()->GetPreference('PreviewCustomTheme', FALSE);
      $AddCustomCss = ArrayHasValue(Gdn::Controller()->CssFiles(), 'style.css'); //
      $IsDefaultMaster = $Sender->MasterView == 'default' || $Sender->MasterView == '';
		$IsHead = property_exists($Sender, 'Head') && is_object($Sender->Head);
		$WorkingRevisionID = C('Plugins.CustomTheme.WorkingRevisionID', 0);
		$LiveRevisionID = C('Plugins.CustomTheme.LiveRevisionID', 0);
		
		if ($IsHead && $AddCustomCss) {
			// New method
			if ($DoPreview && $WorkingRevisionID > 0) {
				// $Sender->Head->AddString("\n".'<link rel="stylesheet" type="text/css" href="'.Asset('/plugin/customcss/rev_'.$WorkingRevisionID.'.css', FALSE, TRUE).'" media="all" />');
				$Sender->Head->AddCss('/plugin/customcss/'.Gdn_Format::Url(Gdn::Request()->Host()).'/rev_'.$WorkingRevisionID.'.css', 'all');
			} elseif ($LiveRevisionID > 0) {
				// $Sender->Head->AddString("\n".'<link rel="stylesheet" type="text/css" href="'.Asset('/plugin/customcss/rev_'.$LiveRevisionID.'.css', FALSE, TRUE).'" media="all" />');
				$Sender->Head->AddCss('/plugin/customcss/'.Gdn_Format::Url(Gdn::Request()->Host()).'/rev_'.$LiveRevisionID.'.css', 'all');
			}
		}

		// Backwards compatibility
		$Theme = C('Garden.Theme');
		$PreviewHtml = C('Plugins.CustomTheme.PreviewHtml', '');
		$HtmlFile = PATH_THEMES . DS . $Theme . DS . 'views' . DS . $PreviewHtml;
		if ($PreviewHtml == '' || !file_exists($HtmlFile))
			$HtmlFile = '';
			
		if ($LiveRevisionID > 0)
			$HtmlFile = 'customtheme:default_master_'.$LiveRevisionID.'.tpl';

		if ($DoPreview && $WorkingRevisionID > 0)
			$HtmlFile = 'customtheme:default_master_'.$WorkingRevisionID.'.tpl';

		if ($HtmlFile != '' && $IsDefaultMaster) {
			$MasterViewPath = GetValue('MasterViewPath', $Sender->EventArguments, '');
			$Sender->EventArguments['MasterViewPath'] = $HtmlFile;
		}
	}
	
   /** 
   *	Hook smarty up with our custom template resource (functions at bottom of this file)
   * @param type $Smarty 
   */
   public function Gdn_Smarty_Init_Handler($Smarty) {
		// Register the resource name "customtheme"
      $Smarty->register_resource("customtheme", array(
         "customtheme_smarty_get_template", 
         "customtheme_smarty_get_timestamp",
         "customtheme_smarty_get_secure",
         "customtheme_smarty_get_trusted"
      ));
   }

   public static function GetRevisionFromFileName($FileName, $Default = 0) {
		if ($FileName === FALSE)
			return $Default;
		
		// Note: the _css and _tpl is because PHP replaces url dots with underscores automatically.
		$Revision = str_replace(array('default_master_', 'rev_', 'custom_', '.css', '.tpl', '_css', '_tpl'), array('', '', '', '', '', '', ''), $FileName);
      $Revision = self::GetRevisionID($Revision);
		return is_numeric($Revision) ? $Revision : $Default;
	}
	
	/**
	 * Renders the requested css from the db.
	 */
	public function PluginController_CustomCSS_Create($Sender) {
		$this->_Construct();
		
		header('Content-Type: text/css', TRUE); // Force browsers to agree that this is css
		$Sender->MasterView = 'none';
		$FileToLoad = GetValue(1, $Sender->RequestArgs);
		$RevisionID = CustomThemePlugin::GetRevisionFromFileName($FileToLoad);
		$ThemeData = Gdn::SQL()
			->Select()
			->From('CustomThemeRevision')
			->Where('RevisionID', $RevisionID)
			->Get()
			->FirstRow();
				
		if ($ThemeData)
			echo $ThemeData->CSS;

		// Cleanup
		Gdn::Database()->CloseConnection();
	}

	/**
	 * Write the button to customize the current theme.
	 */
	public function SettingsController_AfterCurrentTheme_Handler($Sender) {
		if ($this->_CanCustomizeTheme())
			echo Wrap(sprintf(T('You can customize the HTML and CSS for this theme on the %s page.'), Anchor('Customize Theme', 'settings/customtheme')), 'div', array('class' => 'CustomThemeOptions'));

	}
	
   /**
    *
    * @param Gdn_Controller $Sender 
    */
   public function SettingsController_CustomTheme_Create($Sender) {
		$Session = Gdn::Session();
		$UserModel = Gdn::UserModel();
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Customize Theme');
      $Sender->AddSideMenu('settings/customtheme');
		
      $Sender->Form = new Gdn_Form();
		if ($Sender->Form->GetFormValue('Exit_Preview') ? TRUE : FALSE) {
			$UserModel->SavePreference($Session->UserID, 'PreviewCustomTheme', FALSE);
			Redirect('/settings/customtheme');
		}		
		
//		$Sender->AddJsFile('/js/library/jquery.autogrow.js');
		$Sender->AddJsFile('customtheme.js', 'plugins/CustomTheme');
      $Sender->AddJsFile('jquery.textarea.js', 'plugins/CustomTheme');
		$Sender->AddCssFile('customtheme.css', '/plugins/CustomTheme');
		
		$ThemeManager = new Gdn_ThemeManager();
		$CurrentThemeInfo = $ThemeManager->EnabledThemeInfo();
		$CurrentThemeFolder = basename(GetValue('ThemeRoot', $CurrentThemeInfo));
		$Folder = PATH_THEMES . DS . $CurrentThemeFolder;
		
		// Keep these for backwards compatibility:
		$PreviewCSSFile = C('Plugins.CustomTheme.PreviewCSS', 'custom_0.css');
		$PreviewHtmlFile = C('Plugins.CustomTheme.PreviewHtml', 'custom_0.tpl');
		// This is the new method:
		$LiveRevisionID = self::GetRevisionID('LiveRevisionID');      
		$WorkingRevisionID = self::GetRevisionID('WorkingRevisionID');
		
		// Are we switching back to a previous revision (css OR html)?
		if (!$Sender->Form->AuthenticatedPostBack()) {
			$RequestedRevisionID = GetValue(2, $Sender->RequestArgs);
			if (is_numeric($RequestedRevisionID) && $RequestedRevisionID >= 0)
				$WorkingRevisionID = $RequestedRevisionID;
		}

		// Get customizations from db
		$this->_Construct();
		$ThemeData = Gdn::SQL()
			->Select()
			->From('CustomThemeRevision')
			->Where('RevisionID', $WorkingRevisionID)
			->Get()
			->FirstRow();
			
		if ($ThemeData) {
			$HtmlContents = $ThemeData->Html;
			$CSSContents = $ThemeData->CSS;
			// $IncludeThemeCSS = $ThemeData->IncludeThemeCSS;
         $Label = $ThemeData->Label;
         $SaveWorkingRevisionID = self::SetRevisionID($WorkingRevisionID, $ThemeData->DateInserted);
		} else {
			// $IncludeThemeCSS = 'Yes';
			$CSSContents = '';
         $Label = '';
			if (file_exists($Folder . DS . 'design' . DS . 'customtheme.css'))
				$CSSContents = file_get_contents ($Folder . DS . 'design' . DS . 'customtheme.css');
				
			if ($CSSContents == '')
				$CSSContents = '/* ---- Edit CSS ----

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
	
4. When you preview your changes, you are the only one who can see the preview.

5. Feel free to delete these comments!

*/';
			$HtmlContents = '';
			if (file_exists($Folder . DS . 'views' . DS . 'default.master.tpl'))
				$HtmlContents = file_get_contents ($Folder . DS . 'views' . DS . 'default.master.tpl');
         else
            $HtmlContents = file_get_contents(PATH_APPLICATIONS.'/dashboard/views/default.master.tpl');
		}
			
		// If viewing the form for the first time
		if (!$Sender->Form->AuthenticatedPostBack()) {
			$Sender->Form->SetFormValue('CustomCSS', $CSSContents);
			$Sender->Form->SetFormValue('CustomHtml', $HtmlContents);
			// $Sender->Form->SetFormValue('IncludeThemeCSS', $IncludeThemeCSS);
         $Sender->Form->SetFormValue('Label', $Label);
		} else {
			// If saving the form
			$IsApply = $Sender->Form->GetFormValue('Apply') ? TRUE : FALSE;
			$IsPreview = $Sender->Form->GetFormValue('Preview') ? TRUE : FALSE;
			$IsApplyPreview = $Sender->Form->GetFormValue('Apply_Changes') ? TRUE : FALSE;

			// If applying the changes from a preview
			if ($IsApplyPreview) {
				$Sender->Form->SetFormValue('CustomCSS', $CSSContents);
				$Sender->Form->SetFormValue('CustomHtml', $HtmlContents);
				// $Sender->Form->SetFormValue('IncludeThemeCSS', $IncludeThemeCSS);
			}
			
			// Save the changes (if there are changes to save):
			$NewCSS = $Sender->Form->GetFormValue('CustomCSS', '');
			$NewHtml = $Sender->Form->GetFormValue('CustomHtml', '');
         $NewLabel = $Sender->Form->GetFormValue('Label', NULL);
			// $NewIncludeThemeCSS = $Sender->Form->GetFormValue('IncludeThemeCSS', 'Yes');
			$SmartyCompileError = FALSE;
			// if ($CSSContents != $NewCSS || $HtmlContents != $NewHtml || $IncludeThemeCSS != $NewIncludeThemeCSS) {
			if ($CSSContents != $NewCSS || $HtmlContents != $NewHtml) {
            $Set = array(
                  'ThemeName' => C('Garden.Theme'),
                  'Html' => $NewHtml,
                  'CSS' => $NewCSS,
                  'Label' => $NewLabel,
                  'IncludeThemeCSS' => 'Yes', // $NewIncludeThemeCSS,
                  'InsertUserID' => $Session->UserID,
                  'DateInserted' => Gdn_Format::ToDateTime(),
                  'Live' => 2
               );

            // Look for an existing working revision.
            $WorkingRow = Gdn::SQL()->GetWhere('CustomThemeRevision', 
               array('ThemeName' => C('Garden.Theme'), 'Live' => 2))->FirstRow(DATASET_TYPE_ARRAY);
            
            if ($WorkingRow) {
               $WorkingRevisionID = $WorkingRow['RevisionID'];
               Gdn::SQL()->Put('CustomThemeRevision', $Set, array('RevisionID' => $WorkingRevisionID));
            } else {
               $WorkingRevisionID = Gdn::SQL()->Insert('CustomThemeRevision', $Set);
            }
            $SaveWorkingRevisionID = self::SetRevisionID($WorkingRevisionID, $Set['DateInserted']);
				SaveToConfig('Plugins.CustomTheme.WorkingRevisionID', $SaveWorkingRevisionID);
				// SaveToConfig('Plugins.CustomTheme.WorkingIncludeThemeCSS', $NewIncludeThemeCSS);
			} elseif ($NewLabel != $Label && $WorkingRevisionID) {
            Gdn::SQL()->Put('CustomThemeRevision', array('Label' => $NewLabel), array('RevisionID' => $WorkingRevisionID));
         }

			// Check to see if there are any fatal errors in the smarty template
         $Smarty = new Gdn_Smarty();
         $SmartyCompileError = !$Smarty->TestTemplate('customtheme:default_master_'.$WorkingRevisionID.'.tpl');
			
			// Check for required assets
			$AssetError = (stripos($NewHtml, '{asset name="Foot"}') === FALSE) ? TRUE : FALSE;

			// If we are applying the changes, and the changes didn't cause crashes save the live revision number.
			if (!$AssetError && !$SmartyCompileError && ($IsApply || $IsApplyPreview)) {
				$UserModel->SavePreference($Session->UserID, 'PreviewCustomTheme', FALSE);
            
				$LiveRevisionID = $WorkingRevisionID;
            $SaveLiveRevisionID = isset($SaveWorkingRevisionID) ? $SaveWorkingRevisionID : $WorkingRevisionID;
				SaveToConfig('Plugins.CustomTheme.WorkingRevisionID', $SaveLiveRevisionID);
				// SaveToConfig('Plugins.CustomTheme.WorkingIncludeThemeCSS', $NewIncludeThemeCSS);
				SaveToConfig('Plugins.CustomTheme.LiveRevisionID', $SaveLiveRevisionID);
				SaveToConfig('Plugins.CustomTheme.LiveTime', time());
				// SaveToConfig('Plugins.CustomTheme.LiveIncludeThemeCSS', $NewIncludeThemeCSS);
				
				// Update out old live revision row(s)
				Gdn::SQL()->Update('CustomThemeRevision')
					->Set('Live', 0)
					->Where('ThemeName', C('Garden.Theme'))
					->Put();
					
				// Update new live revision row
				Gdn::SQL()->Update('CustomThemeRevision')
					->Set('Live', 1)
					->Where('RevisionID', $LiveRevisionID)
					->Put();
			}
			
			if ($IsPreview && !$SmartyCompileError && !$AssetError) {
				$UserModel->SavePreference($Session->UserID, 'PreviewCustomTheme', TRUE);
				Redirect('/');
			}
			
			if ($SmartyCompileError)
				$Sender->Form->AddError('There was a templating error in your HTML customizations. Make sure that any javascript or inline CSS definitions are wrapped in {literal} tags, and all {if} statements have a closing {/if} tag.');
			elseif ($AssetError)
			   $Sender->Form->AddError('There was a templating error in your HTML customizations. Make sure you have not removed any required tags like {asset name="Foot"}.');
			else 
				$Sender->StatusMessage = "Your changes have been applied.";
				
		}
		$Sender->SetData('LiveRevisionID', $LiveRevisionID);
		// Load revision history
		$Sender->SetData('RevisionData', Gdn::SQL()
			->Select()
			->From('CustomThemeRevision')
			->Where('ThemeName', C('Garden.Theme'))
			->OrderBy('RevisionID', 'desc')
			->Limit(10)
			->Get()
		);
		
      $Sender->Render(PATH_PLUGINS . DS . 'CustomTheme' . DS . 'views' . DS . 'customtheme.php');
   }
	
	/**
	 * After a theme has been enabled, reset it's related revisions based on
	 * what's in the db.
	 */
	public function SettingsController_AfterEnableTheme_Handler($Sender) {
		$this->SetRevisionsByTheme(C('Garden.Theme'));
	}
	
	/**
	 * Look at what's in the database for the specified theme and set it as the
	 * working & live customisations.
	 */
	public function SetRevisionsByTheme($ThemeName) {
		$this->_Construct();
		$Live = Gdn::SQL()->Select()
			->From('CustomThemeRevision')
			->Where('ThemeName', $ThemeName)
			->Where('Live', 1)
			->OrderBy('RevisionID', 'desc')
			->Limit(1, 0)
			->Get()
			->FirstRow(DATASET_TYPE_ARRAY);
			
		if ($Live) {
			SaveToConfig('Plugins.CustomTheme.LiveRevisionID', self::SetRevisionID($Live['RevisionID'], $Live['DateInserted']));
			SaveToConfig('Plugins.CustomTheme.LiveTime', time());
			// SaveToConfig('Plugins.CustomTheme.LiveIncludeThemeCSS', GetValue('IncludeThemeCSS', $Live));
		} else {
			SaveToConfig('Plugins.CustomTheme.LiveRevisionID', 0);
			SaveToConfig('Plugins.CustomTheme.LiveTime', time());
			// SaveToConfig('Plugins.CustomTheme.LiveIncludeThemeCSS', 0);
		}
		
		$Working = Gdn::SQL()->Select()
			->From('CustomThemeRevision')
			->Where('ThemeName', $ThemeName)
         ->Where('Live', 2)
			->OrderBy('RevisionID', 'desc')
			->Limit(1, 0)
			->Get()
			->FirstRow(DATASET_TYPE_ARRAY);

		if ($Working) {
			SaveToConfig('Plugins.CustomTheme.WorkingRevisionID', self::SetRevisionID($Working['RevisionID'], $Working['DateInserted']));
			// SaveToConfig('Plugins.CustomTheme.WorkingIncludeThemeCSS', GetValue('IncludeThemeCSS', $Working));
		} else {
			SaveToConfig('Plugins.CustomTheme.WorkingRevisionID', 0);
			// SaveToConfig('Plugins.CustomTheme.WorkingIncludeThemeCSS', 0);
		}
	}
	
   public function SettingsController_CustomThemeUpgrade_Create($Sender, $EventArguments = array()) {
		$Sender->Render(PATH_PLUGINS . DS . 'CustomTheme' . DS . 'views' . DS . 'upgrade.php');
	}
	
   public function Setup() {
		$this->Structure();
	}

   public function Structure() {
      Gdn::Structure()
         ->Table('CustomThemeRevision')
			->PrimaryKey('RevisionID')
         ->Column('Label', 'varchar(50)', TRUE)
         ->Column('ThemeName', 'varchar(255)')
			->Column('Html', 'text', null)
			->Column('CSS', 'text', null)
			->Column('Live', 'int', 0)
			->Column('IncludeThemeCSS', array('Yes', 'No'), 'Yes')
			->Column('InsertUserID', 'int', TRUE, 'key')
			->Column('DateInserted', 'datetime')
         ->Set();
      
      // Make sure the theme revision exists in the database.
      $RevisionID = C('Plugins.CustomTheme.LiveRevisionID');
      if ($RevisionID) {
         $Row = Gdn::SQL()->GetWhere('CustomThemeRevision', array('RevisionID' => $RevisionID))->FirstRow();
         if (!$Row) {
            RemoveFromConfig('Plugins.CustomTheme.LiveRevisionID');
         }
      }
   }
	
	/**
	 * Lazy-Ensure that the database table exists for saving theme changes.
	 */
	private function _Construct() {
		if (!C('Plugins.CustomTheme.Constructed')) {
			try {
				$this->Structure();
			} catch (Exception $ex) {
				// Fail silently.
			}
			SaveToConfig('Plugins.CustomTheme.Constructed', TRUE);
		}
	}
	
   /**
    *
    * @param Gdn_Controller $Sender 
    */
   public function SettingsController_CustomCSS_Create($Sender) { 
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Edit CSS');      
      $Sender->MasterView = 'empty';
      $Sender->ClearCSSFiles();
      $Sender->AddCssFile('editcss.css', 'plugins/CustomTheme');
      
      $WorkingRevisionID = self::GetRevisionID('WorkingRevisionID');
		$ThemeData = Gdn::SQL()
			->Select()
			->From('CustomThemeRevision')
			->Where('RevisionID', $WorkingRevisionID)
			->Get()
			->FirstRow();
			
		if (!$Sender->Form->AuthenticatedPostBack()) {
         if ($ThemeData)
            $Sender->Form->SetFormValue('CSS', GetValue('CSS', $ThemeData));
		}
      
      $Sender->Render('customcss', '', 'plugins/CustomTheme');
   }
}

/* Smarty functions to allow reading the template from the db as a resource */

function customtheme_smarty_get_template($tpl_name, &$tpl_source, $smarty_obj) {
	// do database call here to fetch your template,
	// populating $tpl_source with actual template contents
	$RevisionID = CustomThemePlugin::GetRevisionFromFileName($tpl_name);
	$Data = Gdn::SQL()->Select('Html')->From('CustomThemeRevision')->Where('RevisionID', $RevisionID)->Get()->FirstRow();
   if ($Data)
		$tpl_source = $Data->Html;
	else
		return FALSE;
	
   // return true on success, false to generate failure notification
	return TRUE;
}

function customtheme_smarty_get_timestamp($tpl_name, &$tpl_timestamp, $smarty_obj) {
    // do database call here to populate $tpl_timestamp
    // with unix epoch time value of last template modification.
    // This is used to determine if recompile is necessary.
    $tpl_timestamp = C('Plugins.CustomTheme.LiveTime');
	 if ($tpl_timestamp)
		$tpl_timestamp = time(); // this will always recompile!
		
    // return true on success, false to generate failure notification
    return true;
}

function customtheme_smarty_get_secure($tpl_name, $smarty_obj) {
    // assume all templates are secure
    return true;
}

function customtheme_smarty_get_trusted($tpl_name, $smarty_obj) {
    // not used for templates
}
