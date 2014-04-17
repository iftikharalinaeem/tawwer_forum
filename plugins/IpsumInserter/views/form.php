<?php
// Settings
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<?php if (!empty($this->Data['Message'])) { ?>
   <div class="Message">
      <?php echo htmlspecialchars($this->Data['Message']); ?>
   </div>
<?php } ?>
<ul>

   <li>
      <?php
      echo $this->Form->Label('Number Of Discussions', 'DiscussionNumber');
      echo $this->Form->TextBox('DiscussionNumber');
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->Label('Number Of Comments', 'CommentNumber');
      echo $this->Form->TextBox('CommentNumber');
      ?>
   </li>
   <li>
      <select name="IpsumType">
         <option value="lorem">Standard</option>
         <option value="gangsta">Gangsta</option>
      </select>
   </li>

</ul>

<?php
echo $this->Form->Close('Save');
?>
