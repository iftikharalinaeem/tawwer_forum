<?php if (!defined('APPLICATION')) exit();

$list = $this->data('list');

// Table view
if (val('view', $list) == 'table') {
  include('grouplist-table.php');
} else if (val('view', $list) == 'modern') {
  include('grouplist-modern.php');
}
