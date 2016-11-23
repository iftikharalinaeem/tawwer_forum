<div class="toolbar-analytics toolbar flex-wrap" id="analytics_toolbar">
    <?php
    $form = new Gdn_Form('', 'bootstrap');

    echo '<div class="filter-content">'.$form->dropDown('cat01', $this->data('cat01'), ['IncludeNull' => 'All Categories']).'</div>';
    echo '<div class="btn-group">';
    foreach($this->data('Intervals') as $interval) {
        echo wrap(val('text', $interval), 'div',
            [
                'class' => 'js-analytics-interval btn btn-secondary',
                'data-seconds' => val('data-seconds', $interval),
                'data-interval' => strtolower(val('text', $interval))
            ]
        );
    }
    echo '</div>';
    echo '<div class="filter-date">'.$form->textBox('dates', ['class' => 'js-date-range form-control']).'</div>';
    ?>
</div>
