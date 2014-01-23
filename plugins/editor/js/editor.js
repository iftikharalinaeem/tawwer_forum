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
       *
       * TODO: add fullpage class to iframe bodybox if in wysiwyg, for easier
       * style overwriting.
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

            var bodyEl = $('body');

            // New wrapper classes added to form. If the first is not availble,
            // fall back to second one. This was added to core, so core will
            // need to be updated alongside the plugin if site owners want
            // latest feature.
            var formWrapper = ($(toggleButton).closest('.fullpage-wrap').length)
               ? $(toggleButton).closest('.fullpage-wrap')
               : $(toggleButton).closest('.bodybox-wrap');

            // Not all parts of the site have same surrounding markup, so if that
            // fails, grab nearest parent element that might enclose it. The
            // exception this was made for is the signatures plugin.
            if (typeof formWrapper == 'undefined') {
               formWrapper = $(toggleButton).parent().parent();
            }

            var fullPageCandidate = $('#editor-fullpage-candidate');

            // If no fullpage, enable it
            if (!bodyEl.hasClass('js-editor-fullpage')) {
               $(formWrapper).attr('id', 'editor-fullpage-candidate');
               bodyEl.addClass('js-editor-fullpage');
               $(toggleButton).addClass('icon-resize-small');

               var editorToolbar = $(fullPageCandidate).find('.editor');

               // When textarea pushes beyond viewport of its container, a
               // scrollbar appears, which pushes the textarea left, while the
               // fixed editor toolbar does not move, so push it over.
               // Opted to go this route because support for the flow events is
               // limited, webkit/moz both have their own implementations, while
               // IE has no support for them. See below for example, commented out.

               // Only Firefox seems to have this issue (unless this is
               // mac-specific. Chrome & Safari on mac do not shift content over.
               /*
               if (typeof InstallTrigger !== 'undefined') {
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
               */
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

               // If in wysiwyg fullpage mode with lights toggled off, then
               // exit, the lights off remains.
               // TODO move into own function
               var ifr = $(fullPageCandidate).find('.wysihtml5-sandbox');
               if (ifr.length) {
                  var iframeBodyBox = ifr.contents().find('.BodyBox');
                  //$(iframeBodyBox).addClass('iframe-bodybox-lightsoff');
                  iframeBodyBox.off('focus blur');
                  $(fullPageCandidate).removeClass('editor-lights-candidate');
                  $(iframeBodyBox).removeClass('iframe-bodybox-lightsoff');
                  $(iframeBodyBox).removeClass('iframe-bodybox-lightson');
               }

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

            // Toggle lights while in fullpage (lights off essentially makes
            // the background black and the buttons lighter.
            toggleLights();
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
          * Lights on/off in fullpage
          *
          * Note: Wysiwyg makes styling the BodyBox more difficult as it's an
          * iframe. Consequently, JavaScript has to override all the styles
          * that skip the iframe, and tie into the focus and blur events to
          * override the Wysihtml5 inline style events.
          */
         var toggleLights = function() {
            var toggleLights = $('.editor-toggle-lights-button');
            var fullPageCandidate = $('#editor-fullpage-candidate');
            var ifr = {};

            if (fullPageCandidate.length) {
               $(toggleLights).attr('style', 'display:inline-block !important');

               // Due to wysiwyg styles embedded inline and states changed
               // using JavaScript, all the styles have to be duplicated from
               // the external stylesheet and override the iframe inlines.
               ifr = $(fullPageCandidate).find('.wysihtml5-sandbox');
               if (ifr.length) {
                  var iframeBodyBox = ifr.contents().find('.BodyBox');
                  iframeBodyBox.css({
                     "transition": "background-color 0.4s ease, color 0.4s ease"
                  });

                  // By default, black text on white background. Some themes
                  // prevent text from being readable, so make sure it can.
                  iframeBodyBox.addClass('iframe-bodybox-lightson');
               }
            } else {
               $(toggleLights).attr('style', '');
            }

            $(toggleLights).off('click').on('click', function() {
               if (!$(fullPageCandidate).hasClass('editor-lights-candidate')) {
                  $(fullPageCandidate).addClass('editor-lights-candidate');

                  // Again, for Wysiwyg, override styles
                  if (ifr.length) {
                     // if wysiwyg, need to manipulate content in iframe
                     iframeBodyBox.removeClass('iframe-bodybox-lightson');
                     iframeBodyBox.addClass('iframe-bodybox-lightsoff');

                     iframeBodyBox.on('focus blur', function(e) {
                        $(this).addClass('iframe-bodybox-lightsoff');
                     });
                  }
               } else {
                  $(fullPageCandidate).removeClass('editor-lights-candidate');

                  // Wysiwyg override styles
                  if (ifr.length) {
                     iframeBodyBox.off('focus blur');

                     // if wysiwyg, need to manipulate content in iframe
                     iframeBodyBox.removeClass('iframe-bodybox-lightsoff');
                     iframeBodyBox.addClass('iframe-bodybox-lightson');
                  }
               }
            });
         };
      };

      /**
       * When rendering editor, load correct helpt text message
       */
      var editorSetHelpText = function(format, editorAreaObj) {
         format = format.toLowerCase();
         if (format != 'wysiwyg' && format != 'text' && format != 'textex') {
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
          // Check if can access selection, as programmatically triggering the
          // dd close event throws an error here.
          if (obj.selectionEnd) {
            // selectionStart is implied 0
            obj.selectionEnd = obj.value.length;
            obj.focus();
          }
       };

      /**
       * Deal with clashing JS for opening dialogs on click, and do not let
       * more than one dialog/dropdown appear at once.
       */
      var editorSetupDropdowns = function(editorInstance) {
         $('.editor-dropdown .editor-action')
         .off('click.dd')
         .on('click.dd', function(e) {
            var parentEl = $(e.target).parent();

            // Again, tackling with clash from multiple codebases.
            $('.editor-insert-dialog').each(function(i, el) {
               setTimeout(function() {
                  $(el).removeAttr('style');
               }, 0);
            });

            if (parentEl.hasClass('editor-dropdown')
            && parentEl.hasClass('editor-dropdown-open')) {
               parentEl.removeClass('editor-dropdown-open');
               //$(parentEl).find('.wysihtml5-command-dialog-opened').removeClass('wysihtml5-command-dialog-opened');
            } else {
               // clear other opened dropdowns before opening this one
               $(parentEl).parent('.editor').find('.editor-dropdown-open').each(function(i, el) {
                  $(el).removeClass('editor-dropdown-open');
                  $(el).find('.wysihtml5-command-dialog-opened').removeClass('wysihtml5-command-dialog-opened');
               });

               // If the editor action buttons have been disabled (by switching
               // to HTML code view, then do not allow dropdowns. CSS pointer-
               // events should have taken care of this, but JS still fires the
               // event regardless, so disable them here as well.
               if (!parentEl.hasClass('wysihtml5-commands-disabled')) {
                  parentEl.addClass('editor-dropdown-open');

                  // if has input, focus and move caret to end of text
                  var inputBox = parentEl.find('.InputBox');
                  if (inputBox.length) {
                     editorSelectAllInput(inputBox[0]);
                  }
               }
            }
         });

         // Handle Enter key
         $('.editor-dropdown').find('.InputBox').on('keydown', function(e) {
            if (e.which == 13) {
               // Cancel enter key submissions on these values.
               if (this.value == ''
               || this.value == 'http://'
               || this.value == 'https://') {
                  e.stopPropagation();
                  e.preventDefault();
                  return false;
               }

               // Make exception for non-wysiwyg, as wysihtml5 has custom
               // key handler.
               if (!$(this).closest('.editor').hasClass('editor-format-wysiwyg')) {
                  // Fire event programmatically to do what needs to be done in
                  // ButtonBar code.
                  $(this).parent().find('.Button').trigger('click.insertData');

                  e.stopPropagation();
                  e.preventDefault();
                  return false;
               }
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

         /*
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
               if ($(editor.textarea.element).val() !== initialText) {
                  clearInterval(si);
                  $(editor.textarea.element).css({"opacity":""});
                  editor.fire("change_view", "composer");
                  // Inserting a quote at the end prevents editor from
                  // breaking out of quotation, which means everything
                  // typed after the inserted quotation, will be wrapped
                  // in a blockquote.
                  editor.composer.selection.setAfter(editor.composer.element.lastChild);
                  editor.composer.commands.exec("insertHTML", "<p></p>");

                  // editor.composer.setValue(editor.composer.getValue() + "<p><br></p>");
                  // editor.fire("focus:composer");
               }
            }, 0);
         });
         */

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
            // The quotes plugin tends to add line breaks to the end of the
            // quoted string, which upsets wysihtml5 paragraphing, so replace
            // it with proper block ending to make sure paragraphs continue.
            data = data.replace(/<br\s?\/?>$/, '<p><br></p>');

            // Read nullFix function for full explanation. Essentially,
            // placeholder does not get removed, so remove it manually if
            // one is set.
            if (editor.composer.placeholderSet) {
               // Just clear it on Firefox, then insert null fix.
               editor.composer.setValue('');
            }

            editor.composer.commands.exec("insertHTML", data);

            // Reported bug: Chrome does not handle wysihtml5's insertHTML
            // command properly. The downside to this workaround is that the
            // caret will be placed at the beginning of the text box.
            if (window.chrome) {
               var initial_value = editor.composer.getValue();

               if (!initial_value.length
               || initial_value.toString() === '<p></p>') {
                  editor.composer.setValue(initial_value + data);
               } else {
                  editor.composer.setValue(initial_value);
               }
            }

            editor.focus();
         });

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
            // Originally found this bug through a client site mentioning paste
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
         //var text = editor.composer.getValue();
         //editor.composer.setValue(text + "<p>&zwnj;<br></p>");

         // Problem with this is being able to post "empty", because invisible
         // space is counted as a character. However, many forums could
         // implemented a character minimum, so this will
         // not happen everywhere. Regardless, this is only a bandaid. A real
         // fix will need to be figured out. The wysihtml5 source was pointing
         // to a few things, but it largely also utilizes hacks like this, and
         // in fact does insert an initial p tag in the editor to signal that
         // paragraphs should follow.
         var insertNull = function() {
            if (!window.chrome) {
               // When placeholder attribute set, Firefox does not clear it, and this
               // is due to this nullfix. Chrome handles it okay, though this
               // whole function (nullFix) is just a mess of flimflam.
               // Paragraphing in Wysihtml5 added many exceptions.
               if (editor.composer.placeholderSet) {
                  // Just clear it on Firefox, then insert null fix.
                  editor.composer.setValue('');
               }

               editor.composer.commands.exec("insertHTML", "<p>"+wysihtml5.INVISIBLE_SPACE+"</p>");
            } else {
               editor.composer.setValue(editor.composer.getValue() + "<p>"+wysihtml5.INVISIBLE_SPACE+"</p>");
            }
            editor.fire("blur", "composer");
            editor.focus();
         };

         editor.on("focus", function() {
            if (!editor.composer.getValue().length) {
               insertNull();
            }
         });

         // On Chrome, when loading page, first editing a post, then going to
         // reply, ctrl+a all body of new reply, delete, then start typing,
         // and paragraphing is gone. This is because I'm only checking and
         // inserting null on backspace when empty. I could just interval
         // check for emptiness, but this nullFix is already too hackish.
         // OKAY no. Return to this problem in future.
         $(editor.composer.doc).on('keyup', function(e) {
            // Backspace
            if (e.which == 8) {
               if (!editor.composer.getValue().length) {
                  insertNull();
               }
            }
         });
      };


      /**
       * Using at.js library.
       *
       * This allows @mentions and :emoji: autocomplete, as well as any other
       * character key autocompletion.
       */
      var atCompleteInit = function(editorElement, iframe) {

         // Cache non-empty server requests
         var cache = {};

         // Cache empty server requests to prevent similarly-started requests
         // from being sent.
         var empty = {};

         // Set minimum characters to type for @mentions to fire
         var min_characters = 2;

         // Max suggestions to show in dropdown.
         var max_suggestions = 5;

         // Server response limit. This should match the limit set in
         // *UserController->TagSearch* and UserModel->TagSearch
         var server_limit = 30;

         // Emoji
         var emoji = $.parseJSON(gdn.definition('emoji', []));

         // Handle iframe situation
         var iframe_window = (iframe)
            ? iframe.contentWindow
            : '';

         $(editorElement)
            .atwho({
               at: '@',
               tpl: '<li data-value="@${name}" data-id="${id}">${name}</li>',
               limit: max_suggestions,
               callbacks: {

                  // Custom data source.
                  remote_filter: function(query, callback) {
                     // Do this because of undefined when adding spaces to
                     // matcher callback, as it will be monitoring changes.
                     var query = query || '';

                     // Only all query strings greater than min_characters
                     if (query.length >= min_characters) {

                        // If the cache array contains less than LIMIT 30
                        // (according to server logic), then there's no
                        // point sending another request to server, as there
                        // won't be any more results, as this is the maximum.
                        var filter_more = true;

                        // Remove last character so that the string can be
                        // found in the cache, if exists, then check if its
                        // matching array has less than the server limit of
                        // matches, which means there are no more, so save the
                        // additional server request from being sent.
                        var filter_string = '';

                        // Loop through string and find first closest match in
                        // the cache, and if a match, check if more filtering
                        // is required.
                        for (var i = 0, l = query.length; i < l; i++) {
                           filter_string = query.slice(0, -i);

                           if (cache[filter_string]
                           && cache[filter_string].length < server_limit) {
                              //console.log('no more filtering for "'+ query + '" as its parent filter array, "'+ filter_string +'" is not maxed out.');

                              // Add this other query to empty array, so that it
                              // will not fire off another request.
                              empty[query] = query;

                              // Do not filter more, meaning, do not send
                              // another server request, as all the necessary
                              // data is already in memory.
                              filter_more = false;
                              break;
                           }
                        }

                        // Check if query would be empty, based on previously
                        // cached empty results. Compare against the start of
                        // the latest query string.
                        var empty_query = false;

                        // Loop through cache of empty query strings.
                        for (key in empty) {
                           if (empty.hasOwnProperty(key)) {
                              // See if cached empty results match the start
                              // of the latest query. If so, then no point
                              // sending new request, as it will return empty.
                              if (query.match(new RegExp('^'+ key +'+')) !== null) {
                                 empty_query = true;
                                 break;
                              }
                           }
                        }

                        // Produce the suggestions based on data either
                        // cached or retrieved.
                        if (filter_more && !empty_query  && !cache[query]) {
                           $.getJSON('/user/tagsearch', {"q": query, "limit": server_limit}, function(data) {
                              callback(data);

                              // If data is empty, cache the results to prevent
                              // other requests against similarly-started
                              // query strings.
                              if (data.length) {
                                 cache[query] = data;
                              } else {
                                 empty[query] = query;
                              }
                           });
                        } else {
                           // If no point filtering more as the parent filter
                           // has not been maxed out with responses, use the
                           // closest parent filter instead of the latest
                           // query string.
                           if (!filter_more) {
                              callback(cache[filter_string]);
                           } else {
                              callback(cache[query]);
                           }
                        }
                     }
                  },

                  // Note, in contenteditable mode (iframe for us), the value
                  // is surrounded by span tags.
                  before_insert: function(value, $li) {

                     // It's better to use the value provided, as it may have
                     // html tags around it, depending on mode. Using the
                     // regular expression avoids the need to check what mode
                     // the suggestion is made in, and then constructing
                     // it based on that. Optional assignment for undefined
                     // matcher callback results.
                     var username = $li.data('value') || '';
                     // Pop off the flag--usually @ or :
                     username = username.slice(1, username.length);

                     // Check if there are any whitespaces, and if so, add
                     // quotation marks around the whole name.
                     var requires_quotation = (/\s/g.test(username))
                        ? true
                        : false;

                     // Check if there are already quotation marks around
                     // the string--double or single.
                     var has_quotation = (/(\"|\')(.+)(\"|\')/g.test(username))
                        ? true
                        : false;

                     var insert = username;

                     if (requires_quotation && !has_quotation) {
                        // Do not even need to have value wrapped in
                        // any tags at all. It will be done automatically.
                        //insert = value.replace(/(.*\>?)@([\w\d\s\-\+\_]+)(\<?.*)/, '$1@"$2"$3');
                        insert = '"' + username + '"';
                     }

                     // This is needed for checking quotation mark directly
                     // after at character, and preventing another at character
                     // from being inserted into the page.
                     var raw_at_match = this.raw_at_match || '';

                     var at_quote = (/.?@(\"|\')/.test(raw_at_match))
                        ? true
                        : false;

                     // If at_quote is false, then insert the at character,
                     // otherwise it means the user typed a quotation mark
                     // directly after the at character, which, would get
                     // inserted again if not checked. at_quote would
                     // be false most of the time; the exception is when
                     // it's true.
                     if (!at_quote) {
                        insert = this.at + insert;
                     }

                     // Keep for reference, but also, spaces add complexity,
                     // so use zero-width non-joiner delimiting those advanced
                     // username mentions.
                     var hidden_unicode_chars = {
                        zws:  '\u200b',
                        zwnj: '\u200C',
                        nbsp: '\u00A0' // \xA0
                     };

                     // The last character prevents the matcher from trigger
                     // on nearly everything.
                     return insert + hidden_unicode_chars.zwnj;
                  },

                  // Custom highlighting to accept spaces in names. This is
                  // almost a copy of the default in the library, with tweaks
                  // in the regex.
                  highlighter: function(li, query) {
                     var regexp;
                     if (!query) {
                        return li;
                     }
                     regexp = new RegExp(">\\s*(\\w*)(" + query.replace("+", "\\+") + ")(\\w*)\\s*(\\s+.+)?<", 'ig');
                     // Capture group 4 for possible spaces
                     return li.replace(regexp, function(str, $1, $2, $3, $4) {
                        return '> ' + $1 + '<strong>' + $2 + '</strong>' + $3 + $4 + ' <';
                     });
                  },

                  // Custom matching to allow quotation marks in the matching
                  // string as well as spaces. Spaces make things more
                  // complicated.
                  matcher: function(flag, subtext, should_start_with_space) {
                     var match, regexp;
                     flag = flag.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");

                     if (should_start_with_space) {
                        flag = '(?:^|\\s)' + flag;
                     }

                     // Note: adding whitespace to regex makes the query in
                     // remote_filter and before_insert callbacks throw
                     // undefined when not accounted for, so optional
                     // assigments added to each.
                     //regexp = new RegExp(flag + '([A-Za-z0-9_\+\-]*)$|' + flag + '([^\\x00-\\xff]*)$', 'gi');
                     // Note: this does make the searching a bit more loose,
                     // but it's the only way, as spaces make searching
                     // more ambiguous.
                     // \xA0 non-breaking space
                     regexp = new RegExp(flag + '\"?([\\sA-Za-z0-9_\+\-]*)\"?$|' + flag + '\"?([^\\x00-\\xff]*)\"?$', 'gi');

                     match = regexp.exec(subtext);
                     if (match) {
                        // Store the original matching string to check against
                        // quotation marks after the at symbol, to prevent
                        // double insertions of the at symbol. This will be
                        // used in the before_insert callback.
                        this.raw_at_match = match[0];

                        return match[2] || match[1];
                     } else {
                        return null;
                     }
                  }
               },
               display_timeout: 0,
               cWindow: iframe_window
            })
            .atwho({
               at: ':',
               tpl: '<li data-value=":${name}:" class="at-suggest-emoji"><img src="${url}" width="20" height="20" alt=":${name}:" class="emoji-img" /> <span class="emoji-name">${name}</span></li>',
               limit: max_suggestions,
               data: emoji,
               cWindow: iframe_window
            });

         // http://stackoverflow.com/questions/118241/calculate-text-width-with-javascript
         String.prototype.width = function(font) {
            var f = font || "15px 'lucida grande','Lucida Sans Unicode',tahoma,sans-serif'";
            o = $('<div>' + this + '</div>')
               .css({'position': 'absolute', 'float': 'left', 'white-space': 'nowrap', 'visibility': 'hidden', 'font': f})
               .appendTo($('body')),
            w = o.width();
            o.remove();
            return w;
         }

         // Only necessary for iframe.
         // Based on work here: https://github.com/ichord/At.js/issues/124
         if (iframe_window) {
            // This hook is triggered when atWho places a selection list in the
            // window. The context is passed implicitly when triggered by at.js.
            $(iframe_window).on("reposition.atwho", function(e, offset, context) {

               // Actual suggestion box that will appear.
               var suggest_el = context.view.$el;

               // The area where text will be typed (contenteditable body).
               var $inputor = context.$inputor;

               // Display it below the text.
               var line_height = parseInt($inputor.css('line-height'));

               // offset contains the top left values of the offset to the iframe
               // we need to convert that to main window coordinates
               var oIframe = $(iframe).offset(),
                  iLeft = oIframe.left + offset.left,
                  iTop = oIframe.top,
                  select_height = 0;

               // In wysiwyg mode, the suggestbox follows the typing, which
               // does not happen in regular mode, so adjust it.
               // Either @ or : for now.
               var at = context.at;
               var text = context.query.text;
               var font_mirror = $('.BodyBox');
               var font = font_mirror.css('font-size') + ' ' + font_mirror.css('font-family');

               // Get font width
               var font_width = (at+text).width(font) - 2;

               if (at == '@') {
                  iLeft -= font_width;
               }

               if (at == ':') {
                  iLeft -= 2;
               }

               // atWho adds 3 select areas, presumably for differnet positing on screen (above below etc)
               // This finds the active one and gets the container height
               $(suggest_el).each(function(i, el) {
                  if ($(this).outerHeight() > 0) {
                     select_height += $(this).height() + line_height;
                  }
               });

               // Now should we show the selection box above or below?
               var iWindowHeight = $(window).height(),
                  iDocViewTop = $(window).scrollTop(),
                  iSelectionPosition = iTop + offset.top - $(window).scrollTop(),
                  iAvailableSpace = iWindowHeight - (iSelectionPosition - iDocViewTop);

               if (iAvailableSpace >= select_height) {
                  // Enough space below
                  iTop = iTop + offset.top + select_height - $(window).scrollTop();
               }
               else {
                  // Place it above instead
                  // @todo should check if this is more space than below
                  iTop= iTop + offset.top - $(window).scrollTop();
               }

               // Move the select box
               offset = {left: iLeft, top: iTop};
               $(suggest_el).offset(offset);
            });
         }
      };

      // Note, this depends heavily on blueimp's file upload, and just a bit
      // on Tim Down's rangyinputs (originally loaded for buttonbarplus).
      var fileUploadsInit = function(dropElement, editorInstance) {

         // Disable default browser behaviour of file drops
         $(document).on('drop dragover', function(e) {
            e.preventDefault();
         });

         // Multi upload element. If an iframe, it will need to be checked
         // in the loop far below. Redundant, but let's see.
         var dropZone = dropElement;

         // Pass editor instance to uploader, to access methods
         var editor = (editorInstance)
            ? editorInstance
            : dropElement;

         var maxUploadSize = gdn.definition('maxUploadSize');
         var editorFileInputName = gdn.definition('editorFileInputName');
         var allowedFileExtensions = gdn.definition('allowedFileExtensions');
         var maxFileUploads = gdn.definition('maxFileUploads');

         // Add CSS class to this element to style children on dragover
         var $dndCueWrapper = $(dropElement).closest('.bodybox-wrap');

         // Determine if element passed is an iframe or local element.
         var handleIframe = false;
         var iframeElement = '';
         if ($(dropElement)[0].contentWindow) {
            iframeElement = dropElement;
            dropElement = $(dropElement)[0].contentWindow;
            handleIframe = true;
         }

         // Insert container for displaying all uploads. All successful
         // uploads will be inserted here.
         $dndCueWrapper.find('.TextBoxWrapper').after('<div class="editor-upload-previews"></div>');
         $editorUploadPreviews = $dndCueWrapper.find('.editor-upload-previews');

         // Handle drop effect as UX cue
         $(dropElement).on('dragenter dragover', function(e) {
            $dndCueWrapper.addClass('editor-drop-cue');
         }).on('drop dragend dragleave', function(e) {
            $dndCueWrapper.removeClass('editor-drop-cue');
         });


         // Get current comment or discussion post box

         // This is the key that finds .editor-upload-saved
         var editorKey = 'editor-uploads-';
         var editorForm = $dndCueWrapper.closest('form');

         var savedUploadsContainer = '';
         var mainCommentForm = '';
         if (editorForm) {
            var formCommentId = $(editorForm).find('#Form_CommentID');
            var formDiscussionId = $(editorForm).find('#Form_DiscussionID');

            // Determine if bodybox loaded is the main comment one. This one
            // will never have any saved uploads, so make sure it never grabs
            // saved ones when switching.
            var mainCommentBox = false;
            if (formCommentId.length
            && formCommentId[0].value == ''
            && formDiscussionId.length
            && parseInt(formDiscussionId[0].value) > 0) {
               mainCommentBox = true;
               mainCommentForm = editorForm;
               mainCommentPreviews = $editorUploadPreviews;
            }

            // Build editorKey
            if (formCommentId.length
            && parseInt(formCommentId[0].value) > 0) {
               editorKey += 'commentid' + formCommentId[0].value;
            } else if (formDiscussionId.length
            && parseInt(formDiscussionId[0].value) > 0) {
               editorKey += 'discussionid' + formDiscussionId[0].value;
            }

            // Make saved files editable
            if (!mainCommentBox && editorKey != 'editor-uploads-') {
               var savedContainer = $('#' + editorKey);
               if (savedContainer.length && savedContainer.html().trim() != '') {
                  savedUploadsContainer = savedContainer;
               }
            }
         }

         /**
          * Help methods
          */
         var buildImgTag = function(href) {
            return '<img src="'+ href +'" alt="" />';
         };

         // Used in two places below
         var insertImageIntoBody = function(filePreviewContainer) {
            $(filePreviewContainer).removeClass('editor-file-removed');
            var file = $(filePreviewContainer).find('a.filename');
            var type = $(file).data('type');
            var href = file.attr('href');

            if (type.indexOf('image') > -1) {
               if (handleIframe) {
                  var iframeBody = $(iframeElement).contents().find('body');
                  var imgTag = buildImgTag(href);
                  editor.focus();
                  editor.composer.commands.exec('insertHTML', '<p>' + imgTag + '</p>');
                  var insertedImage = $(iframeBody).find('img[src="'+href+'"]');
                  var newHeight = parseInt($(iframeElement).css('min-height')) + insertedImage.height() + 'px';
                  $(iframeElement).css('min-height', newHeight);
               } else {
                  try {
                     $(editor).replaceSelectedText(buildImgTag(href) + '\n');
                  } catch(ex) {}
               }
            }
         };

         // Used in two places below.
         var removeImageFromBody = function(filePreviewContainer) {
            $(filePreviewContainer).addClass('editor-file-removed');
            var file = $(filePreviewContainer).find('a.filename');
            var type = $(file).data('type');
            var href = file.attr('href');

            // If images, remove insert from body as well
            if (type.indexOf('image') > -1) {
               if (handleIframe) {
                  var iframeBody = $(iframeElement).contents().find('body');
                  var insertedImage = $(iframeBody).find('img[src="'+href+'"]');
                  var newHeight = parseInt($(iframeElement).css('min-height')) - insertedImage.height() + 'px';
                  $(iframeElement).css('min-height', newHeight);
                  $(insertedImage).remove();
               } else {
                  var text = $(editor).val();
                  // A shame that JavaScript does not have this built-in.
                  // https://developer.mozilla.org/en/docs/Web/JavaScript/Guide/Regular_Expressions
                  //var imgTagEscaped = buildImgTag(href).replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
                  // Make it more loose, so it doesn't matter what the user
                  // may have done to the markup, it will be removed.
                  var imgTagEscaped = '<img(\s+|.*)src\="'+ href.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1") +'"(\s+|.*)\/?>\n?';
                  var reg = new RegExp(imgTagEscaped, 'gi');
                  $(editor).val(text.replace(reg, ''));
               }
            }
         };

         /**
          * Remove and reattach files in a live upload session
          */
         $editorUploadPreviews
         .on('click.live-file-remove', '.editor-file-remove', function(e) {
            var $editorFilePreview = $(this).closest('.editor-file-preview');
            $editorFilePreview.find('input').attr('name','RemoveMediaIDs[]');

            // Remove element from editor body
            removeImageFromBody($editorFilePreview);
         })
         .on('click.live-file-reattach', '.editor-file-reattach', function(e) {
            var $editorFilePreview = $(this).closest('.editor-file-preview');
            $editorFilePreview.find('input').attr('name','MediaIDs[]');

            // Re-attach
            insertImageIntoBody($editorFilePreview);
         });

         /**
          * Remove and reattach files in a saved upload session, typically
          * through editing.
          */
         if (savedUploadsContainer) {
            // Turn read-only mode on. Event is fired from conversations.js
            // and discussion.js.
            $(editorForm).on('clearCommentForm', function(e) {
               $(savedUploadsContainer).addClass('editor-upload-readonly');
            });

            $(savedUploadsContainer)
            // Turn read-only mode off.
            .removeClass('editor-upload-readonly')
            // Remove saved file. This will add hidden input to form
            .on('click.saved-file-remove', '.editor-file-remove', function(e) {
               var $editorFilePreview = $(this).closest('.editor-file-preview');
               var mediaId = $editorFilePreview.find('input').val();

               // Add hidden input to form so it knows to remove files.
               $('<input>').attr({
                  type: 'hidden',
                  id: 'file-remove-' + mediaId,
                  name: 'RemoveMediaIDs[]',
                  value: mediaId
               }).appendTo($(editorForm));

               // Remove element from body.
               removeImageFromBody($editorFilePreview);
            })
            // This will remove the hidden input
            .on('click.saved-file-reattach', '.editor-file-reattach', function(e) {
               var $editorFilePreview = $(this).closest('.editor-file-preview');
               var mediaId = $editorFilePreview.find('input').val();

               // Remove hidden input from form
               $('#file-remove-' + mediaId).remove();

               // Re-attach
               insertImageIntoBody($editorFilePreview);
            });
         }

         // Clear session preview files--this for main comment box.
         if (mainCommentBox) {
            // When closing editor with new uploads in session, typically
            // commentbox at bottom of discussion, remove the uploads
            $(editorForm).on('clearCommentForm', function(e) {
               // Empty out the session previews.
               $(mainCommentPreviews).empty();
            });
         }

         // This will grab all the files--saved and session--currently
         // attached to a discussion. This is useful to
         // prevent the same files being uploaded multiple times to the
         // same discussion. It's better to constrain check to whole
         // discussion instead of on a per-comment basis, as it's not
         // useful to have the same file uploaded by multiple users in
         // a discussion as a whole.
         var getAllFileNamesInDiscussion = function() {
            var filesInDiscussion = $('.editor-file-preview');
            var fileNames = [];

            filesInDiscussion.each(function(i, el) {
               fileNames.push($(el).find('.filename').text().trim());
            });

            return fileNames;
         };

         // Initialize file uploads.
         //$('.bodybox-wrap').fileupload({

         $(dropZone).each(function(i, el) {
            var $init = $(this);
            var cssDropInitClass = 'editor-dropzone-init';

            if (!$(this).hasClass(cssDropInitClass)) {
               $(this).addClass(cssDropInitClass);

               if ($init[0].contentWindow) {
                  $init = $init[0].contentWindow;
               }
            } else {
               console.log('doubling up:');
               console.log($(this));
            }

            // Attach voodoo to dropzone.
            $(this).fileupload({

               // Options
               url: '/post/editorupload',
               paramName: editorFileInputName,
               dropZone: $init,
               forceIframeTransport: false,
               dataType: 'json',
               progressInterval: 25,
               autoUpload: true,

               // Fired on entire drop, and contains entire batch.
               drop: function (e, data) {
                  // Save original title for progress meter title percentages.
                  documentTitle = document.title;

                  // This is from PHP's max_file_uploads setting
                  if (data.files.length > maxFileUploads) {
                     var message = 'You cannot upload more than '+ maxFileUploads +' files at once.';
                     gdn.informMessage(message);
                     return false;
                  }
               },

               // Where to process and validate files.
               add: function(e, data) {
                  if (data.autoUpload
                  || (data.autoUpload !== false
                  && $(this).fileupload('option', 'autoUpload'))) {
                     data.process().done(function () {
                        // Single upload per request, files[] is always
                        // going to be an array of 1.
                        var file = data.files[0];
                        var type = file.type.split('/').pop();
                        var filename = file.name;
                        var extension = filename.split('.').pop();
                        var allowedExtensions = JSON.parse(allowedFileExtensions);

                        var validSize = (file.size <= maxUploadSize)
                           ? true
                           : false;

                        // Check against provided file type and file extension.
                        var validFile = ($.inArray(type, allowedExtensions) > -1 && $.inArray(extension, allowedExtensions) > -1)
                           ? true
                           : false;

                        // Check if the file is already a part of the
                        // discussion--that is, already uploaded to the
                        // current discussion.
                        var fileAlreadyExists = ($.inArray(filename, getAllFileNamesInDiscussion()) > -1)
                           ? true
                           : false;

                        if (validSize && validFile && !fileAlreadyExists) {
                           data.submit();
                        } else {
                           // File dropped is not allowed!
                           var message = '"'+ filename +'" ';

                           if (!validFile) {
                              message += 'is not allowed';
                           }

                           if (!validSize) {
                              if (!validFile) {
                                 message += ' and ';
                              }
                              message += 'is too large (max '+ maxUploadSize +' bytes)';
                           }

                           if (fileAlreadyExists) {
                              message += 'is already in this discussion';
                           }

                           gdn.informMessage(message +'.');
                        }
                     });
                  }
               },

               // Fired on every file.
               //send: function(e, data) {},

               // There is also `progress` per file upload.
               progressall: function (e, data) {
                  var progress = parseInt(data.loaded / data.total * 100, 10);
                  var $progressMeter = $(this).closest('form').find('.editor-upload-progress');
                  document.title = '('+ progress + '%) ' + documentTitle;
                  $progressMeter.css({
                     'width': progress + '%'
                  });
               },

               // Fired on successful upload
               done: function (e, data) {

                  var result = data.result;

                  if (!result.error) {
                     var payload = result.payload;

                     // If has thumbnail, display it instead of generic file icon.
                     var filePreviewCss = (payload.thumbnail_url)
                        ? '<i class="file-preview img" style="background-image: url('+ payload.thumbnail_url +')"></i>'
                        : '<i class="file-preview icon icon-file"></i>';

                     // If it's an image, then indicate that it's been embedded
                     // in the post
                     var imageEmbeddedText = (payload.thumbnail_url)
                        ? ' &middot; <em title="This image has been inserted into the body of text.">inserted</em>'
                        : '';

                     var html = ''
                     + '<div class="editor-file-preview" id="media-id-'+ payload.MediaID +'" title="'+ payload.Filename +'">'
                     + '<input type="hidden" name="MediaIDs[]" value="'+ payload.MediaID +'" />'
                     + filePreviewCss
                     + '<div class="file-data">'
                     + '<a class="filename" data-type="'+payload.type+'" data-width="'+payload.original_width+'" data-height="'+payload.original_height+'" href="'+ payload.original_url +'" target="_blank">'+ payload.Filename + '</a>'
                     + '<span class="meta">' + payload.FormatFilesize + imageEmbeddedText + '</span>'
                     + '</div>'
                     + '<span class="editor-file-remove" title="Remove file"></span>'
                     + '<span class="editor-file-reattach" title="Click to re-attach \''+ payload.Filename +'\'"></span>'
                     + '</div>';


                     // Editor upload previews is getting found above, and
                     // does not change per upload dropzone, which causes
                     // files to preview on the last found preview zone.
                     $editorUploadPreviews = $(this).closest('form').find('.editor-upload-previews');

                     // Add file blocksjust below editor area for easy removal
                     // and preview.
                     // Find it here.
                     $editorUploadPreviews.append(html);

                     // If photo, insert directly into editor area.
                     if (payload.type.toLowerCase().indexOf('image') > -1) {
                        // Determine max height for sample. They can resize it
                        // afterwards.
                        var maxHeight = (payload.original_height >= 400)
                           ? 400
                           : payload.original_height;

                        var imgTag = buildImgTag(payload.original_url);

                        if (handleIframe) {
                           editor.focus();
                           editor.composer.commands.exec('insertHTML', '<p>' + imgTag + '</p>');
                           var newHeight = parseInt($(iframeElement).css('min-height')) + payload.original_height + 'px';
                           $(iframeElement).css('min-height', newHeight);
                        } else {
                           try {
                              // i don't know why adding [0] to this when iframe
                              // matters, and clear up part of t problem.
                           $(editor).replaceSelectedText(imgTag + '\n');
                           } catch(ex){}
                        }
                     }
                  }
               },

               // Called after every file upload in a session
               // Typically this is fired due to 400 bad request, because
               // the file was probably not allowed, but passed client checks.
               // That would tend to mean the client checks need to be updated.
               fail: function(e, data) {
                  var filename = data.files[0].name;
                  var message = '"'+ filename +'" could not be uploaded.'
                  gdn.informMessage(message);
               },

               // Called regardless of success or failure.
               //always: function(e, data) {},

               // This is the last event that fires, and it's only fired once.
               // Note, sometimes the upload progress meter never reaches the
               // end, so this would be a good place to clear it as a backup.
               stop: function(e) {
                  var $progressMeter = $(this).closest('form').find('.editor-upload-progress');

                  // If progress meter didn't reach the end, force it.
                  $progressMeter.css({
                     'width': '100%'
                  });

                  $progressMeter.addClass('fade-out');

                  // Transition inserted above is 400ms, so remove it shortly
                  // after.
                  setTimeout(function() {
                     // Restore original document title
                     document.title = documentTitle;
                     // Remove transition class
                     $progressMeter.removeClass('fade-out');
                     // Reset width
                     $progressMeter.css({
                        'width': 0
                     });
                  }, 710);
               }
            });
         });
      };

      /**
       * Mobile devices don't play too well with contenteditable within an
       * iFrame, particularly iOS.
       */
      var iOSwysiFix = function(editor) {

         // iOS keyboard does not push content up initially,
         // thus blocking the actual content. Typing (spaces, newlines) also
         // jump the page up, so keep it in view.
         if (window.parent.location != window.location
         && (/ipad|iphone|ipod/i).test(navigator.userAgent)) {

            var contentEditable = $(editor.composer.iframe).contents().find('body');
            contentEditable.attr('autocorrect', 'off');
            contentEditable.attr('autocapitalize', 'off');

            var iOSscrollFrame = $(window.parent.document).find('#vanilla-iframe').contents();
            var iOSscrollTo = $(iOSscrollFrame).find('#'+editor.config.toolbar).closest('form').find('.Buttons');

            contentEditable.on('keydown keyup', function(e) {
               Vanilla.scrollTo(iOSscrollTo);
               editor.focus();
            });

            editor.on('focus', function() {
               //var postButton = $('#'+editor.config.toolbar).parents('form').find('.CommentButton');
               setTimeout(function() {
                 Vanilla.scrollTo(iOSscrollTo);
               }, 1);
            });
         }
      }

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
         })
         .on("set_placeholder", function() {
            console.log('set_placeholder');
         })
         .on("unset_placeholder", function(e) {
            console.log('unset_placeholder');
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
         var $t = $(obj);

         // if using mutation events, use this, and send mutation
         if (typeof obj.target != 'undefined') {
            $t = $(obj.target);
         }

         // if using livequery, use this, and send blank string
         if (obj == '') {
            $t = $(textareaObj).closest('form');
         }

         //var currentEditorFormat     = t.find('#Form_Format');
         var currentEditorFormat     = $t.find('input[name="Format"]');
         var $currentEditorToolbar    = '';
         var $currentEditableTextarea = '';
         var currentTextBoxWrapper   = '';

         // When previewing text in standard reply discussion controller,
         // the mutation will cause the iframe to be inserted AGAIN, thus
         // having multiple iframes with identical properties, which, upon
         // reverting back to editing mode, will break everything, so kill
         // mutation callback immediately, so check if iframe already exists.
         if ($t.find('iframe').hasClass('vanilla-editor-text')) {
            return false;
         }

         if (currentEditorFormat.length) {

             formatOriginal          = currentEditorFormat[0].value;
             currentEditorFormat     = currentEditorFormat[0].value.toLowerCase();
             format                  = currentEditorFormat + '';
             $currentEditorToolbar    = $t.find('.editor-format-'+ format);
             //currentEditableTextarea = t.find('#Form_Body');
             $currentEditableTextarea = $t.find('.BodyBox');

            if (textareaObj) {
                $currentEditableTextarea = textareaObj;
             }

             currentTextBoxWrapper   = $currentEditableTextarea.parent('.TextBoxWrapper');

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
         if ($currentEditorToolbar.length
         && $currentEditableTextarea.length) {

            var currentEditableCommentId = (new Date()).getTime();
            var editorName               = 'vanilla-editor-text-'+ currentEditableCommentId;

            switch (format) {
               case 'wysiwyg':
               case 'ipb':
               case 'bbhtml':
               case 'bbwysiwyg':

                   // Lazyloading scripts, then run single callback
                   $.when(
                      loadScript(assets + '/js/wysihtml5-0.4.0pre.js?v=' + editorVersion),
                      loadScript(assets + '/js/advanced.js?v=' + editorVersion),
                      loadScript(assets + '/js/jquery.wysihtml5_size_matters.js?v=' + editorVersion)
                   ).done(function(){

                      var editorRules = {
                         // Give the editor a name, the name will also be set as class name on the iframe and on the iframe's body
                         name:                 editorName,
                         // Whether the editor should look like the textarea (by adopting styles)
                         style:                true,
                         // Id of the toolbar element or DOM node, pass false value if you don't want any toolbar logic
                         toolbar:              $currentEditorToolbar.get(0),
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
                         //placeholderText:      "Write something!",
                         // Whether the composer should allow the user to manually resize images, tables etc.
                         allowObjectResizing:  true,
                         // Whether the rich text editor should be rendered on touch devices (wysihtml5 >= 0.3.0 comes with basic support for iOS 5)
                         supportTouchDevices:  true,
                         // Whether senseless <span> elements (empty or without attributes) should be removed/replaced with their content
                         cleanUp:              true
                      };

                      // instantiate new editor
                      var editor = new wysihtml5.Editor($currentEditableTextarea[0], editorRules);

                      editor.on('load', function(e) {
                          if (!editor.composer) {
                              $currentEditorToolbar.hide();
                              return;
                          }

                         // enable auto-resize
                         $(editor.composer.iframe).wysihtml5_size_matters();
                         editorHandleQuotesPlugin(editor);

                         // Clear textarea/iframe content on submit.
                         // This is not actually necessary here because
                         // the whole editor is removed from the page on post.
                         $currentEditableTextarea.closest('form').on('clearCommentForm', function() {
                            editor.fire('clear');
                            editor.composer.clear();
                            this.reset();
                            $currentEditableTextarea.val('');
                            //$('iframe').contents().find('body').empty();
                            $(editor.composer.iframe).css({"min-height": "inherit"});
                         });

                         // Fix problem of editor losing its default p tag
                         // when loading another instance on the same page.
                         nullFix(editor);

                        // iOS
                        iOSwysiFix(editor);

                         //wysiPasteFix(editor);
                         fullPageInit(editor);
                         editorSetupDropdowns(editor);

                         // If editor is being loaded inline, then focus it.
                         if (!singleInstance) {
                           //scrollToEditorContainer(editor.textarea.element);
                           editor.focus();
                         }

                         if (debug) {
                            wysiDebug(editor);
                         }

                         // Enable at-suggestions
                         var iframe = $(editor.composer.iframe);
                         var iframe_body = iframe.contents().find('body')[0];
                         atCompleteInit(iframe_body, iframe[0]);


                         // Enable file uploads. Pass the iframe as the
                         // drop target in wysiwyg, while the regular editor
                         // modes will just require the standard textarea
                         // element.
                         fileUploadsInit(iframe, editor);
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
                               composer.commands.exec("insertHTML", "<p><br></p>");
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
                               composer.commands.exec("insertHTML", "<p><br></p>");
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
                              composer.commands.exec("insertHTML", "<p><br></p>");
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
                      loadScript(assets + '/js/buttonbarplus.js?v=' + editorVersion),
                      loadScript(assets + '/js/jquery.hotkeys.js?v=' + editorVersion),
                      loadScript(assets + '/js/rangy.js?v=' + editorVersion)
                   ).done(function() {
                      ButtonBar.AttachTo($($currentEditableTextarea)[0], formatOriginal);
                      fullPageInit();
                      editorSetupDropdowns();
                      if (!singleInstance) {
                         //scrollToEditorContainer($(currentEditableTextarea)[0]);
                         editorSetCaretFocusEnd($currentEditableTextarea[0]);
                      }

                      // Enable at-suggestions
                      atCompleteInit($currentEditableTextarea, '');

                      // Enable file uploads
                      fileUploadsInit($currentEditableTextarea, '');
                   });
                   break;

               case 'text':
               case 'textex':

                   break;
            }

            // Set up on editor load
            editorSetHelpText(formatOriginal, currentTextBoxWrapper);

            // some() loop requires true to end loop. every() requires false.
            return true;
         }
      } //editorInit

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
