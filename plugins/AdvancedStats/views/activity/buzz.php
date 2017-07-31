<?php
echo '<h1>'.$this->Data('Title').'</h1>';

function _WriteBuzz($field, $data, $label = '') {
    if (!$label) {
        $label = StringBeginsWith($field, 'Count', TRUE, TRUE);
        $label = T(Gdn_Form::LabelCode($label));
    }

    $number = Gdn_Format::BigNumber(GetValue($field, $data), 'html');

    echo '<span class="Buzz">',
        '<span class="Buzz-Number">'.$number.'</span>',
        '<span class="Buzz-Label">'.$label.'</span>',
    '</span>';
}

_WriteBuzz('CountUsers', $this->Data);
_WriteBuzz('CountDiscussions', $this->Data);
_WriteBuzz('CountComments', $this->Data);
_WriteBuzz('CountContributors', $this->Data);
