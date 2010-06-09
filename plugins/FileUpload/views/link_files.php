<div class="Attachments">
   <div class="AttachmentHeader">Attachments</div>
   <table class="AttachFileContainer">
      <?php
         foreach ($this->CommentMediaList as $Media) {
      ?>
            <tr>
               <td><img src="<?php echo $this->GetWebResource('images/gear.png'); ?>"/></td>
               <td><a href="<?php echo Url("/discussion/download/{$Media->MediaID}/{$Media->Name}"); ?>"><?php echo $Media->Name; ?></a></td>
               <td>(<?php echo Gdn_Format::Bytes($Media->Size, 0); ?>)</td>
            </tr>
      <?php
         }
      ?>
   </table>
</div>