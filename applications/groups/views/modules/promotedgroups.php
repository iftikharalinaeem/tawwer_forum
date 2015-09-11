<?php if (!defined('APPLICATION')) exit();

$list = $this->data('list');

// Table view
if (val('layout', $list) == 'table') {
  include('grouplist-table.php');
} else if (val('layout', $list) == 'modern') {
  include('grouplist-modern.php');
}

