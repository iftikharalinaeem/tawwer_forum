<div class="Attachments">
   <div class="AttachmentHeader">Attachments</div>
   <table class="AttachFileContainer">
      <?php
         foreach ($this->CommentMediaList as $Media) {
      ?>
            <tr>
               <td><img src="<?php echo $this->GetWebResource('images/gear.png'); ?>"/></td>
               <td><a href="/discussion/download/<?php echo $Media->MediaID;?>/<?php echo $Media->Name; ?>"><?php echo $Media->Name; ?></a></td>
               <td>(<?php echo Gdn_Format::Bytes2String($Media->Size, 0); ?>)</td>
            </tr>
      <?php
         }
      ?>
   </table>
</div>