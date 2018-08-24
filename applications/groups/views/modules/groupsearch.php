<?php if (!defined('APPLICATION')) exit();
$title = $this->getTitle();
$form = new Gdn_Form();
?>
<div class="<?php echo $this->getCssClass(); ?>" role="search">
    <?php
        echo $form->open(['action' => url('/groups/browse/search'), 'method' => 'get']);
    ?>
    <div class="groupSearch-search">
        <?php echo $form->textBox('Search', [
                'class' => 'InputBox BigInput groupSearch-text js-search-groups',
            'placeholder' => $title,
            'title' => $title,
            'aria-label' => $title,
            'role',
            'value' => Gdn::controller()->data('GroupSearch', '')
        ]); ?>
        <button type="submit" class="Button groupSearch-button">
            <?php echo $this->getButtonContents(); ?>
        </button>
    </div>
    <?php echo $form->close(); ?>
</div>
