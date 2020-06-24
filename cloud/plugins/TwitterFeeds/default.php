<?php if (!defined('APPLICATION')) exit();

/**
 * Define the plugin
 */
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
	public function profileController_addProfileTabs_handler($sender)
	{
		// Get the selected User's Twitter Name
		$twitterName = $sender->User->TwitterName;
		
		if (!empty($twitterName))
		{
			
			/**
			 * CUSTOM SETTINGS
			 */
			$numberOfTweets		= 6;		// [Integer] - The number of Tweets to fetch
			$linkifyUsernames	= TRUE;		// [TRUE|FALSE] - Automatic linkify Twitter Usernames inside a Tweet
			$useHovercards		= TRUE;		// [TRUE|FALSE] - Show context-aware Tooltips when hovering a Twitter Username in a Tweet
			$addFollowButton	= FALSE;	// [TRUE|FALSE] - Adds also a Follow Button for that particular User
			
			
			/**
			 * General Settings
			 *
			 * !! DO NOT MODIFY !!
			 */
			$twApiKey = '0YeyyhFafvSMoGTam5OjZQ'; // Twitter API-Key to use with this Plugin
			$numberOfTweets = ($numberOfTweets < 1) ? 5 : $numberOfTweets; // Validate that no stupid Custom Settings have been made ;-)
			$htmlOut = '';	// HTML Content of the new Side Panel
			
			/**
			 * Build the User's Twitter Feed
			 * @link http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses-user_timeline
			 */
			$tweetsJson = 'http://api.twitter.com/1/statuses/user_timeline.json?id='.$sender->User->TwitterName.'&count='.$numberOfTweets;
			$tweets = json_decode(file_get_contents($tweetsJson), TRUE);	// Grab the Twitter Feed from the URL into an Array
			
			/**
			 * Initialize Twitter @Anywhere
			 * @link http://dev.twitter.com/anywhere/begin
			 */
			$htmlOut .= '<script src="http://platform.twitter.com/anywhere.js?id='.$twApiKey.'" type="text/javascript"></script>';
			$htmlOut .= '<script type="text/javascript">twttr.anywhere(function (T) {';
			$htmlOut .= ($linkifyUsernames === TRUE) ? 'T(".Tweet").linkifyUsers();' : '';
			$htmlOut .= ($useHovercards === TRUE) ? 'T(".Tweet").hovercards();' : '';
			$htmlOut .= '});</script>';
			
			/**
			 * Construct the Tweets Output
			 */
			$sender->addCssFile('twitterfeeds.css', 'plugins/TwitterFeeds');
			$htmlOut .= '<div id="TwitterFeeds" class="Box">';
			$htmlOut .= '<div id="TwitterFeedsTitle">';
			  $htmlOut .= '<div id="TwitterIcon"></div>';
			  $htmlOut .= '<div><h4><a href="http://twitter.com/'.$twitterName.'" title="'.$sender->User->Name.t(' on Twitter').'">'.$twitterName.'</a></h4></div>';
			$htmlOut .= '</div>';
			$htmlOut .= '<ul class="PanelInfo">';
			
			foreach ($tweets as $tweet)
			{
				$createdAt = strtotime($tweet['created_at']);
				
				$htmlOut .= '<li class="Tweet">';
				$htmlOut .= Gdn_Format::links($tweet['text']);
				$htmlOut .= ' <small><a href="http://twitter.com/'.$twitterName.'/statuses/'.$tweet['id'].'">'.Gdn_Format::date($createdAt).'</a></small>';
				$htmlOut .= '</li>';
			}
			$htmlOut .= '</ul>';
			
			// If enabled, add also a Follow Button to the Page
			if ($addFollowButton === TRUE)
			{
				$htmlOut .= '<span id="follow-'.$twitterName.'"></span>';
				$htmlOut .= '<script type="text/javascript">';
				$htmlOut .= 'twttr.anywhere(function (T) {';
				$htmlOut .= 'T("#follow-'.$twitterName.'").followButton("'.$twitterName.'");';
				$htmlOut .= '});</script>';
			}
			$htmlOut .= '</div>';
			
			/**
			 * Add the new Panel
			 */
			$sender->addAsset('Panel', $htmlOut, 'TwitterFeeds');
			
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
	public function profileController_editMyAccountAfter_handler($sender) {
		echo '<li>';
		echo $sender->Form->label('Twitter Name', 'TwitterName');
		echo $sender->Form->input('TwitterName', 'text', ['maxlength' => 15]);
		echo '</li>';
	}
	
	
	/**
	 * Initialize required data
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	public function setup()
	{
		$structure = Gdn::structure();
		
		// Add an additional Column to the User DB-Table
		$structure->table('User')
			->column('TwitterName', 'varchar(15)', TRUE)
			->set(FALSE, FALSE);
	}	
		
}

?>
