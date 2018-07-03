<?php if (!defined('APPLICATION')) exit();
echo wrap($this->data('Title'), 'h1', ['class' => 'pageTitle']);

$data = array(
    'Test' => '123'
);


//$smarty = $this->smarty->assign($data);

//echo $output = $smarty->fetch('components/test.tpl');
