<div class="analytics-toolbar" id="analytics_toolbar">
    <div class="toolbar-controls">

    <?php
    $form = new Gdn_Form;

    echo $form->dropDown('cat01', $this->data('cat01'), ['IncludeNull' => 'All Categories']);
    echo ' ';
    echo $form->dropDown('intervals', $this->data('Intervals'), ['value' => 'daily']);
    ?>
    </div>
</div>