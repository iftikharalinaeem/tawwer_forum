<?php if (!defined('APPLICATION')) exit();
echo wrap($this->data('Title'), 'h1', ['class' => 'pageTitle']);

/** @var Gdn_Smarty $smarty */
$smarty = Gdn::getContainer()->get(\Gdn_Smarty::class);
$smarty->render($this->fetchViewLocation('layouts/_example_panel-and-nav'), $this);

include "styleGuidePanel.php";

