<?php if (!defined('APPLICATION')) exit();
$hasBanner = val('Banner', $this->group);
?>
<div class="Group-Header<?php echo $hasBanner ? ' HasBanner' : ' NoBanner'; ?>">
  <?php
  WriteGroupBanner($this->group);
  WriteGroupIcon($this->group, 'Group-Icon Group-Icon-Big');
  if ($this->showOptions && $options = getGroupOptions($this->group)) {
    writeGroupOptions($options);
  }
  if ($this->showButtons) {
  $buttons = getGroupButtons($this->group);

  if ($buttons) { ?>
    <div class="Buttons Button-Controls Group-Buttons">
      <?php foreach ($buttons as $button) { ?>
        <a class="Button <?php echo val('cssClass', $button); ?>" href="<?php echo val('url', $button) ?>"
           role="button"><?php echo val('text', $button); ?></a>
      <?php } ?>
    </div>
  <?php } ?>
  <div class="Group-Header-Info">
    <h1 class="Group-Title"><?php echo htmlspecialchars(val('Name', $this->group)); ?></h1>
    <?php
    if ($this->showDescription) { ?>
      <div class="Group-Description">
        <?php echo Gdn_Format::To(val('Description', $this->group), val('Format', $this->group)); ?>
      </div>
    <?php }
    if ($this->showMeta) {
      $meta = new GroupMetaModule($this->group);
      echo $meta;
    } ?>
  </div>
</div>
<?php } ?>
