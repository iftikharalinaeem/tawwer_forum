<?php if (!defined('APPLICATION')) exit();
/** @var Gdn_Smarty $smarty */
$smarty = Gdn::getContainer()->get(\Gdn_Smarty::class);


$smarty->render($this->fetchViewLocation('pages/advancedSearch'), $this);


echo "<style>.Trace { display: none; }</style>";

