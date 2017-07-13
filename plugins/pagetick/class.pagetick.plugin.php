<?php if (!defined('APPLICATION')) exit();

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
         ->Column('CountImgViews', 'int', '0')
         ->Set();
   }

   public function tick($mobile = false) {
      $date = new DateTime('today midnight UTC');
      $sql = "insert GDN_Tick (TickID, CountViews, CountMobileViews)
         values (:TickID, :CountViews, :CountMobileViews)
         on duplicate key update CountViews = CountViews + :CountViews1, CountMobileViews = CountMobileViews + :CountMobileViews1";

      $mobile_views = $mobile ? 1 : 0;

      Gdn::Database()->Query($sql, [
          ':TickID' => $date->format(DateTime::W3C),
          ':CountViews' => 1,
          ':CountMobileViews' => $mobile_views,
          ':CountViews1' => 1,
          ':CountMobileViews1' => $mobile_views]);
   }

   public function tickColumn($column = 'CountViews') {
      $date = new DateTime('today midnight UTC');
      $sql = "insert GDN_Tick (TickID, $column)
         values (:TickID, :CountViews)
         on duplicate key update $column = $column + :CountViews1";

      Gdn::Database()->Query($sql, [
          ':TickID' => $date->format(DateTime::W3C),
          ':CountViews' => 1,
          ':CountViews1' => 1]);
   }


   /// Event Handlers ///

   public function Base_AfterRenderAsset_Handler($Sender, $Args) {
      if ($Args['AssetName'] != 'Head')
         return;

      $script = <<<EOT
<script type="text/javascript">
  (function() {
    var a = document.createElement('script'); a.type = 'text/javascript'; a.async = true;
    a.src = '/plugins/pagetick/js/track.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(a, s);
  })();

</script>
EOT;

      echo $script;
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function Base_render_before($sender) {
      $sender->AddJsFile('pagetick.js', 'plugins/pagetick', ['hint' => 'inline', 'sort' => 1000]);
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function RootController_pagetick_create($sender) {
      if (!$sender->Request->IsPostBack()) {
         throw ForbiddenException('POST');
      }

      $this->tick(IsMobile());
      $sender->setData('ok', true);
      $sender->render();
   }

   public function UtilityController_bb_create($sender) {
      $this->tickColumn('CountImgViews');

      if (!headers_sent()) {
         header("Content-type:  image/gif");
         header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
         header("Cache-Control: no-cache");
         header("Cache-Control: must-revalidate");
      }
      require_once __DIR__.'/Pxgif.php';
      echo Pxgif::httpStr(200, true);
   }
}
