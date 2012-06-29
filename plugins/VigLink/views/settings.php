<?php if (!defined('APPLICATION')) exit();
?>
<style type="text/css">
.Configuration {
   margin: 0 20px 20px;
   background: #f5f5f5;
   float: left;
}
.ConfigurationForm {
   padding: 20px;
   float: left;
}
#Content form .ConfigurationForm ul {
   padding: 0;
}
#Content form .ConfigurationForm input.Button {
   margin: 0;
}
.note { font-size:0.9em; }

.ConfigurationHelp {
   border-left: 1px solid #aaa;
   margin-left: 340px;
   padding: 20px;
}
.ConfigurationHelp strong {
    display: block;
}
input.CopyInput {
   font-family: monospace;
   color: #000;
   width: 240px;
   font-size: 12px;
   padding: 4px 3px;
}
div.Errors {
	font-weight: 800;
}
</style>
<h1><?php echo $this->Data('Title'); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<div class="Info">
	<h2><?php echo Anchor(Img('/plugins/VigLink/design/VigLink.png', array('style' => 'margin-right:0.5em; vertical-align:middle;', 'width' => 48, 'height' => 48)), 'http://www.VigLink.com', array('target' => '_blank')); ?> <?php echo T('VigLink.Title'); ?></h2>
</div>
<div class="Configuration">
   <div class="ConfigurationForm">
      <ul>
         <li>
            <?php
               echo $this->Form->Label(T('VigLink.apikeylabel'), 'apikey');
               echo $this->Form->TextBox('apikey');
            ?>
            <p class="note"><?php echo T('VigLink.getAPIKey'); ?></p>
         </li>

      </ul>
      <?php echo $this->Form->Button(T('VigLink.Save'), array('class' => 'Button SliceSubmit')); ?>
   </div>
   <div class="ConfigurationHelp">
      <strong><?php echo T('VigLink.Info'); ?></strong>
      <p><?php echo T('VigLink.supportInfo'); ?></p>
   </div>
</div>
<?php
   echo $this->Form->Close();