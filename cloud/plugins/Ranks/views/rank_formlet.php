<?php if (!defined('APPLICATION')) exit(); ?>

<li class="RankItem form-group">
    <div class="label-wrap">
        <?php echo $Sender->Form->label('Rank', 'RankID'); ?>
    </div>
    <div class="input-wrap">
        <?php echo $Sender->Form->dropDown('RankID', $Sender->data('_Ranks'), ['IncludeNull' => true]); ?>
    </div>
</li>
