<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->form->open(), $this->form->errors();
?>
<ul>
    <?php
    echo $this->form->simple([
        'Name' => ['Description' => 'Enter a friendly name for the site.'],
        'Folder' => ['Description' => 'Enter a url-friendly folder name for the site.'],
        'CategoryID' => ['LabelCode' => 'Category', 'Control' => 'DropDown', 'Items' => $this->data('Categories'), 'Options' => ['IncludeNull' => true]],
        'Locale' => ['Control' => 'DropDown', 'Items' => $this->data('Locales'), 'Options' => ['IncludeNull' => true]],
        'IsDefault' => ['Control' => 'Checkbox', 'LabelCode' => 'Default']
    ]);
    ?>
</ul>

<?php
echo '<div class="Buttons">';
echo $this->form->button('Save');
echo '</div>';

echo $this->form->close();
