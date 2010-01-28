<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GravityInsights'] = array(
   'Name' => 'Gravity Insights',
   'Description' => 'The Gravity Insights Community Plugin.',
   'Version' => '1',
   'Author' => 'Gravity',
   'AuthorEmail' => 'insights@gravity.com',
   'AuthorUrl' => 'http://gravity.com'
);

class GravityInsights implements Gdn_IPlugin {
	
	/**
	 * Add the hook beacon script to every page.
	 */
	public function Base_Render_Before(&$Sender) {
		// Don't do anything if this page is being loaded by ajax (the script will kill the render as jquery tries to process it):
		if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
			$Session = Gdn::Session();
			$tmp_userid = $Session->UserID;
			$tmp_usertitle = urlencode(ObjectValue('Name', $Session->User, ''));
			$tmp_posttitle='';
			$tmp_postid = 0;
			$tmp_threadtitle = '';
			$tmp_threadid = (int)ObjectValue('DiscussionID', $Sender, 0); // convert to int to prevent injection
			$tmp_forumtitle = '';
			$tmp_forumid = (int)ObjectValue('CategoryID', $Sender, 0); // convert to int to prevent injection
		
			if ($tmp_threadid === 0 && $tmp_forumid === 0) {
				// Page isn't related to any particular category or discussion
				$tmp_postid = 0;
				$tmp_forumid = 0;
				$tmp_threadtitle='';
				$tmp_forumtitle='';
			} else if ($tmp_threadid > 0) {
				// Page is related to a discussion
				$DiscussionModel = new Gdn_DiscussionModel();
				$Discussion = $DiscussionModel->GetID($tmp_threadid);
				$tmp_forumid = $Discussion->CategoryID;
				$tmp_threadtitle = urlencode($Discussion->Name);
				$tmp_forumtitle=urlencode($Discussion->Category);
			} else if ($tmp_forumid > 0) {
				// Page is related to a category
				$CategoryModel = new Gdn_CategoryModel();
				$Category = $CategoryModel->GetID($tmp_forumid);
				$tmp_threadtitle = '';
				$tmp_forumtitle = urlencode($Category->Name);
			}
			
			$Sender->AddAsset('Content', "<div id='insights_div_x99'></div>
<script language='javascript'>
vb_a_stracker='".$this->_GetGravitySetting('site_guid')."';
vb_a_threadid={$tmp_threadid};
vb_a_postid={$tmp_postid};
vb_a_forumid={$tmp_forumid};
vb_a_userid={$tmp_userid};
vb_a_username='{$tmp_usertitle}';
vb_a_posttitle='{$tmp_posttitle}';
vb_a_threadtitle='{$tmp_threadtitle}';
vb_a_forumtitle='{$tmp_forumtitle}';
document.write(unescape('%3Cscript src=\'http://input.insights.gravity.com/pigeons/capture_moth.php\' type=\'text/javascript\'%3E%3C/script%3E'));
</script>");
		}
	}
	
	/**
	 * Handles sending post data when discussions or comments are created.
	 */
	public function Gdn_CommentModel_AfterSaveComment_Handler(&$Sender) {
		$IsNewDiscussion = ArrayValue('IsNewDiscussion', $Sender->EventArguments);
		$CommentID = ArrayValue('CommentID', $Sender->EventArguments, 0);
		if ($CommentID <= 0)
			return false;
		
		$Post = array();
		$PostObject = $Sender->SQL
			->Select('c.CommentID, c.DiscussionID, c.InsertUserID, c.Body, d.CategoryID')
			->Select('cat.Name', '', 'CategoryName')
			->Select('d.Name', '', 'DiscussionName')
			->Select('iu.Name', '', 'InsertName')
			->From('Comment c')
			->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
			->Join('User iu', 'c.InsertUserID = iu.UserID')
			->Join('Category cat', 'd.CategoryID = cat.CategoryID')
			->Where('c.CommentID', $CommentID)
			->Get()
			->FirstRow();
		
		if (!is_object($PostObject))
			return false;

		$Post = array(
			'site_guid' => $this->_GetGravitySetting('site_guid'),
			'thread_id' => $PostObject->DiscussionID,
			'post_id' => 0,
			'forum_id' => $PostObject->CategoryID,
			'user_id' => $PostObject->InsertUserID,
			'forum_title' => urlencode($PostObject->CategoryName),
			'thread_title' => urlencode($PostObject->DiscussionName),
			'post_title' => "",
			'user_name' => urlencode($PostObject->InsertName),
			'forum_description' => "",
			'post_content' => urlencode($PostObject->Body),
			'poster_ip' => ArrayValue('REMOTE_ADDR', $_SERVER, '')
		);

		if ($IsNewDiscussion) {
			// This was a new discussion
			$Post['action'] = 'newthread_post_complete';
		} else {
			// This was a new comment
			$Post['action'] = 'newpost_complete';
			$Post['post_id'] = $PostObject->CommentID;
		}
		
		if ($this->_InsightsNotHiddenForum($Post['forum_id']))
			$this->_InsightsSendPost($Post);
	}

	/**
	 * Handles sending search info when searches are performed.
	 */
	public function Gdn_SearchModel_AfterBuildSearchQuery_Handler(&$Sender) {
		$Session = Gdn::Session();
		$this->_InsightsSendPost(array(
			'site_guid' => $this->_GetGravitySetting('site_guid'),
			'action' => 'search_process_fullsearch',
			'user_id' => $Session->UserID,
			'user_name' => urlencode(ObjectValue('Name', $Session->User, '')),
			'post_content' => urlencode(ArrayValue('Search', $Sender->EventArguments, ''))
		));
	}

	/**
	 * Handles sending user info when users are inserted.
	 */
	public function Gdn_UserModel_AfterInsertUser_Handler(&$Sender) {
		$InsertUserID = ArrayValue('InsertUserID', $Sender->EventArguments);
		$InsertFields = ArrayValue('InsertFields', $Sender->EventArguments, array());
		if ($InsertUserID > 0)
			$this->_InsightsSendPost(array(
				'site_guid' => $this->_GetGravitySetting('site_guid'),
				'action' => 'register_addmember_complete',
				'user_id' => $InsertUserID,
				'user_name' => urlencode(ArrayValue('Name', $InsertFields, ''))
			));
	}
	
   /**
    * No setup required.
    */
   public function Setup() {}

	/**
	 * Grabs settings from the gravity ini file.
	 */
	private $_InsightsConfig = FALSE;	
	private function _GetGravitySetting($SettingName, $DefaultValue = '') {
		if (!is_array($this->_InsightsConfig))
			$this->_InsightsConfig = parse_ini_file(PATH_PLUGINS . DS . 'GravityInsights' . DS . 'siteconfig.ini');
			
		return ArrayValue($SettingName, $this->_InsightsConfig, $DefaultValue);
	}

	/**
	 * Sends data to Insights.
	 */
	private function _InsightsSendPost($Post) {
		$QueryString = $this->_InsightsGenerateQuerystring($Post);
		$Url = $this->_GetGravitySetting('insights_url');
		$PostListener = "/pigeons/capture.php";
		$Pointer = fsockopen($Url, 80, $errno, $errstr, 2);
		stream_set_timeout($Pointer, 2);
		if ($Pointer) {    
			$Out = "POST {$PostListener} HTTP/1.1\r\n";
			$Out .= "Host: {$Url}\r\n";
			$Out .= "Content-type: application/x-www-form-urlencoded\r\n";
			$Out .= "Content-Length: ".strlen($QueryString)."\r\n";
			$Out .= "Connection: Close\r\n\r\n";
			fwrite($Pointer, $Out);
			fwrite($Pointer, $QueryString."\r\n\r\n");
			fclose($Pointer);
		}
	}
	
	/**
	 * Generate an insights querystring.
	 */
	private function _InsightsGenerateQuerystring($Params) {
		$Query = '';
		foreach($Params as $Key => $Val) {
			$Query .= "{$Key}={$Val}&";
		}
	
		$Query = trim($Query, "&");
		return $Query;
	}

	/**
	 * Will determine if this forum should be hidden from insights or not
	 * if the forum is ok to send, it will return false, other wise true means it's hidden
	 * @param int $ForumID
	 */
	private function _InsightsNotHiddenForum($ForumID) {
		$HiddenForums = $this->_GetGravitySetting('hidden_forums');
		if (is_array($HiddenForums) && !empty($HiddenForums)) {
			if (in_array($ForumID, array_map('trim', explode(',',$HiddenForums))))
				return FALSE;

		}
		return TRUE;
	}
}