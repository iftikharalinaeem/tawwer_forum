<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title'); ?></h1>

<?php echo $this->form->Open(array('action' => Url('/multisites'))); ?>
<div class="Wrap">
    <?php
    echo $this->form->Errors();

    echo '<div>', T('Search sites.', 'Search for sites by the name of the site or its url.'), '</div>';

    echo '<div>';
    echo $this->form->TextBox('search');
    echo ' ', $this->form->Button(T('Go'), ['name' => 'go']);
    echo '</div>';

    ?>
</div>
<div class="Wrap">
    <?php echo Anchor(T('Add Site'), '/multisites/add', 'Popup SmallButton'); ?>
</div>

<div id="multisites-wrap">
<?php
require $this->FetchViewLocation('table');
?>
</div>

<div class="Wrap">
    <div class="Info2">
        The sites are synchronized with the hub roughly every 20 minutes.
        <?php
        echo Anchor(T('Sync Now'), '/multisites/syncnode.json', 'SmallButton Hijack');
        ?>
    </div>
</div>