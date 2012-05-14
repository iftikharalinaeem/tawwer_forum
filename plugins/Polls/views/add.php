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
      if ($.fn.duplicate)
         $('.PollOption').duplicate({addButton:'.AddPollOption'});
   });
</script>
<div id="NewPollForm" class="NewPollForm DiscussionForm FormTitleWrapper">
   <?php
   if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
      echo Wrap($this->Data('Title'), 'h1');

   echo '<div class="FormWrapper">';
      echo $this->Form->Open();
      echo $this->Form->Errors();

      if ($this->ShowCategorySelector === TRUE) {
         echo '<div class="P">';
         echo '<div class="Category">';
         echo $this->Form->Label('Category', 'CategoryID'), ' ';
         echo $this->Form->CategoryDropDown('CategoryID', array('Value' => GetValue('CategoryID', $this->Category)));
         echo '</div>';
         echo '</div>';
      }

      echo '<div class="P">';
         echo $this->Form->Label('Poll Question', 'Name');
         echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
      echo '</div>';

      echo '<div class="P">';
         echo $this->Form->Label('Optional Description', 'Body');
         echo Wrap($this->Form->TextBox('Body', array('MultiLine' => TRUE, 'format' => $this->Data('Discussion.Format'))), 'div', array('class' => 'TextBoxWrapper'));
      echo '</div>';

      echo '<div class="P PostOptions" style="margin-bottom: 10px;">';
         echo $this->Form->CheckBox('Anonymous', T('Make this poll anonymous (user votes are not made public).'), array('value' => '1'));
      echo '</div>';

      echo '<div class="P">';
         echo $this->Form->Label('Poll Options', 'PollOption[]');
         echo '<ol class="PollOptions">';
            echo '<li class="PollOption">' . $this->Form->TextBox('PollOption[]', array('class' => 'InputBox BigInput NoIE', 'placeholder' => 'Add an Option...')) . '</li>';
            $PollOptions = GetValue('PollOption', $this->Form->FormValues());
            if (is_array($PollOptions)) {
               foreach ($PollOptions as $PollOption) {
                  $PollOption = trim(Gdn_Format::PlainText($PollOption));
                  if ($PollOption != '') 
                     echo '<li class="PollOption">' . $this->Form->TextBox('PollOption[]', array('value' => $PollOption, 'class' => 'InputBox BigInput NoIE', 'placeholder' => 'Add an Option...')) . '</li>';
               }
            }
         echo '</ol>';
         echo Anchor(T('Add another poll option ...'), '#', array('class' => 'AddPollOption'));
      echo '</div>';

      echo '<div class="Buttons">';
         echo $this->Form->Button('Save Poll', array('class' => 'Button PollButton'));
         echo Anchor(T('Cancel'), $CancelUrl, 'Cancel');
      echo '</div>';
      echo $this->Form->Close();
   echo '</div>';
   ?>
</div>
