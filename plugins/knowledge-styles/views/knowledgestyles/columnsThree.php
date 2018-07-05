<?php if (!defined('APPLICATION')) exit();
echo "<div class='_container'>";
echo wrap("Layout - Three Columns", 'h1', ['class' => 'pageTitle']);
echo "</div>";


/** @var Gdn_Smarty $smarty */
$smarty = Gdn::getContainer()->get(\Gdn_Smarty::class);
$smarty->render($this->fetchViewLocation('layouts/_example_columns_3'), $this);

include "styleGuidePanel.php";
