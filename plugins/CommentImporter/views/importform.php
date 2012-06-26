<?php if (!defined('APPLICATION')) exit; ?>
<style>
   .RadioDiv {
      margin-top: 10px;
   }
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open(array('enctype' => 'multipart/form-data'));
echo $this->Form->Errors();
?>
<ul>
   <li>
      <?php
      echo $this->Form->Label('Choose the type of comments you are importing.');
      echo $this->Form->RadioList('Type', $this->Data('AllowedTypes'));
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Upload your comments or give enter their url.');
      echo '<div class="RadioDiv">',
         $this->Form->Radio('IsUpload', T('If your file is small then just upload it here.'), array('Value' => TRUE)),
         '</div>';
      echo $this->Form->Input('FileUpload', 'file');
      
      echo '<div class="RadioDiv">',
         $this->Form->Radio('IsUpload', T('If your file is bigger than 20M then you must upload it to a public url.'), array('Value' => FALSE)),
         '</div>';
      echo $this->Form->TextBox('FileUrl', array('class' => 'InputBox BigInput'));
      ?>
   </li>
</ul>
<?php
echo $this->Form->Button('Start');
echo $this->Form->Close();
?>