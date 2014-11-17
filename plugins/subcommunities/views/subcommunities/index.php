<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php echo $this->form->Open(array('action' => Url('/minisites'))); ?>
<div class="Wrap">
    <?php
    echo $this->form->Errors();

    echo '<div>', T('Search minisites.', 'Search for sites by the name or slug.'), '</div>';

    echo '<div>';
    echo $this->form->TextBox('search');
    echo ' ', $this->form->Button(T('Go'), ['name' => 'go']);
    echo '</div>';

    ?>
</div>
<div class="Wrap">
    <?php echo Anchor(sprintf(T('Add %s'), T('Subcommunity')), '/subcommunities/add', 'Popup SmallButton'); ?>
</div>

<div id="sites-wrap">
    <?php
    require $this->FetchViewLocation('table');
    ?>
</div>
