<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['adnetwork'] = array(
   'Name' => 'Ad Network',
   'Description' => "Vanilla's advertising network plugin.",
   'Version' => '1.0',
   'MobileFriendly' => TRUE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
   'Hidden' => FALSE
);

class AdNetworkPlugin implements Gdn_IPlugin {

	/**
	 * Drop any advert js in the head of the discussion controller page.
	 */
   public function DiscussionController_Render_Before($Sender) {
		$this->Ad_PostRelease($Sender);
   }
	
	/**
	 * Create the post-release template page.
	 */
	public function DiscussionController_PostRelease_Create($Sender) {
		$Sender->IsPostReleasePage = TRUE;
		
		// Invalidate the session so that bookmark link doesn't show
		$Session = Gdn::Session();
		$Session->UserID = 0;
		
		// Force categories to be turned off (in memory) so it doesn't show a blank category
		SaveToConfig('Vanilla.Categories.Use', FALSE, array('Save' => FALSE));
		
		// Don't let the admin checkboxes appear
		$Sender->CanEditComments = FALSE;
		
		// Set up the discussion & comments & any other elements necessary for the rendering of the empty discussion.		
		$Discussion = (object) array(
			'DiscussionID' => 0,
			'Name' => '<span class="prx_title"></span>',
			'Body' => '<span class="prx_body"></span>',
			'Format' => 'Html',
			'Bookmarked' => 0,
			'CategoryID' => 0,
			'Category' => '',
			'CategoryUrlCode' => '',
			'InsertUserID' => 1,
			'InsertName' => 'Advertisement',
			'DateInserted' => Gdn_Format::ToDateTime(),
			'Announce' => 0,
			'Sink' => 0,
			'Closed' => 1,
			'InsertIPAddress' => '0.0.0.0',
			'LastCommentID' => 0
		);
		
      // Define a bogus discussion record
      $Sender->SetData('Discussion', $Discussion, TRUE);
		$Sender->Offset = 0;
      
      // Setup
      $Sender->Title('Sponsored Advertisement');

      // Load the comments
      $Sender->SetData('CommentData', $Sender->CommentModel->Get(0, 0, 0), TRUE);
      $Sender->SetData('Comments', $Sender->CommentData);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
		$Sender->EventArguments['PagerType'] = 'Pager';
      $Sender->Pager = $PagerFactory->GetPager($Sender->EventArguments['PagerType'], $Sender);
      $Sender->Pager->ClientID = 'Pager';
      $Sender->Pager->Configure(
         0,
         0,
         0,
         '#'
      );
      
      // Add modules
      $Sender->AddModule('NewDiscussionModule');
      $Sender->AddModule('CategoriesModule');
      $Sender->AddModule('BookmarkedModule');
		$Sender->View = 'index';
      $Sender->Render();
	}
	
	/**
	 * Wipe out discussion options on postrelease holder page
	 */
	public function DiscussionController_CommentOptions_Handler($Sender) {
		if (GetValue('IsPostReleasePage', $Sender))
			$Sender->Options = '';
	}
	
	/**
	 * Make sure that the discussion name renders properly on the postrelease holder page.
	 */
	public function DiscussionController_BeforeDiscussionTitle_Handler($Sender) {
		if (GetValue('IsPostReleasePage', $Sender))
			$Sender->EventArguments['DiscussionName'] = '<span class="prx_title"></span>';
	}
	
	/**
	 * Grab an array of ads to show & display them.
	 */
	private function ShowAds($ConfigSetting) {
		$AdsToShow = C($ConfigSetting);
		if (!is_array($AdsToShow))
			return;
		
		foreach($AdsToShow as $Ad) {
			// Show Lijit ads
			if (strtolower(substr($Ad, 0, 5)) == 'lijit')
				$this->Ad_Lijit(substr($Ad, 5));
		}
	}

	/**
	 * Render Banner ads in header.
	 */
	public function Base_TopBannerAd_Handler($Sender) {
		$this->ShowAds('Plugins.AdNetwork.TopBannerAd');
	}

	/**
	 * Render Banner ads in footer.
	 */
	public function Base_BottomBannerAd_Handler($Sender) {
		$this->ShowAds('Plugins.AdNetwork.BottomBannerAd');
	}

	/**
	 * Render Banner ads above panel.
	 */
	public function Base_TopPanelAd_Handler($Sender) {
		$this->ShowAds('Plugins.AdNetwork.TopPanelAd');
	}
	
	/**
	 * Render Banner ads below panel.
	 */
	public function Base_BottomPanelAd_Handler($Sender) {
		$this->ShowAds('Plugins.AdNetwork.BottomPanelAd');
	}
	
	/**
	 * Drop infolinks & viglinks js into the discussion page before the closing
	 * </body> tag
	 */
	public function DiscussionController_AfterBody_Handler($Sender) {
		if (in_array($Sender->MasterView, array('', 'default'))) {
			$this->Ad_InfoLinks();
			$this->Ad_VigLink();
		}
	}

   /**
	 * Hidden administrative ad control panel.
    */
   public function SettingsController_AdNetwork_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');
		$BannerAds = array('LijitWide', 'LijitBlock', 'LijitSkyscraper');
		$Conf = new ConfigurationModule($Sender);
		$Conf->Initialize(array(
			'Plugins.AdNetwork.InfoLinksEnabled' => array('Type' => 'bool', 'Control' => 'CheckBox'),
			'Plugins.AdNetwork.VigLinkEnabled' => array('Type' => 'bool', 'Control' => 'CheckBox'),
			'Plugins.AdNetwork.LijitEnabled' => array('Type' => 'bool', 'Control' => 'CheckBox'),
			'Plugins.AdNetwork.PostReleaseEnabled' => array('Type' => 'bool', 'Control' => 'CheckBox'),
			'Plugins.AdNetwork.infolink_wsid' => array('Control' => 'TextBox'),
			'Plugins.AdNetwork.LijitUrl' => array('Control' => 'TextBox'),
			'Plugins.AdNetwork.TopBannerAd' => array('Type' => 'array', 'Control' => 'CheckBoxList', 'Items' => $BannerAds),
			'Plugins.AdNetwork.BottomBannerAd' => array('Type' => 'array', 'Control' => 'CheckBoxList', 'Items' => $BannerAds),
			'Plugins.AdNetwork.TopPanelAd' => array('Type' => 'array', 'Control' => 'CheckBoxList', 'Items' => $BannerAds),
			'Plugins.AdNetwork.BottomPanelAd' => array('Type' => 'array', 'Control' => 'CheckBoxList', 'Items' => $BannerAds)
		));
		$Sender->AddSideMenu('settings/adnetwork');
		$Sender->SetData('Title', T('Ad Network'));
		$Sender->ConfigurationModule = $Conf;
		$Conf->RenderAll();
   }
	
	public function Setup() {}
	
	/**
	 * This goes just before the closing </body> tag, but also before any
	 * javascript trackers such as Google Analytics. "Please copy and paste the
	 * script code below into your website HTML pages right before the closing
	 * </BODY> HTML tag. If you currently use Google Analytics or other similar
	 * JavaScript based tools, the Infolinks JavaScript can be placed right
	 * before their code."
	 *
	 * The "var infolink_wsid = 0;" denotes the Sub-ID in use.
	 * 0 is for Vanillaforums.com; please install it on the .org forum and
	 * forumaboutforums.com so we can begin tracking it there.
	 *
	 * riverfans.vanillaforums.com = 1
	 * 9to5mac.vanillaforums.com = 2
	 *
	 * Once we're comfortable with how it worked for our sites, we can go ahead
	 * and install it with the right wsid codes on riverfans and then 9to5mac
	 * when it's ready.  This is probably obvious, but each subsequent site will
	 * be one integer higher (3, 4, 5 etc.).
	 */
	private function Ad_InfoLinks() {
		if (!C('Plugins.AdNetwork.InfoLinksEnabled'))
			return;
		
		$infolink_wsid = C('Plugins.AdNetwork.infolink_wsid', 0);
		
		echo '
<script type="text/javascript">
   var infolink_pid = 264163;
   var infolink_wsid = '.$infolink_wsid.';
</script>
<script type="text/javascript" src="http://resources.infolinks.com/js/infolinks_main.js"></script>
';
	}
	
	/**
	 * Below is the code for the VigLink portion of the monetization platform.
	 * Again this goes just before the closing </body> tag.
	 *
	 * They are OK with us using this on the free forums, so we can install this
	 * on each of those as well (as part of the template on set-up).  We should
	 * do this asap.  This is the code we'll use also for the .org forum and
	 * forumaboutforums.com.
	 *
	 * I'm waiting to hear back about a simple way to handle Sub-ID's for
	 * riverfans and 9to5mac (and any other of our monetization platform early
	 * adopters).
	 *
	 * The documents for a more involved integration are attached. "To pass the
	 * revenue through to non-ad supported networks, you will need to utilize
	 * our sub-user API. This API will allow you to create sub-users that will
	 * effectively have their own account on VigLink and allow the 75/12.5/12.5
	 * revenue split. I've attached the API documentation specific to the
	 * sub-user API."
	 */
	private function Ad_VigLink() {
		if (!C('Plugins.AdNetwork.VigLinkEnabled'))
			return;

		echo "
<script type=\"text/javascript\">
  var vglnk = { api_url: '//api.viglink.com/api',
                key: '5e1b44e2ce4b1c4c90eb4b1ebc856d97' };

  (function(d, t) {
    var s = d.createElement(t); s.type = 'text/javascript'; s.async = true;
    s.src = ('https:' == document.location.protocol ? vglnk.api_url :
             '//cdn.viglink.com/api') + '/vglnk.js';
    var r = d.getElementsByTagName(t)[0]; r.parentNode.insertBefore(s, r);
  }(document, 'script'));
</script>
		";
	}

	/**
	 * With regards to the banner network part of the monetization platform, each
	 * individual ad unit of each site/partner will have its own unique code.
	 * Below are the ones for the .org forum and forumaboutforums.com.  In this
	 * particular case, we can re-use the code for each position on both sites
	 * (i.e. same leaderboard, medium rectangle and wide skyscraper code on both
	 * sites).
	 */
	private function Ad_Lijit($Type = '') {
		if (!C('Plugins.AdNetwork.LijitEnabled'))
			return;

		$Type = strtolower($Type);
		$url = C('Plugins.AdNetwork.LijitUrl');
		// VanillaForums.org & forumaboutforums.com
		if (in_array($url, array('vanillaforums.org', 'forumaboutforums.com'))) {

			// Leaderboard 728 x 90
			if ($Type == 'wide')
				echo '<div id="lijit_region_127024"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127024&z=127024&n=1"></script>';

			// Medium Rectangle 300 x 250
			if ($Type == 'block')
				echo '<div id="lijit_region_127025"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127025&z=127025&n=3"></script>';

			// Wide Skyscraper 160 x 600
			if ($Type == 'skyscraper')
				echo '<div id="lijit_region_127026"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127026&z=127026&n=4"></script>';
		}

		// riverfans.vanillaforums.com
		if ($url == 'riverfans.vanillaforums.com') {

			// Leaderboard 728 x 90
			if ($Type == 'wide')
				echo '<div id="lijit_region_127021"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127021&z=127021&n=1"></script>';

			// Medium Rectangle 300 x 250
			if ($Type == 'block')
				echo '<div id="lijit_region_127022"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127022&z=127022&n=3"></script>';

			// Wide Skyscraper 160 x 600
			if ($Type == 'skyscraper')
				echo '<div id="lijit_region_127023"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127023&z=127023&n=4"></script>';
		}

		// 9to5mac.vanillaforums.com
		if ($url == '9to5mac.vanillaforums.com') {
			// Leaderboard 728 x 90
			if ($Type == 'wide')
				echo '<div id="lijit_region_127027"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127027&z=127027&n=1"></script>';

			// Medium Rectangle 300 x 250
			if ($Type == 'block')
				echo '<div id="lijit_region_127028"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127028&z=127028&n=3"></script>';

			// Wide Skyscraper 160 x 600
			if ($Type == 'skyscraper')
				echo '<div id="lijit_region_127029"></div>
<script type="text/javascript" src="http://www.lijit.com/delivery/fp?u=vanillaforums&i=lijit_region_127029&z=127029&n=4"></script>';
		}
	}
	
	/**
	 * In the case of Post-Release it is the same code that is used for every
	 * forum. Their system detects the domains it's serving from, applies the
	 * appropriate template and segments the activity within our dashboard.
	 *
	 * The attached installation guide explains in detail what is needed for
	 * set-up.
	 *
	 * The code that needs to be place in the <head> tag is:
	 * <script type="text/javascript" src="http://a.postrelease.com/serve/load.js"></script>
	 *
	 * Also a template page needs to be created (can be called anything we want
	 * e.g. random.php).  This page should replicate the template of a post on
	 * the given forum.  It replaces the post title tag with:
	 * <span class="prx_title"></span> .  It replaces the post body tag with:
	 * <span class="prx_body"></span> .  We should use the same file name and
	 * file path for each forum.  We pass this file name and file path to
	 * Post-Release as part of set-up.
	 *
	 * Let me know when we have been able to set this up for the .org,
	 * forumaboutforums, riverfans, and 9to5 forums and I'll pass the info on to
	 * Post-Release to make sure we are all set-up for serving, tracking and
	 * monitoring.
	 */
	private function Ad_PostRelease($Sender) {
		if (!C('Plugins.AdNetwork.PostReleaseEnabled'))
			return;

		if (property_exists($Sender, 'Head') && is_object($Sender->Head))
			$Sender->Head->AddString('<script type="text/javascript" src="http://a.postrelease.com/serve/load.js"></script>');
	}
}