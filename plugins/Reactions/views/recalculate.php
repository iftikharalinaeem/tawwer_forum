<?php if (!defined('APPLICATION')) exit; ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Wrap">
    <div class="Info">
        <p><?php echo t('Garden.Reactions.Recalculate', 'Recalculate reaction information.'); ?></p>
        <?php if ($this->data('Recalculated')): ?>
        <p>
            <strong><?php
                echo t('Garden.Reactions.RecalculateComplete', 'Reactions have been recalculated.');
            ?></strong>
        </p>
        <?php endif; ?>
    </div>
    <div>
        <?php
        echo $this->Form->open();
        echo $this->Form->Close('Recalculate Now');
        ?>
    </div>
</div>
