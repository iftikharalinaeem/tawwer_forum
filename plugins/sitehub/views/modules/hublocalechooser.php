<?php
/* @var HubLocaleChooserModule $this */
?>
<span class="ToggleFlyout LocaleDropDown">
    <span title="<?php echo t('Select another community.') ?>"><?php echo htmlspecialchars($this->data('Current.'.$this->currentLabel)); ?></span>
    <?php echo sprite('SpFlyoutHandle', 'Arrow'); ?>
    <ul class="Flyout MenuItems" role="menu">
        <?php foreach ($this->data('Locales') as $item) {
            ?>
                <li role="presentation">
                    <a role="menuitem" class="dropdown-menu-link" tabindex="-1" href="<?php echo htmlspecialchars(val('Url', $item)); ?>"><?php echo htmlspecialchars(val($this->label, $item)); ?></a>
                </li>
            <?php
        } ?>
    </ul>
</span>