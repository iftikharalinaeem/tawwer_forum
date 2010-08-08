<?php if (!defined('APPLICATION')) exit();

echo '<h1>', $this->Data('Title'), '</h1>';
?>
<div class="FilterMenu">
<?php
   echo Anchor(sprintf(T('Add %s'), T('Pocket')), 'plugin/pockets/add', 'SmallButton');
?>
</div>
<table id="Pockets" class="AltColumns">
   <thead>
      <tr>
         <th><?php echo T('Pocket'); ?></th>
         <th><?php echo T('Page'); ?></th>
         <th class="Alt"><?php echo T('Location'); ?></th>
         <th><?php echo T('Body'); ?></th>
         <th class="Alt"><?php echo T('Notes'); ?></th>
      </tr>
   </thead>
   <tbody>
      <?php
      foreach ($this->Data('PocketData') as $PocketRow) {
         echo '<tr'.($PocketRow['Disabled'] != Pocket::DISABLED ? '' : ' class="Disabled"').'>';

         echo '<td>',
            '<strong>', htmlspecialchars($PocketRow['Name']), '</strong>',
            '<div>',
            Anchor('Edit', "/plugin/pockets/edit/{$PocketRow['PocketID']}"),
            ' <span>|</span> ',
            Anchor('Delete', "/plugin/pockets/delete/{$PocketRow['PocketID']}", 'Popup'),
            '</div>',
            '</td>';

         echo '<td>',htmlspecialchars($PocketRow['Page']),'</td>';
         echo '<td  class="Alt">', htmlspecialchars($PocketRow['Location']), '</td>';
         echo '<td>', nl2br(htmlspecialchars(substr($PocketRow['Body'], 0, 200))), '</td>';
         echo '<td  class="Alt">', $PocketRow['Notes'], '</td>';

         echo "</tr>\n";
      }
      ?>
   </tbody>
</table>
<h2><?php echo T('Global Options'); ?></h2>
<?php
   $Form = $this->Form;
   echo $Form->Open();
?>
<ul>
   <li>
      <?php
         echo $Form->CheckBox('ShowLocations', T('Show Pocket Locations'));
         echo '<div class="Info2">', T('Show all possible pocket locations.'), '</div>';
      ?>
   </li>
</ul>
<?php echo $Form->Close('Save'); ?>