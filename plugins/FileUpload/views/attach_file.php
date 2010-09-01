<div class="AttachFileWrapper" id="AttachmentWindow">
   <div id="AttachFileContainer">
      <div class="FileAttachment" id="PrototypicalAttachment" style="display:none;">
         <div class="FileOptions"></div>
         <div class="FileName">FileName</div>
         <div class="FileSize">FileSize</div>
         <div class="UploadProgress">
            <div class="Foreground"><strong>Uploading:</strong> 10%</div>
            <div class="Background">&nbsp;</div>
            <div>&nbsp;</div>
         </div>
      </div>
   </div>
   <div class="AttachFileLink">
      <a href="javascript:;" id="AttachFileLink">Attach a file</a>
      <div id="CurrentUploader"></div>
   </div>
</div>
<script type="text/javascript">
   var UploadManager = new Gdn_MultiFileUpload('<?php echo Gdn::Request()->WebRoot(); ?>', 'AttachmentWindow', 'AttachFileContainer', 'AttachFileLink', 'UploadAttachment', '<?php echo $this->MaxUploadSize; ?>', '<?php echo uniqid(''); ?>');
</script>
