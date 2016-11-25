<?php
echo heading(t('Localligator'));

$helpDescription = t('Localligator helps developers take a bite out of missing translation strings.').' '
    .t('This plugin should only ever be used locally.');
helpAsset(sprintf(t('About %s'), 'Localligator'), $helpDescription);

if (!$this->data('CanLoadResources')) : ?>
    <div class="alert alert-danger padded">
        Cannot locate tx-source files. Maybe you're missing some symlinks. Make sure the tx-source directory is
        symlinked into your locales directory. Or alternatively, symlink the entire locales repo into your webroot.
    </div>
    <?php return;
endif;

/** @var Gdn_Form $form */
$form = $this->form;
/** @var array $strings */
$strings = $this->data('StringsToAddField');

if (empty($strings)) : ?>

    <div class="hero">
        <div class="hero-content">
            <div class="hero-title">
                <?php echo t('Nice Work!'); ?>
            </div>
            <div class="hero-body">
                <?php echo t('Look at this beautiful empty screen. You\'re doing a terrific job.'); ?>
            </div>
        </div>
    </div>

<?php else :

    echo $form->open(); ?>
    <div class="form-group">
        <div class="label-wrap">
            <?php echo $form->label('Where do you want to add these translations?', 'Application'); ?>
            <div class="info"><?php echo t(''); ?></div>
        </div>
        <div class="input-wrap">
            <?php echo $form->dropdown('Application', $this->data('ApplicationField')); ?>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table-data js-tj">
            <thead>
            <tr>
                <th class="column-checkbox" data-tj-ignore="true">
                    <div class="checkbox-painted-wrapper checkbox">
                        <input class="js-check-all" type="checkbox" id="CheckAll">
                        <label for="CheckAll"><?php echo t('Check All'); ?></label>
                    </div>
                </th>
                <th class="column-lg" data-tj-main="true"><?php echo t('Translation Code'); ?></th>
                <th class="column-xl"><?php echo t('Default Translation'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($strings as $string) :
                $attr = [
                    'value' => val('value', $string),
                    'class' => val('class', $string),
                    'display' => 'after'
                ]; ?>
                <tr>
                    <td>
                        <?php
                        $class = 'checkbox-painted-wrapper';
                        echo wrap($form->checkBox('StringsToAdd[]', val('title', $string), $attr), 'div', ['class' => $class]);
                        ?>
                    </td>
                    <td><?php echo val('title', $string); ?></td>
                    <td><?php echo val('description', $string) ?></td>
                </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
    <?php echo $form->close('Save'); ?>

<?php endif;
