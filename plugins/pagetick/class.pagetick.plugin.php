<?php if (!defined('APPLICATION')) exit();

class PageTickPlugin extends Gdn_Plugin {
   /// Methods ///

   public function setup() {
      $this->structure();
   }

   public function structure() {
      Gdn::structure()
         ->table('Tick')
         ->column('TickID', 'datetime', FALSE, 'primary')
         ->column('CountViews', 'int', '0')
         ->column('CountMobileViews', 'int', '0')
         ->column('CountImgViews', 'int', '0')
         ->set();
   }

   public function tick($mobile = false) {
      $date = new DateTime('today midnight UTC');
      $sql = "insert GDN_Tick (TickID, CountViews, CountMobileViews)
         values (:TickID, :CountViews, :CountMobileViews)
         on duplicate key update CountViews = CountViews + :CountViews1, CountMobileViews = CountMobileViews + :CountMobileViews1";

      $mobile_views = $mobile ? 1 : 0;

      Gdn::database()->query($sql, [
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

      Gdn::database()->query($sql, [
          ':TickID' => $date->format(DateTime::W3C),
          ':CountViews' => 1,
          ':CountViews1' => 1]);
   }


   /// Event Handlers ///

   public function base_afterRenderAsset_handler($sender, $args) {
      if ($args['AssetName'] != 'Head')
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
   public function base_render_before($sender) {
      $sender->addJsFile('pagetick.js', 'plugins/pagetick', ['hint' => 'inline', 'sort' => 1000]);
   }

   /**
    * @param Gdn_Controller $sender
    */
   public function rootController_pagetick_create($sender) {
      if (!$sender->Request->isPostBack()) {
         throw forbiddenException('POST');
      }

      $this->tick(isMobile());
      $sender->setData('ok', true);
      $sender->render();
   }

   public function utilityController_bb_create($sender) {
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
