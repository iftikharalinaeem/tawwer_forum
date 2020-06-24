<h1><?php echo t($this->data('Title')); ?></h1>

<div class="alert alert-warning padded">
    <strong><?php echo t('Make sure that your reverse proxy is properly configured and that the X-Proxy-For header matches the Reverse Proxy URL perfectly before attempting to test/save the configuration.') ?></strong>
</div>

<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open([
        'id' => 'reverse-proxy-settings-form',
        'data-proxy-validate-path' => '/reverseproxysupport/validate',
        'data-validation-id' => c('ReverseProxySupport.ValidationID'),
]);
echo $form->errors();
echo $form->simple($this->data('_FormInputDefinition'));
?>
<div class="js-modal-footer form-footer">
    <button type="button" id="reverse-proxy-test-config" class="btn btn-secondary"><?php echo t('Test configuration') ?></button>
    <button type="submit" id="reverse-proxy-save-config" disabled name="Save" class="btn btn-primary" value="Save"><?php echo t('Save') ?></button>
</div>
<?php
echo $form->close();
