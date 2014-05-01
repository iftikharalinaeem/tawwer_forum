<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['GoogleSocialDataHub'] = array(
   'Name' => 'Google Social Data Hub',
   'Description' => 'Provides an Atom/RSS activity stream feed of global activities for Google Social Data Hub to consume.',
   'Version' => '1.0',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com'
);

include("feed-generator/FeedWriter.php");
include("feed-generator/FeedItem.php");
class GoogleSocialDataHubPlugin implements Gdn_IPlugin {
   
   public function ActivityController_GoogleSocialActivityData_Create($Sender) {
      $Filter = 'public';
      $NotifyUserID = ActivityModel::NOTIFY_PUBLIC;
      $Offset = 0;
      $Limit = 30;
      $Activities = $Sender->ActivityModel->GetWhere(array('NotifyUserID' => $NotifyUserID), $Offset, $Limit)->ResultArray();
      $Sender->SetData('Activities', $Activities);
      $Sender->MasterView = 'none';
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->Render('activity', '', 'plugins/GoogleSocialDataHub');
   }
   
   public function ActivityController_GoogleSocialCommentData_Create($Sender) {
      $CommentModel = new CommentModel();
      $CommentModel->CommentQuery(FALSE, FALSE);
      $Comments = $CommentModel->SQL
              ->Select('d.Name', '', 'DiscussionName')
              ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
              ->OrderBy('CommentID', 'desc')
              ->Limit(30)
              ->Get();
      Gdn::UserModel()->JoinUsers($Comments, array('InsertUserID', 'UpdateUserID'));
      $Sender->SetData('Comments', $Comments);
      $Sender->MasterView = 'none';
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->Render('comments', '', 'plugins/GoogleSocialDataHub');
   }
   
   public function Setup() {
      // No setup required.
   }   

  /**
  * Genarates an UUID
  * @author     Anis uddin Ahmad <admin@ajaxray.com>
  * @param      string  an optional prefix
  * @return     string  the formated uuid
  */
  public static function uuid($key = null, $prefix = '') {
      $key = ($key == null) ? uniqid(rand()) : $key;
      $chars = md5($key);
      $uuid  = substr($chars,0,8) . '-';
      $uuid .= substr($chars,8,4) . '-';
      $uuid .= substr($chars,12,4) . '-';
      $uuid .= substr($chars,16,4) . '-';
      $uuid .= substr($chars,20,12);
      return $prefix . $uuid;
  }   
}

