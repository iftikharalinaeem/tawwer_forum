<?php // editor toolbar ?>

<div class="editor wysihtml5-toolbar" id="wysihtml5-toolbar" style="display: none;">

   <a class="icon icon-bold" data-wysihtml5-command="bold" title="Bold CTRL+B"></a>
   <a class="icon icon-italic" data-wysihtml5-command="italic" title="Italic CTRL+I"></a>
   <a class="icon icon-underline" data-wysihtml5-command="underline" title="Underline"></a>
   <a class="icon icon-font" data-wysihtml5-command="foreColor" data-wysihtml5-command-value="red" title="Text color"> <i class="icon-caret-down"></i></a>
   
   <span class="editor-sep"></span>
   
   <div class="ToggleFlyout">
      <a class="icon icon-link FlyoutButton" data-wysihtml5-command="createLink" title="Insert link"></a>
      <div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="createLink" style="display: none;">
        <input class="InputBox" data-wysihtml5-dialog-field="href" value="http://">
        <a data-wysihtml5-dialog-action="save" class="Button">OK</a> 
        <a class="Button" data-wysihtml5-dialog-action="cancel">Cancel</a>
      </div>
   </div>
   
   <div class="ToggleFlyout">
      <a class="icon icon-picture" data-wysihtml5-command="insertImage" title="Insert image"></a>
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
         <a data-wysihtml5-dialog-action="save" class="Button">OK</a>
         <a data-wysihtml5-dialog-action="cancel" class="Button">Cancel</a>
      </div>
   </div>
   
  <span class="editor-sep"></span>
   
   <a class="icon icon-align-left" data-wysihtml5-command="justifyLeft" title="Align left"></a>
   <a class="icon icon-align-justify" data-wysihtml5-command="justifyCenter" title="Align center"></a>
   <a class="icon icon-align-right" data-wysihtml5-command="justifyRight" title="Align right"></a>
   <a class="icon icon-list-ol" data-wysihtml5-command="insertOrderedList" title="Insert ordered list"></a>
   <a class="icon icon-list-ul" data-wysihtml5-command="insertUnorderedList" title="Insert unordered list"></a> 
   
  <span class="editor-sep"></span>
   
   <a class="icon icon-source"data-wysihtml5-action="change_view" title="Toggle HTML view"></a>
   
   <a class="icon icon-resize-full" id="editor-toggle-fullpage-button" title="Toggle fullscreen"></a>
</div>