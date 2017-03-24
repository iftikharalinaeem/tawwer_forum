<?php if (!defined('APPLICATION')) exit();?>
<h1><?php echo t('Trusted Content Sources'); ?></h1>
<?php
/** @var Gdn_Form $form */
$form = $this->Form;
echo $form->open();
echo $form->errors();
?>
</li>
<li class="form-group">
    <?php echo $this->Form->toggle('Garden.HTML.FilterContentSources', 'Allow Embedded Content From Approved Domains Only.'); ?>
</li>
<li class="form-group hidden" id="trustedContentSources">
    <div class="label-wrap">
        <?php echo $this->Form->label('Approved Domains', 'Garden.TrustedContentSources'); ?>
        <div class="info">
            <p>
                <?php
                echo t(
                    'You can specify a whitelist of trusted domains.',
                    'You can specify a whitelist of trusted domains (ex. yourdomain.com) that are safe for embedding.'
                );
                ?>
            </p>
            <p><strong><?php echo t('Note'); ?>:</strong> <?php echo t('Specify one domain per line.'); ?></p>
        </div>
    </div>
    <div class="input-wrap">
        <?php echo $this->Form->textBox('Garden.TrustedContentSources', ['MultiLine' => true]); ?>
    </div>
</li>
<?php echo $form->close('Save'); ?>
