<?php if (!defined('APPLICATION')) exit();

$Session = Gdn::session();
$CancelUrl = $this->data('_CancelUrl');
if (!$CancelUrl) {
    $CancelUrl = '/discussions';
    if (c('Vanilla.Categories.Use') && is_object($this->Category)) {
        $CancelUrl = '/categories/'.urlencode($this->Category->UrlCode);
    }
}
?>

<div id="NewPollForm" class="NewPollForm DiscussionForm FormTitleWrapper">
    <?php
    if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
        echo wrap($this->data('Title'), 'h1 class="H"');
    }

    echo '<div class="FormWrapper">';
    echo $this->Form->open();
    echo $this->Form->errors();

    if ($this->ShowCategorySelector === true) {
        $options = [
            'Value' => val('CategoryID', $this->Category),
            'IncludeNull' => true,
            'PermFilter' => array('AllowedDiscussionTypes' => 'Poll'),
        ];
        echo '<div class="P">';
        echo '<div class="Category">';
        echo $this->Form->label('Category', 'CategoryID'), ' ';
        echo $this->Form->categoryDropDown('CategoryID', $options);
        echo '</div>';
        echo '</div>';
    }

    echo '<div class="P">';
    echo $this->Form->label('Poll Question', 'Name');
    echo wrap($this->Form->textBox('Name', ['maxlength' => 100, 'class' => 'InputBox BigInput']), 'div', ['class' => 'TextBoxWrapper']);
    echo '</div>';

    echo '<div class="P">';
    echo $this->Form->label('Optional Description', 'Body');
    echo wrap($this->Form->bodyBox(), 'div');
    echo '</div>';

    if (!$this->data('_AnonymousPolls')) {
        echo '<div class="P PostOptions" style="margin-bottom: 10px;">';
        echo $this->Form->checkBox('Anonymous', t('Make this poll anonymous (user votes are not made public).'), ['value' => '1']);
        echo '</div>';
    }

    echo '<div class="P">';
    echo $this->Form->label('Poll Options', 'PollOption[]');
    echo '<ol class="PollOptions">';
    echo '<li class="PollOption">' . $this->Form->textBox('PollOption[]', ['class' => 'InputBox BigInput NoIE']) . '</li>';
    $PollOptions = val('PollOption', $this->Form->formValues());
    if (is_array($PollOptions)) {
        foreach ($PollOptions as $PollOption) {
            $PollOption = trim(Gdn_Format::plainText($PollOption));
            if ($PollOption != '') {
                echo '<li class="PollOption">' . $this->Form->textBox('PollOption[]', ['value' => $PollOption, 'class' => 'InputBox BigInput']) . '</li>';
            }
        }
    }
    echo '</ol>';
    echo anchor(t('Add another poll option ...'), '#', ['class' => 'AddPollOption']);
    echo '</div>';

    echo '<div class="Buttons">';
    echo $this->Form->button('Save Poll', ['class' => 'Button PollButton Primary']);
    echo ' '.anchor(t('Cancel'), $CancelUrl, 'Button Cancel');
    echo '</div>';
    echo $this->Form->close();
    echo '</div>';
    ?>
</div>
