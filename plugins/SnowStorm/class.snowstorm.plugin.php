<?php if (!defined('APPLICATION')) exit();
/**
 * Define the plugin:
 */
$PluginInfo['SnowStorm'] = array(
	'Name'			=> 'SnowStorm',
	'Description'	=> 'Adds falling snowflakes to the discussions & categories lists.',
	'Version'		=> '1.1',
	'Author'		=> 'Francis Fontaine',
	'AuthorEmail'	=> 'francisfontaine@gmail.com',
	'AuthorUrl'		=> 'http://francisfontaine.com/',
	'License'		=> 'Free',
	'RequiredApplications' => array('Vanilla' => '>=2.0.18'),
	'MobileFriendly' => true
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
class SnowStormPlugin extends Gdn_Plugin {
	/**
	 * Hack the Base Render in order to achieve our goal
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	public function Base_Render_Before($Sender) {
		// Show the Plugin only on the discussions page
		$DisplayOn =  array('discussionscontroller', 'categoriescontroller');
		if (!InArrayI($Sender->ControllerName, $DisplayOn)) return;

		// Attach the Plugin's JavaScript to the site
		$Sender->AddJsFile('snowstorm-min.js', 'plugins/SnowStorm');

		// Edit some config
		// For the list of options, see http://www.schillmania.com/projects/snowstorm/
		$snowStormSettings = '
		<script type="text/javascript">
			snowStorm.followMouse = false;
			snowStorm.snowColor = "#FFF";
			snowStorm.vMaxX = 2;
			snowStorm.vMaxY = 4;
			snowStorm.animationInterval = 50;
			snowStorm.flakesMax = 160;
			snowStorm.flakesMaxActive = 80;
		</script>
		';

		// Add the script to the page
		$Sender->Head->AddString($snowStormSettings);
	}

	/**
	 * No setup.
	 */
	public function Setup() { }
}
