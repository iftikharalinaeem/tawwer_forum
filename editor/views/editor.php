<?php 

foreach($this->Data('_Toolbar') as $key => $val) {
   
   $format = strtolower($val);
   
   
   /*
   switch ($Item['type']) {
      case 'link':
         unset($Item['type']);
         echo Wrap('', 'a', $Item);
         break;
      case 'callback':
         echo call_user_func($Item['callback'], $Item);
         break;
      case 'sep':
         echo '<span class="editor-sep"></span>';
         break;
   }
   */

}

?>

<div class="editor editor-format-<?php echo $format; ?>" id="editor-format-<?php echo $format; ?>">

   <a class="icon icon-bold" data-wysihtml5-command="bold" title="Bold"></a>
   <a class="icon icon-italic" data-wysihtml5-command="italic" title="Italic"></a>
   <a class="icon icon-strikethrough" data-wysihtml5-command="strikethrough" title="Strike"></a>
 
   <div class="ToggleFlyout editor-dropdown">
      <a class="icon icon-font editor-action-disabled" data-wysihtml5-command-group="foreColor" title="Color">&nbsp;<span class="icon-caret-down"></span></a>
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
   
   <span class="editor-sep"></span>
   
   <a class="icon icon-quote" data-wysihtml5-command="blockquote" title="Quote"></a>
   <a class="icon icon-code" data-wysihtml5-command="code" title="Code"></a>
   <a class="icon icon-ellipsis" data-wysihtml5-command="spoiler" title="Spoiler"></a>
   
   <span class="editor-sep"></span>
   
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
   
  <span class="editor-sep"></span>
   
   <a class="icon icon-align-left editor-action-disabled" data-wysihtml5-command="justifyLeft" title="Align left"></a>
   <a class="icon icon-align-justify editor-action-disabled" data-wysihtml5-command="justifyCenter" title="Align center"></a>
   <a class="icon icon-align-right editor-action-disabled" data-wysihtml5-command="justifyRight" title="Align right"></a>
   <a class="icon icon-list-ol" data-wysihtml5-command="insertOrderedList" title="Insert ordered list"></a>
   <a class="icon icon-list-ul" data-wysihtml5-command="insertUnorderedList" title="Insert unordered list"></a> 
   
   <span class="editor-sep"></span>
   
   <a class="icon icon-source editor-action-disabled"data-wysihtml5-action="change_view" title="Toggle HTML view"></a>
   <a class="icon icon-resize-full editor-toggle-fullpage-button" id="editor-toggle-fullpage-button" title="Toggle full page"></a>
   
</div>