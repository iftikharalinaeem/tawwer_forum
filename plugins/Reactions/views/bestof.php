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
?>
<div class="Tabs BestOfTabs">
   <ul>
      <li class="Active"><?php echo Anchor(T('Best Of...'), '/bestof', 'TabLink'); ?></li>
   </ul>
   <div class="SubTab ReactionFilters">
      <?php
      echo ReactionFilterButton('Everything', 'everything', $CurrentReactionType);
      $ReactionTypeData = $this->Data('ReactionTypeData');
      foreach ($ReactionTypeData as $Key => $ReactionType) {
         // decho($ReactionType);
         echo ReactionFilterButton(GetValue('Name', $ReactionType, ''), GetValue('UrlCode', $ReactionType, ''), $CurrentReactionType);
      }
      ?>
   </div>
</div>
<div class="BestOfData">
<?php include_once('datalist.php'); ?>
</div>