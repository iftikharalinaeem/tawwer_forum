<?php
if (!defined('APPLICATION'))
   exit();
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if (C('Vanilla.Categories.Use') && is_object($this->Category))
   $CancelUrl = '/vanilla/categories/' . urlencode($this->Category->UrlCode);
?>
<script type="text/javascript">
   jQuery(document).ready(function($){
      $('.PollOption').duplicate({addButton:'.AddPollOption'});
   });
</script>
<style type="text/css">
   .PollOption {
      list-style-type: decimal;
      margin: 10px 45px;
      padding: 0;
      line-height: 1;
      font-size: 26px;
      font-weight: bold;
      font-family: arial;
      color: #666;
   }

   .PollOption input.InputBox {
      margin: 0;
      width: 90%;
      vertical-align: bottom;
   }
</style>
<div id="PollForm" class="DiscussionForm FormTitleWrapper">
   <?php
   if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
      echo Wrap($this->Data('Title'), 'h1');

   echo '<div class="FormWrapper">';
   echo $this->Form->Open();
   echo $this->Form->Errors();
   echo '<div class="P">';
   echo $this->Form->Label('Poll Question', 'Name');
   echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
   echo '</div>';

   if ($this->ShowCategorySelector === TRUE) {
      echo '<div class="P">';
      echo '<div class="Category">';
      echo $this->Form->Label('Category', 'CategoryID'), ' ';
      echo $this->Form->CategoryDropDown('CategoryID', array('Value' => GetValue('CategoryID', $this->Category)));
      echo '</div>';
      echo '</div>';
   }

   echo $this->Form->Label('Poll Options', 'PollOption[]');
   echo '<ol class="PollOptions">';
   echo '<li class="PollOption">' . $this->Form->TextBox('PollOption[]', array('class' => 'InputBox BigInput', 'placeholder' => 'Add an Option...')) . '</li>';
   echo '</ol>';
   echo Anchor(T('Add another poll option ...'), '#', array('class' => 'AddPollOption'));

   echo '<div class="Buttons">';
   echo $this->Form->Button('Save Poll', array('class' => 'Button PollButton'));
   echo Anchor(T('Cancel'), $CancelUrl, 'Cancel');
   echo '</div>';
   echo $this->Form->Close();
   echo '</div>';
   ?>
</div>
