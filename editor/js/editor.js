// initialize wysihtml5
$(function() {
   
   // Will need to change depending on area--default values
   var editorToolbarId  = 'wysihtml5-toolbar';
   var editorTextareaId = 'Form_Body';
   var editorName       = 'vanilla-editor-text';
   
   // Abstracted because editor will need to be invoked in more than one place
   var editorRules = {
      // Give the editor a name, the name will also be set as class name on the iframe and on the iframe's body 
      name:                 editorName,
      // Whether the editor should look like the textarea (by adopting styles)
      style:                true,
      // Id of the toolbar element or DOM node, pass false value if you don't want any toolbar logic
      toolbar:              editorToolbarId,
      // Whether urls, entered by the user should automatically become clickable-links
      autoLink:             true,
      // Object which includes parser rules to apply when html gets inserted via copy & paste
      // See parser_rules/*.js for examples
      parserRules:          wysihtml5ParserRules,
      // Parser method to use when the user inserts content via copy & paste
      parser:               wysihtml5.dom.parse,
      // Class name which should be set on the contentEditable element in the created sandbox iframe, can be styled via the 'stylesheets' option
      composerClassName:    "wysihtml5-editor",
      // Class name to add to the body when the wysihtml5 editor is supported
      bodyClassName:        "wysihtml5-supported",
      // By default wysihtml5 will insert a <br> for line breaks, set this to false to use <p>
      useLineBreaks:        true,
      // Array (or single string) of stylesheet urls to be loaded in the editor's iframe
      stylesheets:          [gdn.definition('editorPluginAssets') + 'design/editor.css'],
      // Placeholder text to use, defaults to the placeholder attribute on the textarea element
      placeholderText:      "Write something!",
      // Whether the composer should allow the user to manually resize images, tables etc.
      allowObjectResizing:  true,
      // Whether the rich text editor should be rendered on touch devices (wysihtml5 >= 0.3.0 comes with basic support for iOS 5)
      supportTouchDevices:  true,
      // Whether senseless <span> elements (empty or without attributes) should be removed/replaced with their content
      cleanUp:              true   
   };
   
   // instantiate new editor
   var editor = new wysihtml5.Editor(editorTextareaId, editorRules);

   // attaching editor on-the-fly when editing comments in discussion
   $('a.EditComment').on('click', function() {

      var btn = this;
      var container = $(btn).closest('.ItemComment');

      // the list id in the comment views, use it to replace inline editor
      var currentEditableCommentId = container[0].id;
      
      // This will load content when change in DOM, but wysihtml5 code crashes 
      // browser, so use timeout.
      $(container).find('.Item-Body').on('DOMNodeInserted', function(e) {
         // do something after the div content has changed
         // if no check for this, will loop till crash
         if ($(this).find('#Form_Body').length) {
            
            console.log('fire');
            
            var currentEditableTextarea = $('#Form_Body');
            //var currentEditorWrapper = $(container).find('.TextBoxWrapper');
            var currentEditorToolbar = $(container).find('.wysihtml5-toolbar');

            var editorTextareaId = 'Form_Body_'+ currentEditableCommentId;
            var editorToolbarId  = 'wysihtml5-toolbar_'+ currentEditableCommentId;
            var editorName       = 'vanilla-editor-text_'+ currentEditableCommentId;

            // change ids to bind new editor functionality to particular edit
            $(currentEditorToolbar)
               .attr('id', editorToolbarId)
               .addClass('editorInlineGenerated');

            $(currentEditableTextarea).attr('id', editorTextareaId); 

            // rules updated for particular edit, look to editorRules for 
            // reference. Any defined here will overwrite the defaults set above.
            var editorRulesOTF = {
               name: editorName,
               toolbar: editorToolbarId      
            };

            // overwrite defaults with specific rules for this edit
            for (var dfc in editorRules) {
               if (typeof editorRulesOTF[dfc] == 'undefined') {
                  editorRulesOTF[dfc] = editorRules[dfc];
               }
            }

            // instantiate new editor
            var editorInline = new wysihtml5.Editor(editorTextareaId, editorRulesOTF);

            editorInline.on('load', function() {
               // enable auto-resize
               $(editorInline.composer.iframe).wysihtml5_size_matters();      
            });
         }
      });
      
 
   });  
   
   // load resizer
   editor.on('load', function() {
      // enable auto-resize
      $(editor.composer.iframe).wysihtml5_size_matters();      
   });

   // Toggle fulpage--to be used in three places
   var toggleFullpage = function() { 
      var toggleButton = $("#editor-toggle-fullpage-button");
      var bodyEl = $('body');
      if (!bodyEl.hasClass('js-editor-fullpage')) {
         bodyEl.addClass('js-editor-fullpage');
         toggleButton.addClass('icon-resize-small');
      } else {
         bodyEl.removeClass('js-editor-fullpage');
         toggleButton.removeClass('icon-resize-small');
      }  
   }
   
   // Toggle fullpage on click (from toggler, or posting new comment)
   $("#editor-toggle-fullpage-button").click(toggleFullpage);
   
   // exit fullpage on esc
   $(document).keyup(function(e) {
      if ($('body').hasClass('js-editor-fullpage') && e.which == 27) {
         toggleFullpage();
      }
   });  
   
   // If full page and the user posts comment, exit out of full page.
   // Not smart in the sense that a failed post will also exit out of 
   // full page, but the text will remain in editor, so not big issue.
   $('.CommentButton').click(function() {
      if ($('body').hasClass('js-editor-fullpage')) { 
         toggleFullpage();
      }
   });  
   
});


// extending whysihtml5 library for spoilers
(function(wysihtml5) {
  var undef,
      REG_EXP = /Spoiler/g;
  
  wysihtml5.commands.spoiler = {
    exec: function(composer, command) {
      //return wysihtml5.commands.formatInline.exec(composer, command, "div", "Spoiler", REG_EXP);
      wysihtml5.commands.formatBlock.exec(composer, "formatBlock", "div", "Spoiler", REG_EXP);

      // If block element chosen from last string in editor, there is no way to 
      // click out of it and continue typing below it, so set the selection 
      // after the insertion, and insert a break, because that will set the 
      // caret to after the latest insertion. 
      composer.selection.setAfter(composer.element.lastChild);
      composer.commands.exec("insertHTML", "<br>");
    },

    state: function(composer, command) {
      return wysihtml5.commands.formatBlock.state(composer, "formatBlock", "div", "Spoiler", REG_EXP);
    },

    value: function() {
      return undef;
    }
  };
})(wysihtml5);

// extending whysihtml5 library for blockquotes
(function(wysihtml5) {
  var undef,
      REG_EXP = /Quote/g;
  
  wysihtml5.commands.blockquote = {
    exec: function(composer, command) {
      wysihtml5.commands.formatBlock.exec(composer, "formatBlock", "blockquote", "Quote", REG_EXP);
      composer.selection.setAfter(composer.element.lastChild);
      composer.commands.exec("insertHTML", "<br>");
    },

    state: function(composer, command) {
      return wysihtml5.commands.formatBlock.state(composer, "formatBlock", "blockquote", "Quote", REG_EXP);
    },

    value: function() {
      return undef;
    }
  };
})(wysihtml5);

// extending whysihtml5 library for code blocks
(function(wysihtml5) {
  var undef,
      REG_EXP = /CodeBlock/g;
  
  wysihtml5.commands.code = {
    exec: function(composer, command) {
      wysihtml5.commands.formatBlock.exec(composer, "formatBlock", "blockquote", "CodeBlock", REG_EXP);
      composer.selection.setAfter(composer.element.lastChild);
      composer.commands.exec("insertHTML", "<br>");
    },

    state: function(composer, command) {
      return wysihtml5.commands.formatBlock.state(composer, "formatBlock", "blockquote", "CodeBlock", REG_EXP);
    },

    value: function() {
      return undef;
    }
  };
})(wysihtml5);