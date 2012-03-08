<?php if (!defined('APPLICATION')) exit();

/**
 * Define the plugin
 */
$PluginInfo['TwitterFeeds'] = array(
	'Name' =>		'Twitter Feeds',
	'Description' => 'Allows Users to add their Twitter Feed to their Profile.',
	'Version' =>	'1.0',
	'Author' =>		'Oliver Raduner',
	'AuthorEmail' => 'vanilla@raduner.ch',
	'AuthorUrl' =>	'http://raduner.ch/',
	'RequiredPlugins' => FALSE,
	'HasLocale' => FALSE,
	'RegisterPermissions' => FALSE,
	'SettingsUrl' => FALSE,
	'SettingsPermission' => FALSE
);


/**
 * Twitter Feeds Plugin
 *
 * Allows Users to add their Twitter Feed to their Profile.
 *
 * @version 1.0
 * @author Oliver Raduner <vanilla@raduner.ch>
 *
 * @todo Allow to enable a "Master"-Feed which would display all User Tweets on a top level page
 */
class TwitterFeedsPlugin implements Gdn_IPlugin
{	
	/**
	 * Adds a new Panel with the User's last Tweets to the Profile Page
	 * 
	 * @version 1.0
	 * @since 1.0
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 *
	 * @todo Make use of http://twitter.com/javascripts/blogger.js ??
	 */
	public function ProfileController_AddProfileTabs_Handler(&$Sender)
	{
		// Get the selected User's Twitter Name
		$TwitterName = $Sender->User->TwitterName;
		
		if (!empty($TwitterName))
		{
			
			/**
			 * CUSTOM SETTINGS
			 */
			$NumberOfTweets		= 6;		// [Integer] - The number of Tweets to fetch
			$LinkifyUsernames	= TRUE;		// [TRUE|FALSE] - Automatic linkify Twitter Usernames inside a Tweet
			$UseHovercards		= TRUE;		// [TRUE|FALSE] - Show context-aware Tooltips when hovering a Twitter Username in a Tweet
			$AddFollowButton	= FALSE;	// [TRUE|FALSE] - Adds also a Follow Button for that particular User
			
			
			/**
			 * General Settings
			 *
			 * !! DO NOT MODIFY !!
			 */
			$TwApiKey = '0YeyyhFafvSMoGTam5OjZQ'; // Twitter API-Key to use with this Plugin
			$NumberOfTweets = ($NumberOfTweets < 1) ? 5 : $NumberOfTweets; // Validate that no stupid Custom Settings have been made ;-)
			$HtmlOut = '';	// HTML Content of the new Side Panel
			
			/**
			 * Build the User's Twitter Feed
			 * @link http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-user_timeline
			 */
			$TweetsJson = 'http://api.twitter.com/1/statuses/user_timeline.json?id='.$Sender->User->TwitterName.'&count='.$NumberOfTweets;
			$Tweets = json_decode(file_get_contents($TweetsJson), TRUE);	// Grab the Twitter Feed from the URL into an Array
			
			/**
			 * Initialize Twitter @Anywhere
			 * @link http://dev.twitter.com/anywhere/begin
			 */
			$HtmlOut .= '<script src="http://platform.twitter.com/anywhere.js?id='.$TwApiKey.'" type="text/javascript"></script>';
			$HtmlOut .= '<script type="text/javascript">twttr.anywhere(function (T) {';
			$HtmlOut .= ($LinkifyUsernames === TRUE) ? 'T(".Tweet").linkifyUsers();' : '';
			$HtmlOut .= ($UseHovercards === TRUE) ? 'T(".Tweet").hovercards();' : '';
			$HtmlOut .= '});</script>';
			
			/**
			 * Construct the Tweets Output
			 */
			$Sender->AddCssFile('plugins/TwitterFeeds/twitterfeeds.css');
			$HtmlOut .= '<div id="TwitterFeeds" class="Box">';
			$HtmlOut .= '<div id="TwitterFeedsTitle">';
			  $HtmlOut .= '<div id="TwitterIcon"></div>';
			  $HtmlOut .= '<div><h4><a href="http://twitter.com/'.$TwitterName.'" title="'.$Sender->User->Name.T(' on Twitter').'">'.$TwitterName.'</a></h4></div>';
			$HtmlOut .= '</div>';
			$HtmlOut .= '<ul class="PanelInfo">';
			
			foreach ($Tweets as $Tweet)
			{
				$CreatedAt = strtotime($Tweet['created_at']);
				
				$HtmlOut .= '<li class="Tweet">';
				$HtmlOut .= Gdn_Format::Links($Tweet['text']);
				$HtmlOut .= ' <small><a href="http://twitter.com/'.$TwitterName.'/statuses/'.$Tweet['id'].'">'.Gdn_Format::Date($CreatedAt).'</a></small>';
				$HtmlOut .= '</li>';
			}
			$HtmlOut .= '</ul>';
			
			// If enabled, add also a Follow Button to the Page
			if ($AddFollowButton === TRUE)
			{
				$HtmlOut .= '<span id="follow-'.$TwitterName.'"></span>';
				$HtmlOut .= '<script type="text/javascript">';
				$HtmlOut .= 'twttr.anywhere(function (T) {';
				$HtmlOut .= 'T("#follow-'.$TwitterName.'").followButton("'.$TwitterName.'");';
				$HtmlOut .= '});</script>';
			}
			$HtmlOut .= '</div>';
			
			/**
			 * Add the new Panel
			 */
			$Sender->AddAsset('Panel', $HtmlOut, 'TwitterFeeds');
			
		} else {
			return FALSE;
		}
	}
	
	
	/**
	 * Add a textfield to the Profile, so the User can save his Twitter Name
	 * 
	 * @version 1.0
	 * @since 1.0
	 * @author Oliver Raduner <vanilla@raduner.ch>
	 */
	public function ProfileController_EditMyAccountAfter_Handler(&$Sender) {
		echo '<li>';
		echo $Sender->Form->Label('Twitter Name', 'TwitterName');
		echo $Sender->Form->Input('TwitterName', 'text', array('maxlength' => 15));
		echo '</li>';
	}
	
	
	/**
	 * Initialize required data
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	public function Setup()
	{
		$Structure = Gdn::Structure();
		
		// Add an additional Column to the User DB-Table
		$Structure->Table('User')
			->Column('TwitterName', 'varchar(15)', TRUE)
			->Set(FALSE, FALSE);
	}	
		
}

?>