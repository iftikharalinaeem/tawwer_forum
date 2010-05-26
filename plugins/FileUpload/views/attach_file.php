<div class="AttachFileWrapper" id="AttachmentWindow">
   <table class="AttachFileContainer" id="AttachFileContainer">
      <tr class="FileAttachment" id="PrototypicalAttachment" style="display:none;">
         <td><img src="<?php echo $this->GetWebResource('images/gear.png'); ?>"/></td>
         <td></td>
         <td>Filename</td>
         <td>Filesize</td>
         <td><div class="ProgressBar"><div class="ProgressTicker"></div></div></td>
      </div>
   </table>
   <div class="AttachFileLink"><a href="#" id="AttachFileLink">Attach a file</a></div>
</div>
<script type="text/javascript">
   var UploadManager = new Gdn_MultiFileUpload('<?php echo Gdn::Request()->WebRoot(); ?>', 'AttachmentWindow', 'AttachFileContainer', 'AttachFileLink', 'UploadAttachment', 0, '<?php echo uniqid(''); ?>');
</script>
