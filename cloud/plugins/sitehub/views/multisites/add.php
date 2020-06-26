<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open(['action' => url('/multisites')]), $this->Form->errors();
?>
<ul>
    <?php
    echo $this->Form->simple([
        'Name' => ['Description' => 'Enter a friendly name for the site.'],
        'Slug' => [],
    ]);
    ?>
</ul>

<?php
echo $this->Form->close('Save');
