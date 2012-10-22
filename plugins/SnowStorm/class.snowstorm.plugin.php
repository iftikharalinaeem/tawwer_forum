<?php if (!defined('APPLICATION')) exit();

/**
 * Define the plugin:
 */
$PluginInfo['SnowStorm'] = array(
	'Name'			=> 'SnowStorm',
	'Description'	=> 'Adds a storm of snow in Vanilla',
	'Version'		=> '1.0',
	'Author'		=> 'Francis Fontaine',
	'AuthorEmail'	=> 'francisfontaine@gmail.com',
	'AuthorUrl'		=> 'http://francisfontaine.com/',
	'License'		=> 'Free',
	'RequiredApplications' => array('Vanilla' => '>=2.0.18'),
	'RequiredTheme'	=> FALSE,
	'RequiredPlugins' => FALSE,
	'HasLocale'		=> FALSE,
	'RegisterPermissions' => FALSE,
	'SettingsUrl'	=> FALSE,
	'SettingsPermission' => FALSE,
	'MobileFriendly' => FALSE
);


/**
 * Vanilla SnowStorm-Plugin
 *
 * @version 1.0
 * @date 21-DEC-2011
 * @author Francis Fontaine <francisfontaine@gmail.com>
 * 
 * @link http://www.schillmania.com/projects/snowstorm/ SnowStorm Plugin
 */
class SnowStormPlugin extends Gdn_Plugin
{	
	
	/**
	 * Hack the Base Render in order to achieve our goal
	 * 
	 * @version 1.0
	 * @since 1.0
	 */
	public function Base_Render_Before($Sender)
	{
		// Show the Plugin only on the discussions page
		$DisplayOn =  array('discussionscontroller');
		if (!InArrayI($Sender->ControllerName, $DisplayOn)) return;
		
		// Attach the Plugin's JavaScript to the site
		$Sender->AddJsFile($this->GetResource('snowstorm-min.js', FALSE, FALSE));
		
		// Edit some config
		// For the list of options, see http://www.schillmania.com/projects/snowstorm/
		$snowStormSettings = '
		<script type="text/javascript">
			snowStorm.followMouse = false;
			snowStorm.snowColor = "#FFF";
			snowStorm.vMaxX = 0;
			snowStorm.vMaxY = 2;
			snowStorm.animationInterval = 33;
			snowStorm.flakesMax = 128;
			snowStorm.flakesMaxActive = 64;
		</script>
		';
		
		// Add the script to the page
		$Sender->Head->AddString($snowStormSettings);
		
	}

	/**
	 * Initialize required data
	 *
	 * @since 1.0
	 * @version 1.0
	 */
	public function Setup() { }	
		
}

?>