<?php if (!defined('APPLICATION')) exit();
include_once('reaction_functions.php');
$Session = Gdn::Session();
$CurrentReactionType = $this->Data('CurrentReactionType');

function ReactionFilterButton($Name, $Code, $CurrentReactionType) {
   return '<a href="'.Url('/bestof/'.strtolower($Code)).'" '
    .'class="ReactButton ReactButton-'.$Code.''.(strtolower($Code) == strtolower($CurrentReactionType)?' Active':'').'" '
    .'title="'.$Name.'">'
    .'<span class="ReactSprite React'.$Code.'"></span>'
    .'<span class="ReactLabel">'.$Name.'</span>'
    .'</a>';
}   
echo Wrap($this->Data('Title'), 'h1');
echo '<div class="ReactionFilters">';
   echo ReactionFilterButton('Everything', 'everything', $CurrentReactionType);
   $ReactionTypeData = $this->Data('ReactionTypeData');
   foreach ($ReactionTypeData as $Key => $ReactionType) {
      // decho($ReactionType);
      echo ReactionFilterButton(GetValue('Name', $ReactionType, ''), GetValue('UrlCode', $ReactionType, ''), $CurrentReactionType);
   }
echo '</div>
<div class="BestOfData">';
include_once('datalist.php');
echo '</div>';