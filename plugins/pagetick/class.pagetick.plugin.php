<?php if (!defined('APPLICATION')) exit();

$PluginInfo['pagetick'] = array(
    'Name' => 'Pagetick',
    'Description' => 'Testing alternate pageview tracking.',
    'Version' => '1.0.0-beta2',
    'RequiredApplications' => array('Vanilla' => '2.1a'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'HasLocale' => FALSE,
    'MobileFriendly' => TRUE
);

class PageTickPlugin extends Gdn_Plugin {
   /// Methods ///

   public function setup() {
      $this->structure();
   }

   public function structure() {
      Gdn::Structure()
         ->Table('Tick')
         ->Column('TickID', 'datetime', FALSE, 'primary')
         ->Column('CountViews', 'int', '0')
         ->Column('CountMobileViews', 'int', '0')
         ->Set();
   }

   public function tick($mobile = false) {
      $date = new DateTime('today midnight UTC');
      $sql = "insert GDN_Tick (TickID, CountViews, CountMobileViews)
         values (:TickID, :CountViews, :CountMobileViews)
         on duplicate key update CountViews = CountViews + :CountViews1, CountMobileViews = CountMobileViews + :CountMobileViews1";

      $mobile_views = $mobile ? 1 : 0;

      Gdn::Database()->Query($sql, array(
          ':TickID' => $date->format(DateTime::W3C),
          ':CountViews' => 1,
          ':CountMobileViews' => $mobile_views,
          ':CountViews1' => 1,
          ':CountMobileViews1' => $mobile_views));
   }


   /// Event Handlers ///

   /**
    * @param Gdn_Controller $sender
    */
   public function Base_Render_Before($sender) {
      $sender->AddJsFile('pagetick.js', 'plugins/pagetick', array('hint' => 'inline', 'sort' => 1000));
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function RootController_Pagetick_Create($sender) {
      if (!$sender->Request->IsPostBack()) {
         throw ForbiddenException('POST');
      }

      $this->tick(IsMobile());
      $sender->setData('ok', true);
      $sender->render();
   }
}
