<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .CountItemWrap {
      width: <?php echo round(100 / (2 + count($this->Data('ReactionTypes', [])))).'%'; ?>
   }
</style>

<?php
include_once 'reaction_functions.php';

function ReactionFilterButton($name, $code, $currentReactionType) {
   $lCode = strtolower($code);
   $url = Url("/bestof/$lCode");
   $imgSrc = "https://badges.v-cdn.net/reactions/50/$lCode.png";
   $cssClass = '';
   if ($currentReactionType == $lCode)
      $cssClass .= ' Selected';
   
   $result = <<<EOT
<div class="CountItemWrap">
<div class="CountItem$cssClass">
   <a href="$url">
      <span class="CountTotal"><img src="$imgSrc" /></span>
      <span class="CountLabel">$name</span>
   </a>
</div>
</div>
EOT;
   
   return $result;
}

echo Wrap($this->Data('Title'), 'h1 class="H"');

echo '<div class="DataCounts">';
   $CurrentReactionType = $this->Data('CurrentReaction');
   echo ReactionFilterButton(T('Everything'), 'Everything', $CurrentReactionType);
   $ReactionTypeData = $this->Data('ReactionTypes');
   foreach ($ReactionTypeData as $Key => $ReactionType) {
      echo ReactionFilterButton(T(GetValue('Name', $ReactionType, '')), GetValue('UrlCode', $ReactionType, ''), $CurrentReactionType);
   }
echo '</div>
   
<div class="BestOfData">';
include_once('datalist.php');
echo '</div>';