(function($) {
   $.fn.setAsEditor = function() {

      // If editor can be loaded, add class to body 
      $('body').addClass('js-editor-active');

      /**
       * Determine editor format to load, and asset path, default to Wysiwyg
       */
      var editor, 
          editorName, 
          editorRules             = {}, // for Wysiwyg
          editorCacheBreakValue   = Math.random(), 
          editorVersion           = gdn.definition('editorVersion', editorCacheBreakValue),
          formatOriginal          = gdn.definition('editorInputFormat', 'Wysiwyg'),
          format                  = formatOriginal.toLowerCase(),
          assets                  = gdn.definition('editorPluginAssets', '/plugins/editor'), 
          debug                   = false;

      /**
       * Load relevant stylesheets into editor iframe. The first one loaded is 
       * the actual editor.css required for the plugin. The others are extra, 
       * grabbed from the source of parent to iframe, as different communities 
       * may have styles they want injected into the iframe. 
       */
      
      if (debug) {
         editorVersion += '&cachebreak=' + editorCacheBreakValue;
      }

      var stylesheetsInclude = [assets + '/design/editor.css?v=' + editorVersion];

      /*
      // If you want to include other stylsheets from the main page in the iframe.
      $('link').each(function(i, el) {
         if (el.href.indexOf("style.css") !== -1 || el.href.indexOf("custom.css") !== -1) {
            stylesheetsInclude.push(el.href);
         }
      });
      */

      // Some communities may want to modify just the styling of the Wysiwyg 
      // while editing, so this file will let them. 
      var editorWysiwygCSS = gdn.definition('editorWysiwygCSS', '');
      if (editorWysiwygCSS != '') {
         stylesheetsInclude.push(editorWysiwygCSS + '?v=' + editorVersion);
      }

      /** 
       * Fullpage actions--available to all editor views on page load. 
       */
      var fullPageInit = function(wysiwygInstance) {

         // Hack to push toolbar left 15px when vertical scrollbar appears, so it 
         // is always aligned with the textarea. This is for clearing interval.
         var toolbarInterval;

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

               var fullPageCandidate = $('#editor-fullpage-candidate');
               var editorToolbar = $(fullPageCandidate).find('.editor');

              // experimental lights toggle for chrome.
              toggleLights();

               // When textarea pushes beyond viewport of its container, a 
               // scrollbar appears, which pushes the textarea left, while the 
               // fixed editor toolbar does not move, so push it over.
               // Opted to go this route because support for the flow events is 
               // limited, webkit/moz both have their own implementations, while 
               // IE has no support for them. See below for example, commented out.

               // Only Firefox seems to have this issue (unless this is
               // mac-specific. Chrome & Safari on mac do not shift content over.
               if (typeof InstallTrigger !== 'undefined' && 1===3) {
                  toolbarInterval = setInterval(function() {
                     if ($(fullPageCandidate)[0].clientHeight < $(fullPageCandidate)[0].scrollHeight) {
                        // console.log('scrollbar');
                        $(editorToolbar).css('right', '15px');
                     } else {
                        // console.log('no scrollbar');
                        $(editorToolbar).css('right', '0');
                     }
                  }, 10);
               }
            } else {
               clearInterval(toolbarInterval);

               // wysiwhtml5 editor area sometimes overflows beyond wrapper 
               // when exiting fullpage, and it reflows on window resize, so 
               // trigger resize event to get it done. 
               $('.'+editorName).css('width', '100%');

               // else disable fullpage
               $(formWrapper).attr('id', '');
               bodyEl.removeClass('js-editor-fullpage');
               $(toggleButton).removeClass('icon-resize-small');

               // for experimental chrome lights toggle
               $('.editor-toggle-lights-button').attr('style', '');

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
            }
            
            // Set focus to composer when going fullpage and exiting.
            if (typeof wysiwygInstance != 'undefined') {
               wysiwygInstance.focus();
            } else {
               editorSetCaretFocusEnd($(formWrapper).find('.BodyBox')[0]);
            }
         };

         /**
          * Attach fullpage toggling events 
          */

         // click fullpage 
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

         /**
          * If full page and the user saves/cancels/previews comment, 
          * exit out of full page.
          * Not smart in the sense that a failed post will also exit out of 
          * full page, but the text will remain in editor, so not big issue.
          */
         var postCommentCloseFullPageEvent = (function() {
            $('.Button')
            .off('click.closefullpage')
            .on('click.closefullpage', function() {
               // Prevent auto-saving drafts from exiting fullpage
               if (!$(this).hasClass('DraftButton')) {
                  if ($('body').hasClass('js-editor-fullpage')) { 
                     toggleFullpage();
                  }
               }
            });   
         }()); 

         /** 
          * Toggle spoilers in posted messages.
          */
         var editorToggleSpoiler = (function() {
            // Use event delegation, so that even new comments ajax posted 
            // can be toggled 
            $('.MessageList')
            .on('mouseup.Spoiler', '.Spoiler', function(e) {            
               $(this).removeClass('Spoiler');
               $(this).addClass('Spoiled');
            })
            .on('mouseup.Spoiled', '.Spoiled', function(e) {
               // If the user selects some text, don't close the spoiler, and 
               // if there is an anchor in spoiler, do not close spoiler.
               if (!document.getSelection().toString().length 
               && e.target.nodeName.toLowerCase() != 'a') {
                  $(this).removeClass('Spoiled');
                  $(this).addClass('Spoiler');
               }
            });
         }());

         /**
          * Lights on/off in fullpage--experimental for chrome
          */
         var toggleLights = function() {
            // Just do it for chrome right now. Very experimental.
            if (window.chrome) {
               var toggleLights = $('.editor-toggle-lights-button');           
               $(toggleLights).attr('style', 'display:inline-block !important').off('click').on('click', function() {
                  var fullPageCandidate = $('#editor-fullpage-candidate');
                  if (!$(fullPageCandidate).hasClass('editor-lights-candidate')) {
                     $(fullPageCandidate).addClass('editor-lights-candidate');
                  } else {
                     $(fullPageCandidate).removeClass('editor-lights-candidate');
                  }
               });
            }
         };
         
      };

      /**
       * When rendering editor, load correct helpt text message
       */
      var editorSetHelpText = function(format, editorAreaObj) {
         format = format.toLowerCase();
         if (format != 'wysiwyg') {
            // If the helpt text is already there, don't insert it again.
            if (!$(editorAreaObj).parent().find('.editor-help-text').length) {
               $("<div></div>")
                  .addClass('editor-help-text')
                  .html(gdn.definition(format +'HelpText'))
                  .insertAfter(editorAreaObj);
            }
         }
       };

       /**
        * For non-wysiwyg views. Wysiwyg focus() automatically places caret 
        * at the end of a string of text. 
        */
       var editorSetCaretFocusEnd = function(obj) {
          obj.selectionStart = obj.selectionEnd = obj.value.length;
          // Hack to work around jQuery's autogrow, which requires focus to init 
          // the feature, but setting focus immediately here prevents that. 
          // Considered using trigger() and triggerHandler(), but do not work.
          setTimeout(function(){
            obj.focus();
          }, 250);
       };

       /**
        * Helper function to select whole text of an input or textarea on focus
        */
       var editorSelectAllInput = function(obj) {
          // selectionStart is implied 0
          obj.selectionEnd = obj.value.length;
          obj.focus();
       };

      /**
       * Deal with clashing JS for opening dialogs on click, and do not let 
       * more than one dialog/dropdown appear at once. 
       */
      var editorSetupDropdowns = function(editorInstance) { 
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
         
         // For now, do not let Enter key close and insert text, as it 
         // causes buggy behaviour with dropdowns.
         $('.InputBox').on('keydown', function(e) {
            if (e.which == 13) {
               e.stopPropagation();
               e.preventDefault();
               return false;
            }
         });

         // Clicking into an editor area should close the dropdown, but keep 
         // it open for anything else.
         $('.TextBoxWrapper').add($('.wysihtml5-sandbox').contents().find('html')).each(function(i, el) {
            $(el).addClass('editor-dialog-fire-close');
         });

         // Target all elements in the document that fire the dropdown close 
         // (some are written directly as class in view), then add the matches 
         // from within the iframe, and attach the relevant callbacks to events.
         $('.editor-dialog-fire-close').add($('.wysihtml5-sandbox').contents().find('.editor-dialog-fire-close'))
         .off('mouseup.fireclose')
         .on('mouseup.fireclose', function(e) {   
            $('.editor-dropdown').each(function(i, el) {
               $(el).removeClass('editor-dropdown-open');
               $(el).find('.wysihtml5-command-dialog-opened').removeClass('wysihtml5-command-dialog-opened');
            }); 
         });
      };

      /**
       * Editor does not play well with Quotes plugin in Wysiwyg mode. 
       */
      var editorHandleQuotesPlugin = function(editorInstance) {
         var editor = editorInstance;      

         // handle Quotes plugin using own logic.
         $('.MessageList')
         .on('mouseup.QuoteReply', 'a.ReactButton.Quote', function(e) {            
            // For the quotes plugin to insert the quoted text, it 
            // requires that the textarea be pastable, which is not true 
            // when not displayed, so momentarily toggle to it, then, 
            // unavoidable, wait short interval to then allow wysihtml5
            // to toggle back and render the content.
            editor.fire("change_view", "textarea");
            $(editor.textarea.element).css({"opacity":"0.50"});
            var initialText = $(editor.textarea.element).val();
            var si = setInterval(function() {
               if ($(editor.textarea.element).val() != initialText) {
                  clearInterval(si);
                  $(editor.textarea.element).css({"opacity":""});
                  editor.fire("change_view", "composer");
                  editor.fire("focus:composer");
                  // Inserting a quote at the end prevents editor from 
                  // breaking out of quotation, which means everything 
                  // typed after the inserted quotation, will be wrapped 
                  // in a blockquote.
                  editor.composer.selection.setAfter(editor.composer.element.lastChild);
                  editor.composer.commands.exec("insertHTML", "<p></p>");
               }
            }, 0);
         });
         
         /*
         // Handle quotes plugin using triggered event.
         $('a.ReactButton.Quote').on('click', function(e) {
            // Stop animation from other plugin and let this one
            // handle the scroll, otherwise the scrolling jumps
            // all over, and really distracts the eyes.
            $('html, body').stop().animate({
               scrollTop: $(editor.textarea.element).parent().parent().offset().top
            }, 800);
         });
         
         $(editor.textarea.element).on('appendHtml', function(e, data) {
            editor.composer.commands.exec("insertHTML", data);
            editor.composer.commands.exec("insertHTML", "<p></p>");
            editor.fire("change_view", "composer");
            editor.focus();
         });
         */
      };
      
      /**
       * This is just to make sure that editor, upon choosing to edit an 
       * inline post, will be scrolled to the correct location on the page. 
       * Some sites may have plugins that interfere on edit, so take care of 
       * those possibilities here.
       */
      var scrollToEditorContainer = function(textarea) {
         var scrollto = $(textarea).closest('.Comment');
         
         if (!scrollto.length) {
            scrollto = $(textarea).closest('.CommentForm');
         }

         if (scrollto.length) {
            $('html, body').animate({
               scrollTop: $(scrollto).offset().top
            }, 400);
         } 
      };

      /**
       * Chrome wraps span around content. Firefox prepends b.
       * No real need to detect browsers.
       */ 
      var wysiPasteFix = function(editorInstance) {
         var editor = editorInstance;
         editor.observe("paste:composer", function(e) {
            // Cancel out this function's operation for now. On the original 
            // 0.3.0 version, pasting google docs would wrap either a span or 
            // b tag around the content. Now, since moving to 0.4.0pre, the 
            // paragraphing messes this up severaly. Moreover, pasting 
            // through this function sets caret to end of composer. 
            // Originally found this bug through Kixeye mentioning paste 
            // issue, which opened up larger issue of pasting with new version 
            // of wysihtml5. For now, disable paste filtering to make sure 
            // pasting and the caret remain in same position. 
            // TODO. 
            //return; 
            // Grab paste value
            ////var paste = this.composer.getValue();
            // Just need to remove first one, and wysihtml5 will auto
            // make sure the pasted html has all tags closed, so the 
            // last will just be stripped automatically. sweet.
            ////paste = paste.replace(/^<(span|b)>/m, ''); // just match first
            // Insert into composer
            ////this.composer.setValue(paste);
         });
      };


      /**
       * Debugging lazyloaded scripts impossible with jQuery getScript/get, 
       * so make sure available. 
       * 
       * http://balpha.de/2011/10/jquery-script-insertion-and-its-consequences-for-debugging/
       */
      function loadScript(path) {
         var result = $.Deferred(),
             script = document.createElement("script");
         script.async = "async";
         script.type = "text/javascript";
         script.src = path;
         script.onload = script.onreadystatechange = function(_, isAbort) {
             if (!script.readyState || /loaded|complete/.test(script.readyState)) {
                 if (isAbort)
                     result.reject();
                 else
                     result.resolve();
             }
         };
         script.onerror = function () { result.reject(); };
         $("head")[0].appendChild(script);
         return result.promise();
      }  
      
      /**
       * Strange bug when editing a comment, then returning 
       * to main editor at bottom of discussion. For now, 
       * just use this temp hack. I noticed that if there 
       * was text in the main editor before choosing to edit 
       * a comment further up the discussion, that the main 
       * one would be fine, so insert a zero-width character 
       * that will virtually disappear to everyone and 
       * everything--except wysihtml5. 
       * Actual console error: 
       * NS_ERROR_INVALID_POINTER: Component returned failure code: 0x80004003 (NS_ERROR_INVALID_POINTER) [nsISelection.addRange]
       * this.nativeSelection.addRange(getNativeRange(range));
       * LINE: 2836 in wysihtml5-0.4.0pre.js
       * 
       * &zwnj;
       * wysihtml5.INVISIBLE_SPACE = \uFEFF
       */
      var nullFix = function(editorInstance) {
         var editor = editorInstance;
         var text = editor.composer.getValue();
         //editor.composer.setValue(text + "<p>&zwnj;<br></p>");
         
         // Problem with this is being able to post "empty", because invisible 
         // space is counted as a character. However, many forums could 
         // implemented a character minimum (Kixeye does), so this will 
         // not happen everywhere. Regardless, this is only a bandaid. A real 
         // fix will need to be figured out. The wysihtml5 source was pointing 
         // to a few things, but it largely also utilizes hacks like this, and 
         // in fact does insert an initial p tag in the editor to signal that 
         // paragraphs should follow. 
         var insertNull = function() {
            editor.composer.commands.exec("insertHTML", "<p>"+wysihtml5.INVISIBLE_SPACE+"</p>");
            editor.fire("blur", "composer");
            editor.focus(); 
         };

         editor.on("focus", function() {
            if (!editor.composer.getValue().length) {
               insertNull();
            }
         });

         $(editor.composer.doc).on('keyup', function(e){
            // Backspace
            if (e.which == 8) {
               if (!editor.composer.getValue().length) {
                  insertNull();
               }
            }
         });
      };

      /**
       * This will only be called when debug=true;
       */
      var wysiDebug = function(editorInstance) {
         // Event examples that will come in handy--taken from source. 
         //editor.fire("change_view", "composer");
         //editor.fire("change_view", "textarea");
         //this.editor.observe("change_view", function(view) {
         //this.editor.observe("destroy:composer", stopInterval);
         //editor.setValue('This will do it.');
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
         })
         .on("destroy:composer", function() {
           console.log('destroy:composer');
         }); 
      };

      /**
       * Initialize editor on every .BodyBox (or other element passed to this 
       * jQuery plugin) on the page.
       * 
       * Was originallt built for latest mutation observers, but far too 
       * limited in support and VERY temperamental, so had to move to livequery. 
       * The params checks are there in case in future want to use mutation 
       * observers again. For livequery functionality, just pass empty string as 
       * first param, and the textarea object to the second. 
       */
      var editorInit = function(obj, textareaObj) { 
         var t = $(obj);

         // if using mutation events, use this, and send mutation
         if (typeof obj.target != 'undefined') {
            t = $(obj.target);
         }

         // if using livequery, use this, and send blank string
         if (obj == '') {
            t = $(textareaObj).closest('form');
         }

         var currentEditorFormat     = t.find('#Form_Format');
         var currentEditorToolbar    = '';
         var currentEditableTextarea = '';
         var currentTextBoxWrapper   = '';

         // When previewing text in standard reply discussion controller, 
         // the mutation will cause the iframe to be inserted AGAIN, thus 
         // having multiple iframes with identical properties, which, upon 
         // reverting back to editing mode, will break everything, so kill
         // mutation callback immediately, so check if iframe already exists.
         if ($(t).find('iframe').hasClass('vanilla-editor-text')) {
            return false;
         }

         if (currentEditorFormat.length) {

             formatOriginal          = currentEditorFormat[0].value;
             currentEditorFormat     = currentEditorFormat[0].value.toLowerCase();
             format                  = currentEditorFormat + '';
             currentEditorToolbar    = t.find('.editor-format-'+ format);
             currentEditableTextarea = t.find('#Form_Body');
 
            if (textareaObj) {
                currentEditableTextarea = textareaObj;
             }

             currentTextBoxWrapper   = currentEditableTextarea.parent('.TextBoxWrapper');   
             
             // If singleInstance is false, then odds are the editor is being 
             // loaded inline and there are other instances on page.
             var singleInstance = true;
          
             // Determine if editing a comment, or not. When editing a comment, 
             // it has a comment id, while adding a new comment has an empty 
             // comment id. The value is a hidden input.
             var commentId = $(currentTextBoxWrapper).parent().find('#Form_CommentID').val();
             if (typeof commentId != 'undefined' && commentId != '') {
                singleInstance = false;
             }
         }

         // if found, perform operation
         if (currentEditorToolbar.length 
         && currentEditableTextarea.length) {

            var currentEditableCommentId = (new Date()).getTime(),
                editorTextareaId         = currentEditableTextarea[0].id +'-'+ currentEditableCommentId,
                editorToolbarId          = 'editor-format-'+ format +'-'+ currentEditableCommentId;

            var editorName               = 'vanilla-editor-text-'+ currentEditableCommentId;

            // change ids to bind new editor functionality to particular edit
            $(currentEditorToolbar).attr('id', editorToolbarId);

            $(currentEditableTextarea).attr('id', editorTextareaId); 

            switch (format) {
               case 'wysiwyg':
               case 'ipb':
               case 'bbhtml':
               case 'bbwysiwyg':

                   // Lazyloading scripts, then run single callback
                   $.when(
                      loadScript(assets + '/js/wysihtml5-0.4.0pre.js'),
                      loadScript(assets + '/js/advanced.js'),
                      loadScript(assets + '/js/jquery.wysihtml5_size_matters.js')
                   ).done(function(){

                      var editorRules = {
                         // Give the editor a name, the name will also be set as class name on the iframe and on the iframe's body 
                         name:                 editorName,
                         // Whether the editor should look like the textarea (by adopting styles)
                         style:                true,
                         // Id of the toolbar element or DOM node, pass false value if you don't want any toolbar logic
                         toolbar:              editorToolbarId,
                         // Whether urls, entered by the user should automatically become clickable-links
                         autoLink:             false,
                         // Object which includes parser rules to apply when html gets inserted via copy & paste
                         // See parser_rules/*.js for examples
                         parserRules:          wysihtml5ParserRules,
                         // Parser method to use when the user inserts content via copy & paste
                         parser:               wysihtml5.dom.parse,
                         // Class name which should be set on the contentEditable element in the created sandbox iframe, can be styled via the 'stylesheets' option
                         composerClassName:    "editor-composer",
                         // Class name to add to the body when the wysihtml5 editor is supported
                         bodyClassName:        "js-editor-active",
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

                      // instantiate new editor
                      var editor = new wysihtml5.Editor($(currentEditableTextarea)[0], editorRules);

                      editor.on('load', function() {
                         // enable auto-resize
                         $(editor.composer.iframe).wysihtml5_size_matters();  
                         editorHandleQuotesPlugin(editor);
                         
                         // Clear textarea/iframe content on submit. 
                         // This is not actually necessary here because 
                         // the whole editor is removed from the page on post.
                         $(currentEditableTextarea.closest('form')).on('clearCommentForm', function() {
                            editor.fire('clear');
                            editor.composer.clear();
                            this.reset();           
                            $(currentEditableTextarea).val('');
                            //$('iframe').contents().find('body').empty();
                            $(editor.composer.iframe).css({"min-height": "inherit"});
                         });
                         
                         // Fix problem of editor losing its default p tag 
                         // when loading another instance on the same page. 
                         nullFix(editor);
                        
                         //wysiPasteFix(editor);
                         fullPageInit(editor);
                         editorSetupDropdowns(editor);
                         
                         // If editor is being loaded inline, then focus it.
                         if (!singleInstance) {
                           scrollToEditorContainer(editor.textarea.element);
                           editor.focus();
                         }
                         
                         if (debug) {
                            wysiDebug(editor);
                         }
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
                            if ($(composer.element.lastChild).hasClass('Spoiler')) {
                               composer.selection.setAfter(composer.element.lastChild);
                               composer.commands.exec("insertHTML", "<p></p>");
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
                               composer.commands.exec("insertHTML", "<p></p>");
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
                              composer.commands.exec("insertHTML", "<p></p>");
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

                case 'html':
                case 'bbcode':
                case 'markdown': 
                   // Lazyloading scripts, then run single callback
                   $.when(
                      loadScript(assets + '/js/buttonbarplus.js'),
                      loadScript(assets + '/js/jquery.hotkeys.js'),
                      loadScript(assets + '/js/rangy.js')
                   ).done(function() {
                      ButtonBar.AttachTo($(currentEditableTextarea)[0], formatOriginal);
                      fullPageInit();
                      editorSetupDropdowns();
                      if (!singleInstance) {
                         scrollToEditorContainer($(currentEditableTextarea)[0]);
                         editorSetCaretFocusEnd(currentEditableTextarea[0]);
                      }
                   });                  
                   break;
            }

            // Set up on editor load
            editorSetHelpText(formatOriginal, currentTextBoxWrapper);

            // some() loop requires true to end loop. every() requires false.
            return true;
         }
      }

      // Deprecated livequery.
      if(jQuery().livequery) {
         this.livequery(function() {    
            editorInit('', $(this));
         });
      }

      // jQuery chaining
      return this;
   };
}(jQuery));


// Set all .BodyBox elements as editor, calling plugin above.
jQuery(document).ready(function($) {
   $('.BodyBox').setAsEditor();
});