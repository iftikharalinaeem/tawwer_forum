<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .RadioDiv {
      margin-top: 10px;
   }
</style>
<h1><?php echo $this->data('Title'); ?></h1>
<?php
echo $this->Form->open(['enctype' => 'multipart/form-data']);
echo $this->Form->errors();
?>
<ul>
   <li>
      <?php
      echo $this->Form->label('Choose the type of comments you are importing.');
      echo $this->Form->radioList('Type', $this->data('AllowedTypes'));
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->label('Upload your comments or give enter their url.');
      echo '<div class="RadioDiv">',
         $this->Form->radio('IsUpload', t('If your file is small then just upload it here.'), ['Value' => TRUE]),
         '</div>';
      echo $this->Form->input('FileUpload', 'file');
      
      echo '<div class="RadioDiv">',
         $this->Form->radio('IsUpload', t('If your file is bigger than 20M then you must upload it to a public url.'), ['Value' => FALSE]),
         '</div>';
      echo $this->Form->textBox('FileUrl', ['class' => 'InputBox BigInput']);
      ?>
   </li>
</ul>
<?php
echo $this->Form->button('Start');
echo $this->Form->close();
?>