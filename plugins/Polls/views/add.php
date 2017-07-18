<?php if (!defined('APPLICATION')) exit();

$cancelUrl = $this->data('_CancelUrl');
if (!$cancelUrl) {
    $cancelUrl = '/discussions';
    if (c('Vanilla.Categories.Use') && is_object($this->Category)) {
        $cancelUrl = '/categories/'.urlencode($this->Category->UrlCode);
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
    $pollOptions = val('PollOption', $this->Form->formValues());
    if (is_array($pollOptions)) {
        foreach ($pollOptions as $pollOption) {
            $pollOption = trim(Gdn_Format::plainText($pollOption));
            if ($pollOption != '') {
                echo '<li class="PollOption">' . $this->Form->textBox('PollOption[]', ['value' => $pollOption, 'class' => 'InputBox BigInput']) . '</li>';
            }
        }
    }
    echo '</ol>';

    echo $this->Form->button('Add another poll option ...', ['class' => 'Button AddPollOption', 'type'=>'button']);

    echo '</div>';

    echo '<div class="Buttons">';
    echo $this->Form->button('Save Poll', ['class' => 'Button PollButton Primary']);
    echo ' '.anchor(t('Cancel'), $cancelUrl, 'Button Cancel');
    echo '</div>';
    echo $this->Form->close();
    echo '</div>';
    ?>
</div>
