<?php if (!defined('APPLICATION')) exit(); ?>

<!-- START GeoIP.php template -->

<h1><?php echo T($this->Data['Title']); ?></h1>

<div class="Info">
    <?=T($this->Data['PluginDescription'])?>
</div>


<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>

<ul>

    <li>
        <?=$this->Form->Label('Which Category would you like to Export?');?>
        <?=$this->Form->Dropdown('FieldName', $this->Data['CategoryList'])?>
    </li>
<!--
    <li>
        <?php
        echo $this->Form->Label("Log user's GeoIP information upon login.", 'Plugin.GeoIP.doLogin');
        echo $this->Form->Checkbox("Plugin.GeoIP.doLogin");
        ?>
    </li>
-->
</ul>

<?php
echo $this->Form->Close('Export');
?>


<!-- END config.php template -->
