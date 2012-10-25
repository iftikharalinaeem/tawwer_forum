<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .CountItemWrap {
      width: <?php echo round(100 / (2 + count($this->Data('ReactionTypes', array())))).'%'; ?>
   }
</style>

<?php
include_once 'reaction_functions.php';

function ReactionFilterButton($Name, $Code, $CurrentReactionType) {
   $LCode = strtolower($Code);
   $Url = Url("/bestof/$LCode");
   $ImgSrc = "http://badges.vni.la/reactions/50/$LCode.png";
   $CssClass = '';
   if ($CurrentReactionType == $LCode)
      $CssClass .= ' Selected';
   
   $Result = <<<EOT
<div class="CountItemWrap">
<div class="CountItem$CssClass">
   <a href="$Url">
      <span class="CountTotal"><img src="$ImgSrc" /></span>
      <span class="CountLabel">$Name</span>
   </a>
</div>
</div>
EOT;
   
   return $Result;
}

echo Wrap($this->Data('Title'), 'h1 class="H"');

echo Gdn_Theme::Module('BestOfFilterModule');
   
echo '<div class="BestOfData">';
include_once('datalist.php');
echo '</div>';