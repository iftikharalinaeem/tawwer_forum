<?php 
   $format          = strtolower($this->Data('_EditorInputFormat'));
   $html_toolbar    = ''; // for regular text
   
   if ($format != 'text') {
      $html_toolbar    = '<div class="editor editor-format-'. $format .'">';
      $html_separator  = '<span class="editor-sep hidden-xs"></span>';
      $html_arrow_down = '&nbsp;<span class="icon-caret-down"></span>';

      foreach($this->Data('_Toolbar') as $button) {

         // If the type is not an array, it's a regular button (type==button)
         if (!is_array($button['type'])) {
            if ($button['type'] == 'separator') {
                $html_toolbar .= Wrap('', 'span', $button['attr']);
             } else {
                $html_toolbar .= Wrap('', 'a', $button['attr']);
             }
         } else {
            $html_button_dropdown_options = '';

            // Else this button has dropdown options, so generate them
            foreach ($button['type'] as $button_option) {
               // currently only color has a bunch of sub buttons
               $html_button_dropdown_options .= Wrap('', 'li', $button_option['attr']);
            }

            // TODO make less redundant
            // Wrap the options in particular dropdown markup
            switch ($button['action']) {
               case 'color':
                  $html_toolbar .= Wrap(
                     Wrap($html_arrow_down, 'a', $button['attr']) .''. 
                     Wrap($html_button_dropdown_options, 'ul', array('class' => 'editor-insert-dialog editor-colors-flyout Flyout MenuItems', 'data-wysihtml5-dialog' => ''))
                  , 'div', array('class' => 'editor-dropdown'));
                  break;

               case 'link':
                  $html_toolbar .= Wrap(
                     Wrap($html_arrow_down, 'a', $button['attr']) .''. 
                     '<div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="createLink">
                        <input class="InputBox editor-input-url" data-wysihtml5-dialog-field="href" value="http://" />
                        <hr />
                         <input type="button" data-wysihtml5-dialog-action="save" class="Button editor-dialog-fire-close" value="OK"/>
                         <input type="button" data-wysihtml5-dialog-action="cancel" class="Button Cancel editor-dialog-fire-close" value="Cancel"/>
                      </div>'
                   , 'div', array('class' => 'editor-dropdown'));
                  break;

               case 'image':
                  $html_toolbar .= Wrap(
                     Wrap($html_arrow_down, 'a', $button['attr']) .''. 
                     '<div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="insertImage">
                        <input class="InputBox editor-input-image" data-wysihtml5-dialog-field="src" value="http://">
                        <hr />
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
                     </div>'
                   , 'div', array('class' => 'editor-dropdown'));
                  break;
            }
         }
      }

      $html_toolbar .= '</div>';
   
   }

   
   
   // Generate output for view
   echo $html_toolbar;
   
   


   /*
   // example of above output for reference
   <a class="icon icon-bold" data-wysihtml5-command="bold" data-wysihtml5-command-value="bold" title="Bold"></a>
   <a class="icon icon-italic" data-wysihtml5-command="italic" title="Italic"></a>
   <a class="icon icon-strikethrough" data-wysihtml5-command="strikethrough" title="Strike"></a>

   <div class="ToggleFlyout editor-dropdown">
      <a class="icon icon-font" data-wysihtml5-command-group="foreColor" title="Color">&nbsp;<span class="icon-caret-down"></span></a>
      <ul class="editor-insert-dialog editor-colors-flyout Flyout MenuItems" data-wysihtml5-dialog="" style="display: none;">
         <li class="color color-black" data-wysihtml5-command-value="black" data-wysihtml5-command="foreColor"></li>
         <li class="color color-white" data-wysihtml5-command-value="white" data-wysihtml5-command="foreColor"></li>
         <li class="color color-gray" data-wysihtml5-command-value="gray" data-wysihtml5-command="foreColor"></li>
         <li class="color color-silver" data-wysihtml5-command-value="silver" data-wysihtml5-command="foreColor"></li>
         <li class="color color-maroon" data-wysihtml5-command-value="maroon" data-wysihtml5-command="foreColor"></li>
         <li class="color color-red" data-wysihtml5-command-value="red" data-wysihtml5-command="foreColor"></li>
         <li class="color color-purple" data-wysihtml5-command-value="purple" data-wysihtml5-command="foreColor"></li>
         <li class="color color-green" data-wysihtml5-command-value="green" data-wysihtml5-command="foreColor"></li>
         <li class="color color-olive" data-wysihtml5-command-value="olive" data-wysihtml5-command="foreColor"></li>
         <li class="color color-navy" data-wysihtml5-command-value="navy" data-wysihtml5-command="foreColor"></li>
         <li class="color color-blue" data-wysihtml5-command-value="blue" data-wysihtml5-command="foreColor"></li>
         <li class="color color-lime" data-wysihtml5-command-value="lime" data-wysihtml5-command="foreColor"></li>
      </ul>
   </div>

   <a class="icon icon-list-ol" data-wysihtml5-command="insertOrderedList" title="Insert ordered list"></a>
   <a class="icon icon-list-ul" data-wysihtml5-command="insertUnorderedList" title="Insert unordered list"></a> 

   <span class="editor-sep sep-unique hidden-xs"></span>

   <a class="icon icon-quote" data-wysihtml5-command="blockquote" title="Quote"></a>
   <a class="icon icon-code" data-wysihtml5-command="code" title="Code"></a>
   <a class="icon icon-ellipsis" data-wysihtml5-command="spoiler" title="Spoiler"></a>

   <span class="editor-sep sep-media hidden-xs"></span>

   <div class="editor-dropdown">
      <a class="icon icon-link" data-wysihtml5-command="createLink" title="Url"></a>
      <div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="createLink" style="display: none;">
        <input class="InputBox" data-wysihtml5-dialog-field="href" value="http://" />
        <hr />
         <input type="button" data-wysihtml5-dialog-action="save" class="Button" value="OK"/>
         <input type="button" data-wysihtml5-dialog-action="cancel" class="Button" value="Cancel"/>
      </div>
   </div>

   <div class="editor-dropdown">
      <a class="icon icon-picture" data-wysihtml5-command="insertImage" title="Image"></a>
      <div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="insertImage" style="display: none;">
         <input class="InputBox" data-wysihtml5-dialog-field="src" value="http://">
         <hr />
         <label>
          Align:
          <select data-wysihtml5-dialog-field="className">
            <option value="">default</option>
            <option value="wysiwyg-float-left">left</option>
            <option value="wysiwyg-float-right">right</option>
          </select>
         </label>
         <input type="button" data-wysihtml5-dialog-action="save" class="Button" value="OK"/>
         <input type="button" data-wysihtml5-dialog-action="cancel" class="Button" value="Cancel"/>
      </div>
   </div>

  <span class="editor-sep sep-format hidden-xs"></span>

   <a class="icon icon-align-left" data-wysihtml5-command="justifyLeft" title="Align left"></a>
   <a class="icon icon-align-justify" data-wysihtml5-command="justifyCenter" title="Align center"></a>
   <a class="icon icon-align-right" data-wysihtml5-command="justifyRight" title="Align right"></a>

   <span class="editor-sep sep-switches hidden-xs"></span>

   <a class="icon icon-source editor-toggle-source" data-wysihtml5-action="change_view" title="Toggle HTML view"></a>
   <a class="icon icon-resize-full editor-toggle-fullpage-button" title="Toggle full page"></a>
   */

?>