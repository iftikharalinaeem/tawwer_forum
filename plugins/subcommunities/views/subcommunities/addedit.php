<h1><?php echo $this->data('Title'); ?></h1>
<?php
$options = [];
if ($this->data('_HasFile')) {
    $options['enctype'] = 'multipart/form-data';
}

echo $this->form->open($options), $this->form->errors();
?>
<ul>
    <?php
    echo $this->form->simple($this->data('_Form'));
    ?>
</ul>

<?php
echo '<div class="Buttons">';
echo $this->form->button('Save');
echo '</div>';

echo $this->form->close();
