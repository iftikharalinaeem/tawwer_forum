<?php if (!defined('APPLICATION')) exit(); ?>
<div class="header-block">
    <h1><?php echo $this->Data('Title'); ?></h1>
    <?php echo anchor(t('Add Connection'), '/samlsso/add', 'btn btn-primary'); ?>
</div>

<div class="full-border alert alert-warning">
<?php
    echo 'Warning: These settings are for advanced users. Make sure you have some knowledge of SAML before proceeding.';
?>
</div>
<div class="table-wrap">
    <table border="0" cellpadding="0" cellspacing="0" class="table-data">
        <thead>
        <tr>
            <th><?php echo t('Client ID'); ?></th>
            <th><?php echo t('Site Name'); ?></th>
            <th><?php echo t('Authentication URL'); ?></th>
            <th class="options column-sm"><?php echo t('Options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $Alt = false;
        foreach ($this->data('Providers') as $provider) {
            ?>
            <tr>
                <td>
                    <?php echo $provider['AuthenticationKey']; ?>
                </td>
                <td>
                    <?php echo $provider['Name']; ?>
                </td>
                <td>
                    <?php echo $provider['AuthenticateUrl']; ?>
                </td>
                <td>
                    <div class="btn-group column-sm">
                    <?php
                        echo anchor(dashboardSymbol('edit'), "/samlsso/edit/{$provider['AuthenticationKey']}", 'btn btn-icon', ['aria-label' => t('Edit')]);
                        echo anchor(dashboardSymbol('delete'), "/samlsso/delete/{$provider['AuthenticationKey']}", 'js-modal-confirm js-hijack btn btn-icon', ['aria-label' => t('Delete')]);
                    ?>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<br/>
