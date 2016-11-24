<h1><?php echo $this->data('Title'); ?></h1>

<div class="PageInfo">
    <h2><?php echo t('Create Single Sign On integration using the JSON Web Token!'); ?></h2>
    <p>
        <?php
        echo t('<p>Provide information for connecting with your Authentication provider.</p>');
        echo t('<p>Have your authenticating server send users to this URI to connect to the forum: <code>'.$this->data('ConnectURL').'</code></p>');
        ?>
    </p>
</div>
<?php

echo $this->Form->open(),
$this->Form->errors();

echo $this->Form->simple($this->data('_Form'));


if ($this->data('jwt')) :
echo '<div class="alert alert-info padded">Below is a sample JWT for test purposes. To check the validity of your Secret and see the payload go to <a href="https://jwt.io">jwt.io</a></div>';
echo '<div class="JWT-sample">';
    echo $this->data('jwt');
echo '</div>';
endif;

echo '<div class="js-modal-footer form-footer buttons">';
echo $this->Form->button('Generate Secret', ['Name' => 'Generate', 'class' => 'btn btn-secondary js-generate']);
echo $this->Form->button('Save');
echo '</div>';


echo $this->Form->close();
