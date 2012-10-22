<?php if (!defined('APPLICATION')) exit();
echo '<h1>', $this->Data('Title'), '</h1>';

function _WriteBuzz($Field, $Data, $Label = '') {
   if (!$Label) {
      $Label = StringBeginsWith($Field, 'Count', TRUE, TRUE);
      $Label = T(Gdn_Form::LabelCode($Label));
   }
   
   $Number = Gdn_Format::BigNumber(GetValue($Field, $Data), 'html');
   
   echo '<span class="Buzz">',
      '<span class="Buzz-Number">'.$Number.'</span>',
      '<span class="Buzz-Label">'.$Label.'</span>',
      '</span>';
}

_WriteBuzz('CountUsers', $this->Data);
_WriteBuzz('CountDiscussions', $this->Data);
_WriteBuzz('CountComments', $this->Data);
_WriteBuzz('CountContributors', $this->Data);