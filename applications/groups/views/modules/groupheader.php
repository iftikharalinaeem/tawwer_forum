<?php if (!defined('APPLICATION')) exit();

$hasBanner = val('Banner', $this->group);
?>
<div class="Group-Header<?php echo $hasBanner ? ' HasBanner' : ' NoBanner'; ?>">
    <?php
    writeGroupBanner($this->group);
    writeGroupIcon($this->group, 'Group-Icon Group-Icon-Big', true);
    if ($this->showOptions && $options = getGroupOptions($this->group)) {
        writeGroupOptionsButton($options);
    }
    if ($this->showButtons) {
    $buttons = getGroupButtons($this->group);


    $buttonGroup = "";
    if ($buttons) {
        foreach ($buttons as $button) {
            $buttonGroup .= "<a class='Button " . val('cssClass', $button) . "' href='" . val('url', $button) . "' role='button'>" . val('text', $button) . "</a>";
        }
        if (!empty($buttonGroup)) {
            $buttonGroup = "<div class='Buttons Button-Controls Group-Buttons'>" . $buttonGroup . "</div>";
        }
    }

    $dataDriven = \Gdn::themeFeatures()->useDataDrivenTheme();

    if (!$dataDriven) {
        echo $buttonGroup;
    } ?>
    <div class="Group-Header-Info">
        <h1 class="Group-Title"><?php echo anchor(htmlspecialchars(val('Name', $this->group)), groupUrl($this->group)); ?></h1>
        <?php
        if ($this->showDescription) { ?>
            <div class="Group-Description userContent">
                <?php echo Gdn_Format::to(val('Description', $this->group), val('Format', $this->group)); ?>
            </div>
        <?php }
        if ($this->showMeta) {
            $meta = new GroupMetaModule($this->group);
            echo $meta;
        } ?>
    </div>
    <?php if ($dataDriven) {
        echo $buttonGroup;
    } ?>
</div>
<?php } ?>
