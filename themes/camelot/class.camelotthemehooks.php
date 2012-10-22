<?php if (!defined('APPLICATION')) exit();

class CamelotThemeHooks implements Gdn_IPlugin {
	
	public function Base_Render_Before($Sender) {
		Gdn::Locale()->SetTranslation('Activity.Delete', '×');
		Gdn::Locale()->SetTranslation('Draft.Delete', '×');
		Gdn::Locale()->SetTranslation('All Conversations', 'Inbox');
		Gdn::Locale()->SetTranslation('Comments', 'Comments');
		Gdn::Locale()->SetTranslation('Discussions', 'Discussions');
		Gdn::Locale()->SetTranslation('All Discussions', 'All Discussions');
	}
	
   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
   
   public static function FacebookButton($ImgSrc = '') {
      if (!$ImgSrc)
         $ImgSrc = Asset('/plugins/Facebook/design/facebook-login.png');
      $ImgAlt = T('Login with Facebook');
      
      try {
         $Fb = Gdn::PluginManager()->GetPluginInstance('FacebookPlugin');
         $SigninHref = $Fb->AuthorizeUri();
         $PopupSigninHref = $Fb->AuthorizeUri('display=popup');
         return "<a href=\"$SigninHref\" class=\"PopupWindow FacebookButton\" title=\"$ImgAlt\" popupHref=\"$PopupSigninHref\" popupHeight=\"326\" popupWidth=\"627\" ><img src=\"$ImgSrc\" alt=\"$ImgAlt\" align=\"bottom\" /></a>";
      } catch (Exception $Ex) {
         return '';
      }
   }
	
}