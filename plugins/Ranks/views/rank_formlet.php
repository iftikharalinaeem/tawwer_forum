<?php if (!defined('APPLICATION')) exit(); ?>

<li class="RankItem form-group row">
    <div class="label-wrap">
        <?php echo $Sender->Form->Label('Rank', 'RankID'); ?>
    </div>
    <div class="input-wrap">
        <?php echo $Sender->Form->DropDown('RankID', $Sender->Data('_Ranks'), array('IncludeNull' => TRUE)); ?>
    </div>
</li>
