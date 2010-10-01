<div class="Attachments">
   <div class="AttachmentHeader"><?php echo T('Attachments'); ?></div>
   <table class="AttachFileContainer">
      <?php
         foreach ($this->Data('CommentMediaList') as $Media) {
            $IsOwner = (Gdn::Session()->IsValid() && (Gdn::Session()->UserID == GetValue('InsertUserID',$Media,NULL)));
      ?>
            <tr>
               <td><img src="<?php echo $this->Data('GearImage'); ?>"/></td>
               <?php if ($IsOwner || Gdn::Session()->CheckPermission("Garden.Settings.Manage")) { ?>
                  <td><a class="DeleteFile" href="<?php echo Url("/plugin/fileupload/delete/{$Media->MediaID}"); ?>"><span><?php echo T('Delete'); ?></span></a></td>
               <?php } ?>
               <td><a href="<?php echo Url("/discussion/download/{$Media->MediaID}/{$Media->Name}"); ?>"><?php echo $Media->Name; ?></a></td>
               <td>(<?php echo Gdn_Format::Bytes($Media->Size, 0); ?>)</td>
            </tr>
      <?php
         }
      ?>
   </table>
</div>