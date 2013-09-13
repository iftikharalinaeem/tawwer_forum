// TODO refactor as jQuery plugin. Allow passing arguments as json, check for
// defaults. Enable calling plugin and it just works. 

jQuery(function() {

   // If editor can be loaded, add class to body 
   $('body').addClass('js-editor-active');
   
   // Enable caching of asynced assets for each editor view
   $.ajaxSetup({
      cache: true
   });
   
   /**
    * Determine editor format to load, and asset path, default to Wysiwyg
    */
   var 
       editor, editorInline,
       debug          = false,
       formatOriginal = gdn.definition('editorInputFormat', 'Wysiwyg'),
       format         = formatOriginal.toLowerCase(),
       assets         = gdn.definition('editorPluginAssets'), 
       editorRules    = {}; // for wysiwyg
       
       
  // When editor loaded inline and accomodates an editor format based on the 
  // original post format.

   var editorToolbarId  = 'editor-format-', // append format below
       editorTextareaId = 'Form_Body', 
       editorName       = 'vanilla-editor-text';
       

   var currentEditableTextarea = $('#Form_Body');
   var currentTextBoxWrapper   = currentEditableTextarea.parent('.TextBoxWrapper');
   var currentEditorFormat     = $('#Form_Format')[0].value.toLowerCase();
   
   
   format = (currentEditorFormat !== format) 
      ? currentEditorFormat 
      : format;
   
   editorToolbarId += currentEditorFormat;
   
   
   // Set id of onload toolbar--required for proper functioning
   $('.editor').attr('id', editorToolbarId);
   
   
   
   
   
   
   
   
   /**
    * Load correct editor view onload
    */
   
   switch (format) {
      
      /**
       * Wysiwyg editor
       */
      case 'wysiwyg':
         
         // Slight flicker where textarea content is visible initially
         $(currentEditableTextarea).css('visibility', 'hidden');
         
         // Load script for wysiwyg editor async
         $.getScript(assets + "/js/wysihtml5.js", function(data, textStatus, jqxhr) {
                        
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
               stylesheets:          [assets + '/design/editor.css'],
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
            var editor = new wysihtml5.Editor(editorTextareaId, editorRules);

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
      
      /**
       * HTML editor 
       * BBCode editor 
       * Markdown editor 
       */
      case 'html':
      case 'bbcode':
      case 'markdown':         
      
         // Load script for wysiwyg editor async
         $.getScript(assets + "/js/buttonbarplus.js", function(data, textStatus, jqxhr) {
            ButtonBar.AttachTo($('#'+editorTextareaId), formatOriginal);
         });
         
         break;
   }
   
   
   
   
   
   
   
   
   

   
   /**
    * Mutation observer for attaching editor to every .BodyBox that's inserted 
    * into the DOM. Consider using livequery for wider support.
    * 
    * TODO abstracting the mutation anon function and then calling it as a 
    * callback on mutation, but also just when manually invoked, so less 
    * redundant with onload above.
    * 
    * TODO make onload call this, essentially moving all into one, less
    * redundant.
    */
   $(document).ready(function() {

     // If there are any changes in this node, check for bodyBox.
     var mutationTarget = $('#Content')[0];

     // configuration of the observer:
     var config = { 
        attributes: false, 
        childList: true, 
        characterData: false, 
        subtree: true 
     }

     // All modern browsers, except ie11=<
     // Can use deprecated mutation oberservers.
     // Polyfill available: https://github.com/Polymer/MutationObservers/blob/stable/MutationObserver.js
     var observer = new MutationObserver(function(mutations) {

        // use some loop, return true after first match, because will 
        // list multiple mutations on the same element otherwise. ie8<
        mutations.some(function(mutation) { 

           var t                       = $(mutation.target);
           var currentEditorFormat     = t.find('#Form_Format');
           var currentEditorToolbar    = '';
           var currentEditableTextarea = '';
           var currentTextBoxWrapper   = '';

           if (currentEditorFormat.length) {
     
               formatOriginal = currentEditorFormat[0].value;
               //currentEditorFormat = $(currentEditorFormat).val();
               currentEditorFormat = currentEditorFormat[0].value.toLowerCase();
               format = currentEditorFormat + '';
               
               currentEditorToolbar    = t.find('.editor-format-'+ format);
               currentEditableTextarea = t.find('#Form_Body');
               currentTextBoxWrapper   = currentEditableTextarea.parent('.TextBoxWrapper');
           }      
           
           // if found, perform operation
           if (currentEditorToolbar.length 
           && currentEditableTextarea.length) {

              var currentEditableCommentId = (new Date()).getTime(),
                  editorTextareaId         = currentEditableTextarea[0].id +'-'+ currentEditableCommentId,
                  editorToolbarId          = 'editor-format-'+ format +'-'+ currentEditableCommentId,
                  editorName               = 'vanilla-editor-text-'+ currentEditableCommentId;

              // change ids to bind new editor functionality to particular edit
              $(currentEditorToolbar)
                 .attr('id', editorToolbarId)
                 .addClass('editor-inline-otf');

              $(currentEditableTextarea).attr('id', editorTextareaId); 

              // TODO add these as functions in an object, then just invoke
              // them on format
              switch (format) {
                 case 'wysiwyg':
                    $.getScript(assets + "/js/wysihtml5.js", function(data, textStatus, jqxhr) {
                        // rules updated for particular edit, look to editorRules for 
                        // reference. Any defined here will overwrite the defaults set above.
                        var editorRulesOTF = {
                           name: editorName,
                           toolbar: editorToolbarId      
                        };

                        // overwrite defaults with specific rules for this edit
                        for (var dfr in editorRules) {
                           if (typeof editorRulesOTF[dfr] == 'undefined') {
                              editorRulesOTF[dfr] = editorRules[dfr];
                           }
                        }

                        // instantiate new editor
                        var editorInline = new wysihtml5.Editor(editorTextareaId, editorRulesOTF);

                        editorInline.on('load', function() {
                           // enable auto-resize
                           $(editorInline.composer.iframe).wysihtml5_size_matters();  
                           editorHandleQuotesPlugin(editorInline);
                           
                           // Clear textarea/iframe content on submit.
                           $(currentEditableTextarea.closest('form')).on('clearCommentForm', function() {
                              editor.fire('clear');
                              editor.composer.clear();
                              this.reset();                       
                           });
                           
                           wysiPasteFix(editorInline);
                           
                           if (debug) {
                              wysiDebug(editorInline);
                           }
                        });
                    });
                  break;
                  
                  case 'html':
                  case 'bbcode':
                  case 'markdown': 

                     $.getScript(assets + "/js/buttonbarplus.js", function(data, textStatus, jqxhr) {
                        ButtonBar.AttachTo($('#'+editorTextareaId), formatOriginal);
                     });                  
                     break;
              }

              // Set up on editor load
              editorSetHelpText(formatOriginal, currentTextBoxWrapper);
              editorSetupDropdowns();
              fullPageInit(editorInline);
              editorSetCaretFocusEnd(currentEditableTextarea[0]);

              // some() loop requires true to end loop. every() requires false.
              return true;
           }
        });

     });

     // start observing, call observer.disconnect() to stop
     observer.observe(mutationTarget, config);
   });   
   
   
   
   
   
   
   
   
   
   
   /** 
    * Fullpage actions--available to all editor views on page load. 
    */
   var fullPageInit = function(wysiwygInstance) {
      
      var toggleFullpage = function(e) {
         // either user clicks on fullpage toggler button, or escapes out with key
         var toggleButton = (typeof e != 'undefined') 
            ? e.target 
            : $('#editor-fullpage-candidate').find('.editor-toggle-fullpage-button');

         var bodyEl      = $('body'); 
             formWrapper = $(toggleButton).closest('.FormWrapper')[0];
         
         // Not all parts of the site have same surrounding markup, so if that 
         // fails, grab nearest parent element that might enclose it. The 
         // exception this was made for is the signatures plugin.
         if (typeof formWrapper == 'undefined') {
            formWrapper = $(toggleButton).parent().parent();
         }
         
         // If no fullpage, enable it
         if (!bodyEl.hasClass('js-editor-fullpage')) {
            $(formWrapper).attr('id', 'editor-fullpage-candidate');
            bodyEl.addClass('js-editor-fullpage');
            $(toggleButton).addClass('icon-resize-small');
            window.scrollTo(0, 0);
         } else {
            // else disable fullpage
            $(formWrapper).attr('id', '');
            bodyEl.removeClass('js-editor-fullpage');
            $(toggleButton).removeClass('icon-resize-small');
            
            // Auto scroll to correct location upon exiting fullpage.
            var scrollto = $(toggleButton).closest('.Comment');
            if (!scrollto.length) {
               scrollto = $(toggleButton).closest('.CommentForm');
            }
            
            // Just in case I haven't covered all bases.
            if (scrollto.length) {
                $('html, body').animate({
                   scrollTop: $(scrollto).offset().top
                }, 400);
             }
 
             // Buggy right now. Go back and fix.
             // Set focus--wysiwyg is slightly different
            if (typeof wysiwygInstance != 'undefined') {
               //wysiwygInstance.fire("focus");
               console.log('wysi');
            } else {
                  console.log('foo');
               editorSetCaretFocusEnd($(formWrapper).find('.BodyBox')[0]);
            }
         }
      }
      
      var clickFullPage = (function() {
         $(".editor-toggle-fullpage-button")
         .off('click')
         .on('click', toggleFullpage);
      }());
      
      // exit fullpage on esc
      var closeFullPageEsc = (function() {
         $(document)
         .off('keyup')
         .on('keyup', function(e) {
            if ($('body').hasClass('js-editor-fullpage') && e.which == 27) {
               toggleFullpage();
            }
         });  
      }());

      // If full page and the user saves/cancels/previews comment, 
      // exit out of full page.
      // Not smart in the sense that a failed post will also exit out of 
      // full page, but the text will remain in editor, so not big issue.
      var postCommentCloseFullPageEvent = (function() {
         $('.Button')
         .off('click.closefullpage')
         .on('click.closefullpage', function() {
            if ($('body').hasClass('js-editor-fullpage')) { 
               toggleFullpage();
            }
         });   
      }()); 
   };

   // TODO when previewing a post, then going back to edit, the text help
   // message will display again and again, and all the events will be 
   // reattached. Consider namespacing events, so they overwrite.
   // Insert help text below every editor 
   var editorSetHelpText = function(format, editorAreaObj) {
      if (format != 'Wysiwyg') {
         $("<div></div>")
            .addClass('editor-help-text')
            .html(gdn.definition('editor'+ format +'HelpText'))
            .insertAfter(editorAreaObj);
      }
    };

    var editorSetCaretFocusEnd = function(obj) {
       obj.selectionStart = obj.selectionEnd = obj.value.length;
       // Hack to work around jQuery's autogrow, which requires focus to init 
       // the feature, but setting focus immediately here prevents that. 
       // Considered using trigger() and triggerHandler(), but do not work.
       setTimeout(function(){
         obj.focus();
       }, 50);
    };
    
    var editorSelectAllInput = function(obj) {
       // selectionStart is implied 0
       obj.selectionEnd = obj.value.length;
       obj.focus();
    };

   /**
    * Deal with clashing JS for opening dialogs on click, and do not let 
    * more than one dialog/dropdown appear at once. 
    * 
    * TODO enable enter button to do the same as clicks, or disable enter.
    */
   var editorSetupDropdowns = function() { 
      $('.editor-dropdown')
      .off('click.dd')
      .on('click.dd', function(e) {
         var parentEl = $(e.target).parent();
         
         // Again, tackling with clash from multiple codebases.
         $('.editor-insert-dialog').each(function(i, el) {
            setTimeout(function() {
               $(el).removeAttr('style');
            }, 0);
         });

         if ($(this).hasClass('editor-dropdown') 
         && $(this).hasClass('editor-dropdown-open')) {
            parentEl.removeClass('editor-dropdown-open');
            //$(parentEl).find('.wysihtml5-command-dialog-opened').removeClass('wysihtml5-command-dialog-opened');
         } else {
            // clear other opened dropdowns before opening this one
            $(this).parent('.editor').find('.editor-dropdown-open').each(function(i, el) {
               $(el).removeClass('editor-dropdown-open');
               $(el).find('.wysihtml5-command-dialog-opened').removeClass('wysihtml5-command-dialog-opened');
            });

            parentEl.addClass('editor-dropdown-open');
            
            // if has input, focus and move caret to end of text
            var inputBox = $(this).find('.InputBox');
            if (inputBox.length) {
               editorSelectAllInput(inputBox[0]);
            }
         }
      });
      
      // TODO bug when post-dependent editor loaded, loses events.
      
      // if dropdown open, cliking into an editor area should close it, but 
      // keep it open for anything else.
      $('.TextBoxWrapper').each(function(i, el) {
         $(el).addClass('editor-dialog-fire-close');
      });

      $('.editor-dialog-fire-close')
      .off('mouseup.fireclose')
      .on('mouseup.fireclose', function(e) {
         $('.editor-dropdown').each(function(i, el) {
            
             //console.log(el);
            
            $(el).removeClass('editor-dropdown-open');
            $(el).find('.wysihtml5-command-dialog-opened').removeClass('wysihtml5-command-dialog-opened');
         }); 
      });
   };
   
   // Editor does not play well with Quotes plugin in Wysiwyg mode. 
   var editorHandleQuotesPlugin = function(editorInstance) {
      var editor = editorInstance;
     // handle Quotes plugin
      $('a.ReactButton.Quote').on('click', function(e) {
         // Stop animation from other plugin and let this one 
         // handle the scroll, otherwise the scrolling jumps 
         // all over, and really distracts the eyes. 
         $('html, body').stop().animate({
            scrollTop: $(editor.textarea.element).parent().parent().offset().top
         }, 800);

         // For the quotes plugin to insert the quoted text, it 
         // requires that the textarea be pastable, which is not true 
         // when not displayed, so momentarily toggle to it, then, 
         // unavoidable, wait short interval to then allow wysihtml5
         // to toggle back and render the content.
         editor.fire("change_view", "textarea");
         setTimeout(function() {
            editor.fire("change_view", "composer");
            editor.fire("focus:composer");
            // Inserting a quote at the end prevents editor from 
            // breaking out of quotation, which means everything 
            // typed after the inserted quotation, will be wrapped 
            // in a blockquote.
            editor.composer.selection.setAfter(editor.composer.element.lastChild);
            editor.composer.commands.exec("insertHTML", "<br>");
         }, 400);
         
      }); 
   };
   
   var wysiPasteFix = function(editorInstance) {
      var editor = editorInstance;
      // Chrome wraps span around content. Firefox prepends b.
      // No real check to check browsers. 
      editor.observe("paste:composer", function(e) {
         // Grab paste value
         var paste = this.composer.getValue();
         // Just need to remove first one, and wysihtml5 will auto
         // make sure the pasted html has all tags closed, so the 
         // last will just be stripped automatically. sweet.
         paste = paste.replace(/^<(span|b)>/m, ''); // just match first
         // Insert into composer
         this.composer.setValue(paste);
      });
   };
   

   // Set up on page load
   editorSetHelpText(formatOriginal, $('#Form_Body'));
   editorSetupDropdowns();
   fullPageInit(editor);
   editorSetCaretFocusEnd(currentEditableTextarea[0]);
   
   
   // This will only be called when debug=true;
   var wysiDebug = function(editorInstance) {
      editorInstance.on("load", function() {
        console.log('load');
      })
      .on("focus", function() {
        console.log('focus');
      })
      .on("blur", function() {
        console.log('blur');
      })
      .on("change", function() {
        console.log('change');
      })
      .on("paste", function() {
        console.log('paste');
      })
      .on("newword:composer", function() {
        console.log('newword:composer');
      })
      .on("undo:composer", function() {
        console.log('undo:composer');
      })
      .on("redo:composer", function() {
        console.log('redo:composer');
      })
      .on("change:textarea", function() {
        console.log('change:textarea');
      })
      .on("change:composer", function() {
        console.log('change:composer');
      })
      .on("paste:textarea", function() {
        console.log('paste:textarea');
      })
      .on("paste:composer", function() {
        console.log('paste:composer');
      })
      .on("blur:composer", function() {
        console.log('change:composer');
      })
      .on("blur:textarea", function() {
        console.log('change:composer');
      })
      .on("beforecommand:composer", function() {
        console.log('beforecommand:composer');
      })
      .on("aftercommand:composer", function() {
        console.log('aftercommand:composer');
      }); 
   };
});


// Event examples that will come in handy--taken from source. 
//editor.fire("change_view", "composer");
//editor.fire("change_view", "textarea");
//this.editor.observe("change_view", function(view) {
//this.editor.observe("destroy:composer", stopInterval);
//editor.setValue('This will do it.');