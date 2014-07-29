<?php if (!defined('APPLICATION')) return;
require_once $this->FetchViewLocation('warning_functions', '', 'plugins/Warnings2');
$IsPrivileged = $this->Data('IsPrivileged');
?>
<div class="DataListWrap">
<h2 class="H"><?php echo T('Notes'); ?></h2>

<ul class="DataList DataList-Notes">
   <?php
   foreach ($this->Data('Notes', array()) as $Row):
      $Row['Privileged'] = $IsPrivileged;
   ?>
   <li id="UserNote_<?php echo $Row['UserNoteID']; ?>" class="Item Item-Row <?php echo 'UserNote-'.$Row['Type'] ?>">
      <div class="Item-Col Item-Col9">
         <?php
         $Func = "WriteUserNote{$Row['Type']}";
         if (function_exists($Func)) {
             $Func($Row);
         } else {
         ?>
             <div class="Meta">
                <div class="Options">
                   <?php
                   if ($IsPrivileged):
                      echo Anchor(T('edit'), '/profile/note?noteid='.$Row['UserNoteID'], 'OptionsLink Popup', array('title' => T('Edit'))).
                         Bullet(' ').
                         Anchor(T('delete'), '/profile/deletenote?noteid='.$Row['UserNoteID'], 'OptionsLink Popup', array('title' => T('Delete')));
                   endif;
                   ?>
                </div>
                <?php
                echo '<span class="NoteType NoteType-'.$Row['Type'].'">'.T(ucfirst($Row['Type'])).'</span> ';
                ?>
             </div>
             <div class="Note-Body">
                <?php echo $Row['Body']; ?>
             </div>
         <?php
         }
         ?>
      </div>
      <div class="Item-Col Item-Col3 User-Col">
         <div class="Media">
            <?php echo UserPhoto($Row, array('LinkClass' => 'Img', 'Px' => 'Insert')); ?>
            <div class="Media-Body">
               <?php
               echo '<div>'.UserAnchor($Row, '', array('Px' => 'Insert')).'</div> ';
               echo '<div class="Meta">'.Gdn_Format::Date($Row['DateInserted'], 'html').'</div>';
               ?>
            </div>
         </div>
      </div>
   </li>
   <?php
   endforeach;
   ?>
   <?php if (count($this->Data('Notes')) == 0 && $IsPrivileged): ?>
   <li>
      <div class="Empty">
         <?php echo T('Notes description', 'You can add notes to a user which are only visible to moderators.'); ?>
      </div>
   </li>
   <?php endif; ?>
</ul>

</div>
