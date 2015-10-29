<?php if (!defined('APPLICATION')) exit(); ?>

<!-- START GeoIP.php template -->

<h1><?php echo T($this->Data['Title']); ?></h1>

<div class="Info">
    <?=T($this->Data['PluginDescription'])?>
</div>


<?php

//$this->Form->Action = "/plugin/categoryexport/download";
//$this->Form->Method = "get";

echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>

    <li>
        <?=$this->Form->label('&rarr; Which Category would you like to Export?');?>
        <?=$this->Form->dropdown('CategoryID', $this->Data['CategoryList'])?>
    </li>

    <li>
        <?=$this->Form->label('&rarr; Which Table would you like to Export?');?>
        <ul>
            <li><?=$this->Form->radio('table', 'Discussions', ['value'=>'discussion', 'default'=>true])?></li>
            <li><?=$this->Form->radio('table', 'Comments', ['value'=>'comment'])?></li>
        </ul>
    </li>
    <li>
        <?=$this->Form->label('&rarr; Start:');?>
        <?=$this->Form->input('offset', 'text', ['value'=>0])?>
    </li>
    <li>
        <?=$this->Form->label('&rarr; Rows:');?>
        <?=$this->Form->input('limit', 'text', ['value'=>1000])?>
    </li>

</ul>

<?php
echo $this->Form->Close('Export');
?>


<!-- END config.php template -->
