<div class="AttachFileWrapper AttachmentWindow">
   <div class="AttachFileContainer">
      <div class="FileAttachment PrototypicalAttachment" style="display:none;">
         <div class="FileOptions"></div>
         <div class="FileName"><?php echo T('FileName'); ?></div>
         <div class="FileSize"><?php echo T('FileSize'); ?></div>
         <div class="UploadProgress">
            <div class="Foreground"><strong><?php echo T('Uploading...'); ?></strong></div>
            <div class="Background">&nbsp;</div>
            <div>&nbsp;</div>
         </div>
      </div>
   </div>
   <div class="AttachFileLink">
      <a href="javascript:void(0);"><?php echo T('Attach a file'); ?></a>
      <div class="CurrentUploader"></div>
   </div>
</div>
<script type="text/javascript">
   if (GdnUploaders)
      GdnUploaders.Prepare();
</script>