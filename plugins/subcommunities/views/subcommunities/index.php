<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php echo $this->form->open(array('action' => url('/subcommunities'))); ?>
<div class="Wrap">
    <?php
    echo $this->form->errors();

    echo '<div>', t('Search subcommunities.', 'Search for subcommunities by the name or slug.'), '</div>';

    echo '<div>';
    echo $this->form->textBox('search');
    echo ' ', $this->form->button(t('Go'), ['name' => 'go']);
    echo '</div>';

    ?>
</div>
<div class="Wrap">
    <?php echo anchor(sprintf(t('Add %s'), t('Subcommunity')), '/subcommunities/add', 'Popup SmallButton'); ?>
</div>

<div id="sites-wrap">
    <?php
    require $this->fetchViewLocation('table');
    ?>
</div>
