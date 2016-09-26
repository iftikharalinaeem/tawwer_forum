<?php if (!defined('APPLICATION')) exit(); ?>

<li class="RankItem form-group">
    <div class="label-wrap">
        <?php echo $Sender->Form->label('Rank', 'RankID'); ?>
    </div>
    <div class="input-wrap">
        <?php echo $Sender->Form->dropDown('RankID', $Sender->Data('_Ranks'), array('IncludeNull' => true)); ?>
    </div>
</li>
