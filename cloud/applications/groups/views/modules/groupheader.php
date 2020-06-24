<?php if (!defined('APPLICATION')) exit();

$hasBanner = val('Banner', $this->group);
$dataDriven = \Gdn::themeFeatures()->useDataDrivenTheme();
?>
<div class="Group-Header<?php echo $hasBanner ? ' HasBanner' : ' NoBanner'; ?>">
    <?php
    writeGroupBanner($this->group);
    writeGroupIcon($this->group, 'Group-Icon Group-Icon-Big', true);

    ob_start();
    if ($this->showOptions && $options = getGroupOptions($this->group)) {
        writeGroupOptionsButton($options);
    } else {
        echo "";
    }
    $groupOptions = ob_get_clean();

    if (!$dataDriven) {
        echo $groupOptions;
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
            echo "<div class='Group-Header-Actions'>" . $buttonGroup . $groupOptions. "</div>";
        } ?>
    </div>
<?php } ?>
