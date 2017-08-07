<?php
echo '<h1>'.$this->data('Title').'</h1>';

function _WriteBuzz($field, $data, $label = '') {
    if (!$label) {
        $label = stringBeginsWith($field, 'Count', TRUE, TRUE);
        $label = t(Gdn_Form::labelCode($label));
    }

    $number = Gdn_Format::bigNumber(getValue($field, $data), 'html');

    echo '<span class="Buzz">',
        '<span class="Buzz-Number">'.$number.'</span>',
        '<span class="Buzz-Label">'.$label.'</span>',
    '</span>';
}

_WriteBuzz('CountUsers', $this->Data);
_WriteBuzz('CountDiscussions', $this->Data);
_WriteBuzz('CountComments', $this->Data);
_WriteBuzz('CountContributors', $this->Data);
