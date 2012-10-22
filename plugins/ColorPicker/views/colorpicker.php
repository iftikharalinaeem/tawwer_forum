<?php if (!defined('APPLICATION')) exit(); ?>
<div class="ColorPickerPlugin">
   <div class="ColorPicker">
      <div class="Groups">
         <?php
         $PrevColorGroup = FALSE;
         foreach ($this->Data('ColorPicker.Colors') as $Color => $Options) {
            $ColorGroup = $this->ColorPicker->ColorGroup($Options['hsv'][0]);
            $Key = $ColorGroup !== NULL ? $ColorGroup : '_';

            if ($ColorGroup !== $PrevColorGroup) {
               // Close off previous color group.
               if ($PrevColorGroup !== FALSE)
                  echo '</div></div>'; // swatches, group

               echo "\n<div class=\"ColorGroup ColorGroup$Key\" group=\"$Key\">";

               // Output the big swatch.
               $GroupColor = $this->Data("ColorPicker.Groups.$ColorGroup");
               echo "<div class=\"BigSwatch BigSwatch$Key\" style=\"background: $GroupColor\" orig=\"$GroupColor\">&#160;</div>";

               // Start the swatches div.
               echo '<div class="Swatches">';
               
               $PrevColorGroup = $ColorGroup;
            }

            // Render the swatch.
            echo "<div class=\"Swatch\" style=\"background: $Color\" orig=\"$Color\"></div>";
         }
         // Close off the last color group.
         echo '</div></div>';
         ?>
      </div>
   </div>
   <div class="Stub">&#160;</div>
</div>
