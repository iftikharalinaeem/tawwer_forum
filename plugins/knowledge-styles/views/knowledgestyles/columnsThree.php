<?php if (!defined('APPLICATION')) exit();
echo wrap($this->data('Title'), 'h1', ['class' => 'pageTitle']);

$data = array(
    'Test' => '123'
);

/** @var Gdn_Smarty $smarty */
//$smarty = Gdn::getContainer()->get(\Gdn_Smarty::class);
//$smarty->smarty()->assign($data);
//$smarty->render($this->fetchViewLocation('layouts/columns'), $this);





include "styleGuidePanel.php";
