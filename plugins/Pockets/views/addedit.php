<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->Data('Title'), '</h1>';

$Form = $this->Form; //new Gdn_Form();
echo $Form->Open();
echo $Form->Errors();
?>
<ul>
   <li>
      <?php
         echo $Form->Label('Name', 'Name');
         echo '<div class="Info2">', T('Enter a descriptive name.', 'Enter a descriptive name for the pocket. This name will not show up anywhere except when managing your pockets here so it is only used to help you remember the pocket.'), '</div>';
         echo $Form->TextBox('Name');
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Location', 'Location');
         echo '<div class="Info2">', T('Select the location of the pocket.', 'Select the location of the pocket.'), '</div>';
         echo $Form->DropDown('Location', $this->Data('LocationsArray'));
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Body', 'Body');
         echo '<div class="Info2">', T('The text of the pocket.', 'Enter the text of the pocket. This will be output exactly as you type it so make sure that you enter valid HTML.'), '</div>';
         echo $Form->TextBox('Body', array('Multiline' => TRUE));
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Repeat', 'RepeatType');
         echo $Form->RadioList('RepeatType', array(Pocket::REPEAT_ONCE => 'Once', Pocket::REPEAT_EVERY => T('Repeat Every'), Pocket::REPEAT_INDEX => T('Given Indexes')));

         // Options for repeat every.
         echo '<div class="RepeatEveryOptions">',
            '<div class="Info2">', T('Enter numbers starting at 1.'), '</div>',
            '<p>',
            $Form->Label('Frequency', 'EveryFrequency', array('Class' => 'SubLabel')),
            $Form->TextBox('EveryFrequency', array('Class' => 'SmallInput')),
            '</p><p>',
            $Form->Label('Begin At', 'EveryBegin', array('Class' => 'SubLabel')),
            $Form->TextBox('EveryBegin', array('Class' => 'SmallInput')),
            '</p></div>';

         // Options for repeat indexes.
         echo '<div class="RepeatIndexesOptions"',
            '<div class="Info2">', T('Enter a comma-delimited list of indexes, starting at 1.'), '</div><p>',
            $Form->Label('Indexes', 'Indexes', array('Class' => 'SubLabel')),
            $Form->TextBox('Indexes'),
            '</p></div>';
      ?>
   </li>
   <li>
      <?php
         echo $Form->Label('Enable/Disable', 'Disabled');
         echo $Form->RadioList('Disabled', array(Pocket::ENABLED => T('Enabled'), Pocket::DISABLED => T('Disabled'), Pocket::TESTING => T('Test Mode')));
      ?>
   </li>
</ul>
<?php echo $Form->Close('Save'); ?>