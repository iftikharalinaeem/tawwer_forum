<?php if (!defined('APPLICATION')) exit(); ?>

<li class="RankItem">
   <?php
      echo $Sender->Form->Label('Rank', 'RankID');
      echo $Sender->Form->DropDown('RankID', $Sender->Data('_Ranks'), array('IncludeNull' => TRUE));
   ?>
</li>
