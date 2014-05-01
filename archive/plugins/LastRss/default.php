<?php if (!defined('APPLICATION')) exit();

/**
 * Define the plugin:
 */
$PluginInfo['LastRssPlugin'] = array(
	'Name' 		=>	 'Last RSS for Vanilla',
	'Description' => 'Lets you add the headlines of an RSS feed to your Activities page.',
	'Version' 	=>	 '1.1',
	'Author' 	=>	 'Oliver Raduner',
	'AuthorEmail' => 'vanilla@raduner.ch',
	'AuthorUrl' =>	 'http://raduner.ch/',
	'RequiredPlugins' => FALSE,
	'HasLocale' => FALSE,
	'RegisterPermissions' => FALSE,
	'SettingsUrl' => FALSE,
	'SettingsPermission' => FALSE
);


/**
 * Last RSS Plugin
 *
 * Lets you add headlines from an RSS feed to your site.
 *
 * @version 1.1
 * @author Oliver Raduner <vanilla@raduner.ch>
 * @link http://lastrss.oslab.net/
 *
 * @todo Make a nice Adminmenu to customize the Settings
 * @todo Parsing of multiple Feeds
 * @todo Allow Users to add a personal RSS feed
 */
class LastRssPlugin implements Gdn_IPlugin
{
	
	/**
	 * Add the Plugin Stylesheet to the Header
	 *
	 * @since 1.0
	 * @version 1.0
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function DiscussionController_Render_Before(&$Sender)
	{
		$Sender->Head->AddCss('emoticons.css');
	}
	
	
	/**
	 * Hack the basic rendering in order to add the RSS panel
	 * 
	 * @since 1.0
	 * @version 1.1
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function Base_Render_Before(&$Sender)
	{
		// Include the lastRSS Class
		include('plugins' . DS . 'LastRss' . DS . 'vendors' . DS . 'lastRSS.class.php');
		
		// Initialize the Class & Variables
		$Rss = new lastRSS;
		$HtmlOut = '';
		
		/**
		 * CUSTOM SETTINGS
		 */
		$RssFeedUrl			= 'http://vanillaforums.org/rss/discussions'; // Feed Address
		$DisplayOn			=  array('activitycontroller', 'discussionscontroller'); // Pages where the Feed should be displayed on
		$Rss->cache_dir		=  PATH_CACHE;
		$Rss->cache_time	=  3600;  // Cache Feed for 1 hour
		$Rss->items_limit	=  10;	  // Amount of items to fetch
		$Rss->stripHTML		=  FALSE; // Remove HTML from Feeds
		
		// Show RSS Feed only on the Activities Page
		//if (!InArrayI($Sender->ControllerName, array('activitycontroller'))) return;
		if (!InArrayI($Sender->ControllerName, $DisplayOn)) return;
		
		// Load the RSS file
		if ($rs = $Rss->Get($RssFeedUrl))
		{
			$HtmlOut .= '<div id="RSSFeed" class="Box">';
				$HtmlOut .= '<h4><a href="'.$rs['link'].'">'.imap_utf8($rs['title']).'</a></h4>';
				$HtmlOut .= '<ul class="PanelActivity">';
				
				for($i=0; $i<$Rss->items_limit; $i++)
				{
					$HtmlOut .= '<li class="FeedEntry"><a href="'.$rs['items'][$i]['link'].'">'.imap_utf8($rs['items'][$i]['title']).'</a><br />'.Gdn_Format::Date(strtotime($rs['items'][$i]['pubDate'])).'</li>';
         			// imap_utf8 should ensure the proper output of the Feed-Title in UTF8
         		}
         		$HtmlOut .= '</ul>';
			$HtmlOut .= '</div>';
			
			$Sender->AddAsset('Panel', $HtmlOut, 'RSSFeed');
			
		}
		else {
			$HtmlOut = T('Error: RSS feed not found!');
			echo $HtmlOut;
		}
		
	}
	
	
	/**
	 * Initialize required data
	 *
	 * @since 1.0
	 * @version 1.0
	 *
	 * @todo Add Column to User-Table to save a personal RSS feed
	 */
	public function Setup()
	{
		// No Setup required
	}
	
}

?>