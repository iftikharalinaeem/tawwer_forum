<?php
if (!defined('APPLICATION')) {
    exit();
}

$Session = Gdn::Session();
require_once($this->fetchViewLocation('helper_functions', '', 'plugins/vfoptions'));
?>

<h1><?php echo $this->data('Title') ?></h1>
<div class="toolbar full-border">
   <div class="btn-group">
      <?php
      echo anchor(
          sprintf(t('All'), wrap($this->data('PluginCount'))),
          'settings/addons/all',
          'addons-all btn btn-secondary '.($this->Filter === 'all' ? 'active' : '')
      );
      echo anchor(
          sprintf(t('Enabled'), wrap($this->data('EnabledCount'))),
          'settings/addons/enabled', 'addons-all btn btn-secondary '.($this->Filter == 'enabled' ? 'active' : '')
      );
      echo anchor(
          sprintf(t('Disabled'), wrap($this->data('DisabledCount'))),
          'settings/addons/disabled',
          'addons-all btn btn-secondary '.($this->Filter == 'disabled' ? 'active' : '')
      );
      ?>
   </div>
</div>

<!--<div class="Info">-->
<!--   These are features you can add to your site by enabling them.-->
<!--   We are always adding new features, so check back from time to time.-->
<!--</div>-->

<?php echo $this->Form->errors();

$availableAddons = $this->data('Addons');

$this->EventArguments['AvailableAddons'] = &$availableAddons;
$this->fireAs('SettingsController');
$this->fireEvent('BeforeAddonList');

?>
<ul class="media-list addon-list">
<?php foreach ($availableAddons as $Key => $info) :
   $slug = Gdn_Format::url(strtolower($Key));
   // Apply filters
   if ($this->Filter == 'enabled' && !val('Enabled', $info)) {
      continue;
   }
   if ($this->Filter == 'disabled' && val('Enabled', $info)) {
      continue;
   }
   writeAddonMediaItem($Key, $info, val('Enabled', $info));

endforeach; ?>

</ul>
