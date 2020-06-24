<?php if (!defined('APPLICATION')) exit(); ?>
<?php echo heading($this->data('Title'), t('Add Connection'), '/samlsso/add'); ?>
<div class="alert alert-warning padded">
<?php echo t('These settings are for advanced users. Make sure you have some knowledge of SAML before proceeding.'); ?>
</div>
<div class="table-wrap">
    <table class="table-data">
        <thead>
        <tr>
            <th><?php echo t('Client ID'); ?></th>
            <th><?php echo t('Site Name'); ?></th>
            <th class="column-lg"><?php echo t('SignIn URL'); ?></th>
            <th class="column-sm"><?php echo t('Active'); ?></th>
            <th class="options column-sm"><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $Alt = false;
        foreach ($this->data('Providers') as $provider) {
            ?>
            <tr id="provider_<?php echo $provider['AuthenticationKey'] ?>">
                <td>
                    <?php echo $provider['AuthenticationKey']; ?>
                </td>
                <td>
                    <?php echo $provider['Name']; ?>
                </td>
                <td>
                    <?php echo $provider['SignInUrl']; ?>
                </td>
                <td class="toggle-container">
                <?php
                if ($provider['Active']) {
                    $state = 'on';
                    $url = '/samlsso/state/'.$provider['AuthenticationKey'].'/disabled';
                } else {
                    $state = 'off';
                    $url = '/samlsso/state/'.$provider['AuthenticationKey'].'/active';
                }
                echo wrap(
                    anchor('<div class="toggle-well"></div><div class="toggle-slider"></div>', $url, 'Hijack'),
                    'span',
                    ['class' => "toggle-wrap toggle-wrap-$state"]
                );
                ?>
                </td>
                <td>
                    <div class="btn-group">
                    <?php
                        echo anchor(dashboardSymbol('edit'), "/samlsso/edit/{$provider['AuthenticationKey']}", 'btn btn-icon', ['aria-label' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), "/samlsso/delete/{$provider['AuthenticationKey']}", 'js-modal-confirm btn btn-icon', ['aria-label' => t('Delete')]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
