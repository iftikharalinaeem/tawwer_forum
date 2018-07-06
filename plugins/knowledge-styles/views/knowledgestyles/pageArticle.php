<?php if (!defined('APPLICATION')) exit();
/** @var Gdn_Smarty $smarty */
$smarty = Gdn::getContainer()->get(\Gdn_Smarty::class);


$smarty->render($this->fetchViewLocation('pages/article'), $this);


include "styleGuidePanel.php";
echo "<style>.Trace { display: none; }</style>";
