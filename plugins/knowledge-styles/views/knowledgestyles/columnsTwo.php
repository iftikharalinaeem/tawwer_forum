<?php if (!defined('APPLICATION')) exit();
echo wrap($this->data('Title'), 'h1', ['class' => 'pageTitle']);

/** @var Gdn_Smarty $smarty */
$smarty = Gdn::getContainer()->get(\Gdn_Smarty::class);

$smarty->render($this->fetchViewLocation('layoutExamples/'), $this);

include "styleGuidePanel.php";
