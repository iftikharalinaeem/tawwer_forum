<?php if (!defined('APPLICATION')) { exit(); } ?>
<h1><?php echo $this->data('Title') ?></h1>
<div>
<?php
/** @var Gdn_Form $form */
$form = $this->form;
echo $form->open();
echo $form->errors();
?>

<div class="P">
    <ul>
    <?php
    $discussionTags = $this->data('DiscussionTags');
    foreach ($this->data('TrackedTagIDs') as $trackedTagID) {
        echo '<li>'.$form->checkbox('trackedTag[]', htmlspecialchars($discussionTags[$trackedTagID]['FullName']), ['value' => $trackedTagID]).'</li>';
    }
    ?>
    </ul>
</div>

<?php
echo '<div class="Buttons Buttons-Confirm">',
    $form->button(t('Untrack')), ' ',
    $form->button(t('Cancel'), ['type' => 'button', 'class' => 'Button Close']),
    '</div>';
echo $form->close();
?>
</div>
