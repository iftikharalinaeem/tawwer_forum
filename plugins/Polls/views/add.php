<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CancelUrl = '/vanilla/discussions';
if (C('Vanilla.Categories.Use') && is_object($this->Category))
   $CancelUrl = '/vanilla/categories/'.urlencode($this->Category->UrlCode);

?>
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

         echo 'add poll options here';

         echo '<div class="Buttons">';
            echo $this->Form->Button('Save Poll', array('class' => 'Button PollButton'));
            echo Anchor(T('Cancel'), $CancelUrl, 'Cancel');
         echo '</div>';
         echo $this->Form->Close();
      echo '</div>';
   ?>
</div>
