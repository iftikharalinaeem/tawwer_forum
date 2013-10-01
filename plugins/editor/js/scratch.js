
// misc code in dev





/*
   // attaching editor on-the-fly when editing comments in discussion
   $('a.EditComment').on('click', function() {

      var btn = this;
      var container = $(btn).closest('.ItemComment');

      // the list id in the comment views, use it to replace inline editor
      var currentEditableCommentId = container[0].id;
      
      // This will load content when change in DOM
      $(container).find('.Item-Body').on('DOMNodeInserted', function(e) {
         
         // do something after the div content has changed
         // if no check for this, will loop till crash
         if ($(this).find('#Form_Body').length) {
            
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
   
*/







/**
    * Load correct editor view onload
    */
   switch (format) {

      case 'wysiwyg#FixASAP':
         // Slight flicker where textarea content is visible initially
         $(currentEditableTextarea).css('visibility', 'hidden');

         // Lazyloading scripts, then run single callback
         $.when(
            loadScript(assets + '/js/wysihtml5-0.4.0pre.js'),
            loadScript(assets + '/js/advanced.js'),
            loadScript(assets + '/js/jquery.wysihtml5_size_matters.js')
         ).done(function(){
  
            /**
             * Default editor values when first instantiated on page. Later, on DOM
             * mutations, these values are updated per editable comment.
             */
            editorRules = {
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
               useLineBreaks:        false,
               // Array (or single string) of stylesheet urls to be loaded in the editor's iframe
               stylesheets:          stylesheetsInclude,
               // Placeholder text to use, defaults to the placeholder attribute on the textarea element
               placeholderText:      "Write something!",
               // Whether the composer should allow the user to manually resize images, tables etc.
               allowObjectResizing:  true,
               // Whether the rich text editor should be rendered on touch devices (wysihtml5 >= 0.3.0 comes with basic support for iOS 5)
               supportTouchDevices:  true,
               // Whether senseless <span> elements (empty or without attributes) should be removed/replaced with their content
               cleanUp:              true   
            };

            /**
             * Instantiate new editor on page load, against editor already in DOM 
             */
            var editor = new wysihtml5.Editor($(currentEditableTextarea)[0], editorRules);

            // load resizer
            editor.on('load', function() {
                              
               $(editor.composer.iframe).wysihtml5_size_matters();
                              
               // Make visible again for Html toggling.
               $(currentEditableTextarea).css('visibility', '');  
               editorHandleQuotesPlugin(editor);

               // Clear textarea/iframe content on submit.
               $(currentEditableTextarea.closest('form')).on('clearCommentForm', function() {
                  editor.fire('clear');
                  editor.composer.clear();
                  this.reset();
                  $(currentEditableTextarea).val('');
                  //$('iframe').contents().find('body').empty();
                  
                  // The editor is not resized on post, so reset it.
                  $(editor.composer.iframe).css({"min-height": "inherit"});
               });            
               
               // Some browsers modify pasted content, adding superfluous tags.
               wysiPasteFix(editor);

               if (debug) {
                  wysiDebug(editor);
               }
               
            });
            

            /**
             * Extending functionality of wysihtml5.js
             */

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
                  if ($(composer.element.lastChild).hasClass('Spoiler')) {
                     composer.selection.setAfter(composer.element.lastChild);
                     composer.commands.exec("insertHTML", "<br>");
                  }
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
                  if ($(composer.element.lastChild).hasClass('Quote')) {
                     composer.selection.setAfter(composer.element.lastChild);
                     composer.commands.exec("insertHTML", "<br>");
                  }
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
                  wysihtml5.commands.formatBlock.exec(composer, "formatBlock", "pre", "CodeBlock", REG_EXP);
                  if ($(composer.element.lastChild).hasClass('CodeBlock')) {
                    composer.selection.setAfter(composer.element.lastChild);
                    composer.commands.exec("insertHTML", "<br>");
                  }
                },

                state: function(composer, command) {
                  return wysihtml5.commands.formatBlock.state(composer, "formatBlock", "blockquote", "CodeBlock", REG_EXP);
                },

                value: function() {
                  return undef;
                }
              };
            })(wysihtml5);
         });
         
         break;

      case 'html#FixASAP':
      case 'bbcode#FixASAP':
      case 'markdown#FixASAP':         
         // Lazyloading scripts, then run single callback
         $.when(
            loadScript(assets + '/js/buttonbarplus.js'),
            loadScript(assets + '/js/jquery.hotkeys.js'),
            loadScript(assets + '/js/rangy.js')
         ).done(function(){
            ButtonBar.AttachTo($(currentEditableTextarea)[0], formatOriginal);            
         });
         
         break;
   }