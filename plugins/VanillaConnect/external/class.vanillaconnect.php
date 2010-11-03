<?php

/**
 * Tell VanillaConnect how to connect to your forum by filling in these values:
 *
 *  VanillaForumURL        - Find this on your Vanilla dashboard, under VanillaConnect
 *  VanillaConnectKey      - Find this on your Vanilla dashboard, under VanillaConnect
 *  VanillaConnectSecret   - Find this on your Vanilla dashboard, under VanillaConnect
 *  UserEmail              - The email address of the user you're sending to Vanilla
 *  UserName               - The username of the user on your site
 *  UserID                 - The ID of the user on your site.
 *
 * $_GET['oauth_token']    - When Vanilla sends a user to this site, they have a token payload. 
 *                           This payload must be used to sign the reply.
 */

   $RequestToken = OAuthToken($_GET['oauth_token'], "");

   $VanillaConnect = VanillaConnect::Authenticate(
      $VanillaForumURL,
      $VanillaConnectKey,
      $VanillaConnectSecret,
      $RequestToken,
      $UserEmail,
      $UserName,
      $UserID
   );

/**
 *
 * DO NOT MAKE CHANGES BELOW THIS LINE!
 * --------------------------------------------------------
 */

/**
 * Vanilla Single-Sign-On foreign consumer
 *
 */
class VanillaConnect {

   protected $_Request;
   protected $_NotifyUrl;
   protected $_Url;
   
   protected function __construct() {}

   public static function Authenticate($ProviderDomain, $ConsumerKey, $ConsumerSecret, $RequestToken, $UserEmail, $UserName, $UserID, $SSL = FALSE, $ExtensionArguments = array()) {
      preg_match('/^(?:http|https)?(?::\/\/)?(.*)$/',$ProviderDomain,$Matches);
      $ProviderDomain = trim($Matches[1],'/');
   
      $ConsumerNotifyUrl = sprintf('%s://%s%s',
         $SSL ? "https" : "http",
         $ProviderDomain,
         '/entry/auth/handshake'
      );
      
      $VanillaConnect = new VanillaConnect();
      $OAuthConsumer = new OAuthConsumer($ConsumerKey, $ConsumerSecret);
      $ConsumerParams = array_merge($ExtensionArguments, array("email" => $UserEmail, "name" => $UserName, "uid" => $UserID));
      $OAuthRequest = OAuthRequest::from_consumer_and_token($OAuthConsumer, $RequestToken, "GET", $ConsumerNotifyUrl, $ConsumerParams);
      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $OAuthRequest->sign_request($SignatureMethod, $OAuthConsumer, $RequestToken);
      $VanillaConnect->_Request = $OAuthRequest;
      
      $VanillaConnect->_Url = $VanillaConnect->_Request->to_url();
      return $VanillaConnect;
   }
   
   public static function DeAuthenticate($ProviderDomain, $ConsumerKey, $ConsumerSecret, $SSL = FALSE, $ExtensionArguments = array()) {
      preg_match('/^(?:http|https)?(?::\/\/)?(.*)$/',$ProviderDomain,$Matches);
      $ProviderDomain = trim($Matches[1],'/');
   
      $ConsumerNotifyUrl = sprintf('%s://%s%s',
         $SSL ? "https" : "http",
         $ProviderDomain,
         '/entry/leave/handshake'
      );
      
      $VanillaConnect = new VanillaConnect();
      $OAuthConsumer = new OAuthConsumer($ConsumerKey, $ConsumerSecret);
      $ConsumerParams = array_merge($ExtensionArguments, array('action' => 'logout'));
      $OAuthRequest = OAuthRequest::from_consumer_and_token($OAuthConsumer, null, "GET", $ConsumerNotifyUrl, $ConsumerParams);
      $SignatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
      $OAuthRequest->sign_request($SignatureMethod, $OAuthConsumer, null);
      $VanillaConnect->_Request = $OAuthRequest;
      
      $VanillaConnect->_Url = $VanillaConnect->_Request->to_url();
      return $VanillaConnect;
   }
   
   public function Script() {
      $Url = $this->_Url;
      
      $ScriptResult = <<<SCRIPTFRAGMENT
      <script type="text/javascript">
         (function(){
            AddInclude = function(ElementID, SrcURL) {
               var DomHead = document.getElementsByTagName("head")[0];
               NewInclude = document.createElement('script');
               NewInclude.id = ElementID;
               NewInclude.type = "text/javascript";
               NewInclude.src = SrcURL;
               DomHead.appendChild(NewInclude);
            }

            if (window.onload) { window.prevonload = window.onload; }
            window.onload = function() {
               if (window.prevonload) window.prevonload();
               AddInclude("vanillaconnect", "{$Url}&mode=javascript");
            }
         })();
      </script>
SCRIPTFRAGMENT;

      return $ScriptResult;
   }
   
   public function Url($UrlName) {
      $Url = $this->_Url;
      return '<a href="'.htmlspecialchars($Url).'&mode=direct" alt="'.$UrlName.'">'.$UrlName.'</a>';
   }

   public function Image() {
      $Url = $this->_Url;
      return '<img src="'.htmlspecialchars($Url).'&mode=image" alt="" />';
   }
   
/*
   public function Cookie() {
      setcookie();
   }
*/
   
   public function  Redirect() {
      $Url = $this->_Request->to_url();
      Header("Location: ".$Url."&mode=direct");
      exit();
   }
   
   public function GetUrl() {
      return $this->_Url;
   }

}
?>