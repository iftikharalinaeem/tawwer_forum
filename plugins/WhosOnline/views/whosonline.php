<?php if (!defined('APPLICATION')) {
    exit();
}
echo $this->Form->open();
echo $this->Form->errors();
?>
    <h1><?php echo t("Who's Online"); ?></h1>
    <div class="Info"><?php echo t('Where should the plugin be shown?'); ?></div>
    <table class="AltRows">
        <thead>
        <tr>
            <th><?php echo t('Sections'); ?></th>
            <th class="Alt"><?php echo t('Description'); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <th><?php
                echo $this->Form->radio('WhosOnline.Location.Show', "Every", ['value' => 'every', 'selected' => 'selected']);
                ?></th>
            <td class="Alt"><?php echo t("This will show the panel on every page."); ?></td>
        </tr>
        <tr>
            <th><?php
                echo $this->Form->radio('WhosOnline.Location.Show', "Discussion", ['value' => "discussion"]);
                ?></th>
            <td class="Alt"><?php echo t("This show the plugin on only selected discussion pages"); ?></td>
        </tr>
        </tbody>
    </table>
    <table class="AltRows">
        <tbody>
        <tr>
            <th><?php
                echo $this->Form->checkbox('WhosOnline.Hide', "Hide for non members of the site");
                ?></th>
        </tr>
        </tbody>
    </table>

<?php echo $this->Form->close('Save');
