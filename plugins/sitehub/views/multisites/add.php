<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open(['action' => Url('/multisites')]), $this->Form->Errors();
?>
<ul>
    <?php
    echo $this->Form->Simple([
        'Name' => ['Description' => 'Enter a friendly name for the site.'],
        'Slug' => [],
    ]);
    ?>
</ul>

<?php
echo '<div class="Buttons">';
echo $this->Form->Button('Save');
echo '</div>';

echo $this->Form->Close();