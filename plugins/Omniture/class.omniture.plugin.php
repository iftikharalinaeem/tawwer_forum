<?php if (!defined('APPLICATION')) exit();

/**
 * Made for YoungEntrepreneurs. Not clear if this is universally usable.
 */
$PluginInfo['Omniture'] = array(
   'Name' => 'Omniture Analytics',
   'Description' => 'Adds Omniture analytics tracking script to the forum.',
   'Version' => '1.0',
   'SettingsUrl' => 'dashboard/settings/omniture',
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com'
);

class OmniturePlugin implements Gdn_IPlugin {
   /**
    * Includes Omniture analytics code on all pages.
    * 
    * @param Gdn_Controller $Sender
    */
   public function Base_AfterRenderAsset_Handler($Sender, $Args) {
      if ($Args['AssetName'] != 'Head')
         return;

      $ContentID = "";
      switch (strtolower($Sender->ControllerName)) {
         case 'profilecontroller':
            $ContentID = GetValue('UserID', $this->User);
            break;
         case 'discussioncontroller':
         case 'commentcontroller':
            $ContentID = GetValue('DiscussionID', $Sender->Data('Discussion'));
            break;
         case 'categoriescontroller':
         case 'discussionscontroller':
            $ContentID = GetValue('CategoryID', $Sender->Data('Category'));
            break;
      }

      $Breadcrumbs = '';
      if (is_array($Sender->Data("Breadcrumbs"))) {
         foreach ($Sender->Data("Breadcrumbs") as $Crumb) {
            $Breadcrumbs .= ':'.GetValue('Name', $Crumb);
         }
      }

      echo '
      <script src="/scripts/omniture/s_code.js" type="text/javascript" charset="utf-8"></script>
      <script type="text/javascript">//<![CDATA[
      if(s){
         s.pageName = "forum : '.$Sender->Data('Title').'";    //Pages
         s.server  = "'.C('Garden.Domain').'";  //Server/Domain Identification
         s.eVar3 = "vanillaforums";   //Application Server
         s.eVar4 = "web";   //Platform
         s.eVar6 = "forums";   //Content Type
         s.eVar7 = "'.$ContentID.'";   //Content ID
         s.eVar9 = "'.$Sender->Data('Title').'"; //ogtitle
         s.eVar74 = window.top.location.href;
         s.hier1 = "forum'.$Breadcrumbs.':'.$Sender->Data('Title').'";   //Page Hierarchy
         s.t() //fires code to omniture
      }
      //]]></script>';
   }
   /**
    * Settings page.
    *
    * @param $Sender
    */
   /*public function SettingsController_Omniture_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Omniture Settings');
      $Sender->AddSideMenu('dashboard/settings/plugins');

      $Cf = new ConfigurationModule($Sender);
      $Cf->Initialize(array(
         'Plugins.Omniture.Example' => array('Description' => $KeyDesc)
      ));
      $Cf->RenderAll();
   }*/
   
   public function Setup() {
      // No setup required.
   }
}