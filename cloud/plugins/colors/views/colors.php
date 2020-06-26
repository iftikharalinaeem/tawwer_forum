<?php 

   $format          = strtolower($this->data('_EditorInputFormat'));
   $html_toolbar    = ''; // for regular text
   
   if ($format != 'text') {
      $html_toolbar    = '<div class="editor editor-format-'. $format .'">';
      $html_arrow_down = '<span class="icon-caret-down"></span>';

      foreach($this->data('_EditorToolbar') as $button) {

         // If the type is not an array, it's a regular button (type==button)
         if (!is_array($button['type'])) {
            $html_toolbar .= wrap('', 'span', $button['attr']);
         } else {
            // Else this button has dropdown options, so generate them
            $html_button_dropdown_options = '';

            foreach ($button['type'] as $button_option) {
               
               // If any text, use it
               $action_text = (isset($button_option['text'])) 
                   ? $button_option['text']
                   : '';

               // If the dropdown child elements require a different tag, 
               // specify it in the array, then grab it here, otherwise 
               // use the default, being a span. 
               $html_tag = (isset($button_option['html_tag']))
                   ? $button_option['html_tag'] 
                   : 'span';

               // Concatenate child elements 
               $html_button_dropdown_options .= wrap($action_text, $html_tag, $button_option['attr']);
            }
            
            switch ($button['action']) {             
               
               case 'link':
                  $html_toolbar .= wrap(
                     wrap($html_arrow_down, 'span', $button['attr']) .''. 
                     '<div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="createLink">
                        <input class="InputBox editor-input-url" data-wysihtml5-dialog-field="href" value="http://" />
                         <div class="MenuButtons">
                         <input type="button" data-wysihtml5-dialog-action="save" class="Button editor-dialog-fire-close" value="OK"/>
                         <input type="button" data-wysihtml5-dialog-action="cancel" class="Button Cancel editor-dialog-fire-close" value="Cancel"/>
                         </div>
                      </div>'
                   , 'div', ['class' => 'editor-dropdown']);
                  break;

               case 'image':
                  $html_toolbar .= wrap(
                     wrap($html_arrow_down, 'span', $button['attr']) .''. 
                     '<div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="insertImage">
                        <input class="InputBox editor-input-image" data-wysihtml5-dialog-field="src" value="http://">
                        <div class="MenuButtons">
                        <label class="editor-image-align">
                         Align:
                         <select data-wysihtml5-dialog-field="className">
                           <option value="">default</option>
                           <option value="wysiwyg-float-left">left</option>
                           <option value="wysiwyg-float-right">right</option>
                         </select>
                        </label>
                        <input type="button" data-wysihtml5-dialog-action="save" class="Button editor-dialog-fire-close" value="OK"/>
                        <input type="button" data-wysihtml5-dialog-action="cancel" class="Button Cancel editor-dialog-fire-close" value="Cancel"/>
                        </div>
                     </div>'
                   , 'div', ['class' => 'editor-dropdown']);
                  break;
               
               // All other dropdowns (color, format, emoji)
               default:
                  $html_toolbar .= wrap(
                     wrap($html_arrow_down, 'span', $button['attr']) .''. 
                     wrap($html_button_dropdown_options, 'div', ['class' => 'editor-insert-dialog Flyout MenuItems', 'data-wysihtml5-dialog' => ''])
                  , 'div', ['class' => 'editor-dropdown']);
                  break;  
            }
         }
      }

      $html_toolbar .= '</div>';
   
   }

   // Generate output for view
   echo $html_toolbar;

?>