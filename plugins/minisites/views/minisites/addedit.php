<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->form->Open(), $this->form->Errors();
?>
<ul>
    <?php
    echo $this->form->Simple([
        'Name' => ['Description' => 'Enter a friendly name for the site.'],
        'Folder' => ['Description' => 'Enter a url-friendly folder name for the site.'],
        'CategoryID' => ['LabelCode' => 'Category', 'Control' => 'DropDown', 'Items' => $this->Data('Categories'), 'Options' => ['IncludeNull' => true]],
        'Locale' => ['Control' => 'DropDown', 'Items' => $this->Data('Locales'), 'Options' => ['IncludeNull' => true]]
    ]);
    ?>
</ul>

<?php
echo '<div class="Buttons">';
echo $this->form->Button('Save');
echo '</div>';

echo $this->form->Close();
