<?php if (!defined('APPLICATION')) return;
require_once $this->fetchViewLocation('warning_functions', '', 'plugins/Warnings2');
$IsPrivileged = $this->data('IsPrivileged');
?>
<div class="DataListWrap">
<h2 class="H"><?php echo t('Note'); ?></h2>
<ul class="DataList DataList-Notes">
    <?php foreach ($this->data('Notes', []) as $Row): ?>
    <li id="UserNote_<?php echo $Row['UserNoteID']; ?>" class="Item Item-Row <?php echo 'UserNote-'.$Row['Type'] ?>">
      <?php
      $Func = "WriteUserNote{$Row['Type']}";
      if (function_exists($Func)):
         $Func($Row);
      else:
      ?>

      <div class="Item-Col Item-Col9">
         <div class="Meta">
            <div class="Options">
               <?php
               if ($IsPrivileged):
                  echo anchor(t('edit'), '/profile/note?noteid='.$Row['UserNoteID'], 'OptionsLink Popup', ['title' => t('Edit')]).
                     bullet(' ').
                     anchor(t('delete'), '/profile/deletenote?noteid='.$Row['UserNoteID'], 'OptionsLink Popup', ['title' => t('Delete')]);
               endif;
               ?>
            </div>
            <?php
            echo '<span class="NoteType NoteType-'.$Row['Type'].'">'.t(ucfirst($Row['Type'])).'</span> ';
            ?>
         </div>
         <div class="Warning-Body">
            <?php echo $Row['Body']; ?>
         </div>
      </div>
      <div class="Item-Col Item-Col3 User-Col">
         <div class="Media">
             <?php if (!isset($Row['HideWarnerIdentity']) || !$Row['HideWarnerIdentity']): ?>
                <?php echo userPhoto($Row, ['LinkClass' => 'Img', 'Px' => 'Insert']); ?>
             <?php endif; ?>
            <div class="Media-Body">
               <?php
               if (!isset($Row['HideWarnerIdentity']) || !$Row['HideWarnerIdentity']) {
                  echo '<div>'.userAnchor($Row, '', ['Px' => 'Insert']).'</div> ';
               }
               echo '<div class="Meta">'.Gdn_Format::date($Row['DateInserted'], 'html').'</div>';
               ?>
            </div>
         </div>
      </div>
      <?php endif; ?>
   </li>
   <?php endforeach; ?>

   <?php if (count($this->data('Notes')) == 0 && $IsPrivileged): ?>
   <li>
      <div class="Empty">
         <?php echo t('Notes description', 'You can add notes to a user which are only visible to moderators.'); ?>
      </div>
   </li>
   <?php endif; ?>
</ul>

</div>
