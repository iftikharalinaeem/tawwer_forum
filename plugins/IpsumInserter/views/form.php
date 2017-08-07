<?php
// Settings
echo $this->Form->open();
echo $this->Form->errors();
?>
<?php if (!empty($this->Data['Message'])) { ?>
   <div class="Message">
      <?php echo htmlspecialchars($this->Data['Message']); ?>
   </div>
<?php } ?>
<ul>

   <li>
      <?php
      echo $this->Form->label('Number Of Discussions', 'DiscussionNumber');
      echo $this->Form->textBox('DiscussionNumber');
      ?>
   </li>
   <li>
      <?php
      echo $this->Form->label('Number Of Comments', 'CommentNumber');
      echo $this->Form->textBox('CommentNumber');
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
echo $this->Form->close('Save');
?>
