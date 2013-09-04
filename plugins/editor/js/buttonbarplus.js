/*
 * Caret insert JS
 * 
 * This code extends the base object with a method called 'insertAtCaret', which
 * allows text to be added to a textArea at the cursor position.
 * 
 * Thanks to http://technology.hostei.com/?p=3
 */
jQuery.fn.insertAtCaret = function (tagName) {
   return this.each(function(){
      if (document.selection) {
         //IE support
         this.focus();
         sel = document.selection.createRange();
         sel.text = tagName;
         this.focus();
      } else if (this.selectionStart || this.selectionStart == '0') {
         //MOZILLA/NETSCAPE support
         startPos = this.selectionStart;
         endPos = this.selectionEnd;
         scrollTop = this.scrollTop;
         this.value = this.value.substring(0, startPos) + tagName + this.value.substring(endPos,this.value.length);
         this.focus();
         this.selectionStart = startPos + tagName.length;
         this.selectionEnd = startPos + tagName.length;
         this.scrollTop = scrollTop;
      } else {
         this.value += tagName;
         this.focus();
      }
   });
};

jQuery.fn.insertRoundCaret = function(strStart, strEnd, strReplace) {
   return this.each(function() {
      if (document.selection) {
         // IE support
         stringBefore = this.value;
         this.focus();
         sel = document.selection.createRange();
         insertString = strReplace ? strReplace : sel.text;
         fullinsertstring = strStart + insertString + strEnd;
         sel.text = fullinsertstring;
         document.selection.empty();
         this.focus();
         stringAfter = this.value;
         i = stringAfter.lastIndexOf(fullinsertstring);
         range = this.createTextRange();
         numlines = stringBefore.substring(0, i).split("\n").length;
         i = i + 3 - numlines + tagName.length;
         j = insertstring.length;
         range.move("character", i);
         range.moveEnd("character", j);
         range.select();
      } else if (this.selectionStart || this.selectionStart == '0') {
         // MOZILLA/NETSCAPE support
         startPos = this.selectionStart;
         endPos = this.selectionEnd;
         scrollTop = this.scrollTop;
         
         if (!strReplace)
            strReplace = this.value.substring(startPos, endPos);
         
         this.value = this.value.substring(0, startPos) + strStart
                    + strReplace + strEnd
                    + this.value.substring(endPos, this.value.length);
         this.focus();
         this.selectionStart = startPos + strStart.length;
         this.selectionEnd = this.selectionStart + strReplace.length;
         this.scrollTop = scrollTop;
      } else {
         if (!strReplace)
            strReplace = '';
         this.value += strStart + strReplace + strEnd;
         this.focus();
      }
      
   });
}

jQuery.fn.hasSelection = function() {
   var sel = false;
   this.each(function() {
      if (document.selection) {
         sel = document.selection.createRange().text;
      } else if (this.selectionStart || this.selectionStart == '0') {
         startPos = this.selectionStart;
         endPos = this.selectionEnd;
         scrollTop = this.scrollTop;
         sel = this.value.substring(startPos, endPos);
      }
   });
   
   return sel;
}

/*
 * Caret insert advanced
 * 
 * This code allows insertion on complex tags, and was extended by @Barrakketh 
 * (barrakketh@gmail.com) from http://forums.penny-arcade.com to allow 
 * parameters.
 * 
 * Thanks!
 */

//$.fn.insertRoundTag = function(tagName, opts, props) {
//   return this.each(function() {
//      var opener = opts.opener || '[';
//      var closer = opts.closer || ']';
//      var closetype = opts.closetype || 'full';
//      var shortporp = opts.shortprop;
//      
//      strStart = opener + tagName;
//      strEnd = '';
//      
//      if (shortprop)
//         strStart = strStart + '="' + opt + '"';
//      
//      if (props) {
//         for ( var param in props) {
//            strStart = strStart + ' ' + param + '="' + props[param] + '"';
//         }
//      }
//
//      if (closetype == 'full') {
//         strStart = strStart + closer;
//         strEnd = opener + '/' + tagName + closer;
//      } else {
//         strStart = strStart + '/' + closer;
//      }
//
//      $(this).insertRoundCaret(strStart, strEnd);
//    });
//};

$.fn.insertRoundTag = function(tagName, opts, props){
   var opentag = opts.opentag != undefined ? opts.opentag : tagName;
   var closetag = opts.closetag != undefined ? opts.closetag : tagName;
   var prefix = opts.prefix != undefined ? opts.prefix : '';
   var suffix = opts.suffix != undefined ? opts.suffix : '';
   var prepend = opts.prepend != undefined ? opts.prepend : '';
   var replace = opts.replace != undefined ? opts.replace : false;
   var opener = opts.opener != undefined ? opts.opener : '';
   var closer = opts.closer != undefined ? opts.closer : '';
   var closeslice = opts.closeslice != undefined ? opts.closeslice : '/';
   var closetype = opts.closetype != undefined ? opts.closetype : 'full';
   var shortprop = opts.shortprop;
   var focusprop = opts.center;
   var hasFocused = false;
   
   strStart = prefix + opener + opentag;
   strEnd = '';
   
   if (shortprop) {
      strStart = strStart + '="' + shortprop;
      if (focusprop == 'short') {
         strEnd = strEnd + '"';
         hasFocused = true;
      }
      else 
         strStart = strStart + '"';
   }
   if (props) {
      var focusing = false;
      for ( var param in props) {
         if (hasFocused) {strEnd = strEnd + ' ' + param + '="' + props[param] + '"';continue;}
         
         if (!hasFocused) {
            strStart = strStart + ' ' + param + '="' + props[param];
            if (param == focusprop) {
               focusing = true;
               hasFocused = true;
            }
         }
         
         if (focusing) {
            strEnd = strEnd + '"';
            focusing = false;
         } else {
            strStart = strStart + '"';
         }
      }
   }
   
   strReplace = '';
   if (prefix) {
      var selection = $(this).hasSelection();
      if (selection) {
         strReplace = selection.replace(/\n/g, '\n'+prefix);
      }
   }
   
   if (replace != false) {
      strReplace = replace;
   }
   
   if (closetype == 'full') {
      if (!hasFocused)
         strStart = strStart + closer;
      else
         strEnd = strEnd + closer;
      
      strEnd = strEnd + opener + closeslice + closetag + closer + suffix;
   } else {
      if (closeslice && closeslice.length)
         closeslice = " "+closeslice;
      if (!hasFocused)
         strStart = strStart + closeslice + closer + suffix;
      else
         strEnd = strEnd + closeslice + closer + suffix;
   }
   jQuery(this).insertRoundCaret(strStart+prepend, strEnd, strReplace);
}

jQuery(document).ready(function($) {
   
   ButtonBar = {
      
      Const: {
         URL_PREFIX: 'http://'
      },
      
      AttachTo: function(TextArea) {
         // Load the buttonbar and bind this textarea to it
         var ThisButtonBar = $(TextArea).closest('form').find('.editor');
         $(ThisButtonBar).data('ButtonBarTarget', TextArea);
         
         var format = gdn.definition('editorInputFormat', 'Html');
         
         // Apply the page's InputFormat to this textarea.
         $(TextArea).data('InputFormat', format);
         
         // Build button UIs 
         // TODO remove redundancy, have operations dependent on another value
         $(ThisButtonBar).find('.icon').each(function(i, el){
            var Operation = $(el).attr('title').toLowerCase();
            
            var UIOperation = Operation.charAt(0).toUpperCase() + Operation.slice(1);
            
            var Action = "ButtonBar"+UIOperation;
            $(el).addClass(Action);
         
         });

         // Attach events
         $(ThisButtonBar).find('.icon').mousedown(function(event){
            var MyButtonBar = $(event.target).closest('.editor');
            //var Button = $(event.target).find('span').closest('.ButtonWrap');
            var Button = $(event.target);

            //if ($(Button).hasClass('ButtonOff')) return;

            var TargetTextArea = $(MyButtonBar).data('ButtonBarTarget');
            if (!TargetTextArea) return false;

            var Operation = ($(Button).attr('title')) 
               ? $(Button).attr('title').toLowerCase().replace(/\s+/g, '') 
               : '';
                  
            ButtonBar.Perform(TargetTextArea, Operation, event);
            return false;
         });
         
         // Attach shortcut keys
         // TODO use these for whole editor.
         ButtonBar.BindShortcuts(TextArea);
      },
      
      BindShortcuts: function(TextArea) {
         ButtonBar.BindShortcut(TextArea, 'bold', 'ctrl+B');
         ButtonBar.BindShortcut(TextArea, 'italic', 'ctrl+I');
         ButtonBar.BindShortcut(TextArea, 'underline', 'ctrl+U');
         ButtonBar.BindShortcut(TextArea, 'strike', 'ctrl+S');
         ButtonBar.BindShortcut(TextArea, 'url', 'ctrl+L');
         ButtonBar.BindShortcut(TextArea, 'code', 'ctrl+O');
         ButtonBar.BindShortcut(TextArea, 'quote', 'ctrl+Q');
         ButtonBar.BindShortcut(TextArea, 'post', 'tab');
      },
      
      BindShortcut: function(TextArea, Operation, Shortcut, ShortcutMode, OpFunction) {
         if (OpFunction == undefined)
            OpFunction = function(e){ButtonBar.Perform(TextArea, Operation, e);}
         
         if (ShortcutMode == undefined)
            ShortcutMode = 'keydown';
         
         $(TextArea).bind(ShortcutMode,Shortcut,OpFunction);
         
         /* for now title is fickle
         var UIOperation = Operation.charAt(0).toUpperCase() + Operation.slice(1);
         var Action = "ButtonBar"+UIOperation;
         
         var ButtonBarObj = $(TextArea).closest('form').find('.icon');
         var Button = $(ButtonBarObj).find('.'+Action);
         Button.attr('title', Button.attr('title')+', '+Shortcut);
         */
         
      },

      Perform: function(TextArea, Operation, Event) {
         Event.preventDefault();
         
         var InputFormat = $(TextArea).data('InputFormat');
         var PerformMethod = 'Perform'+InputFormat;
         if (ButtonBar[PerformMethod] == undefined)
            return;
         
         // Call performer
         ButtonBar[PerformMethod](TextArea,Operation);
         
         switch (Operation) {
            case 'post':
               $(TextArea).closest('form').find('.CommentButton').focus();
               break;
         }
      },
      
      PerformBBCode: function(TextArea, Operation) {
         bbcodeOpts = {
            opener: '[',
            closer: ']'
         }
         switch (Operation) {
            case 'bold':
               $(TextArea).insertRoundTag('b',bbcodeOpts);
               break;

            case 'italic':
               $(TextArea).insertRoundTag('i',bbcodeOpts);
               break;
            /*
            case 'underline':
               $(TextArea).insertRoundTag('u',bbcodeOpts);
               break;
            */

            case 'strike':
               $(TextArea).insertRoundTag('s',bbcodeOpts);
               break;

            case 'code':
               $(TextArea).insertRoundTag('code',bbcodeOpts);
               break;

            case 'quote':
               $(TextArea).insertRoundTag('quote',bbcodeOpts);
               break;

            case 'spoiler':
               $(TextArea).insertRoundTag('spoiler',bbcodeOpts);
               break;

            case 'url':
               var thisOpts = $.extend(bbcodeOpts, {});
               
               // Hooking in to standardized dropdown for submitting links
               var inputBox = $('.editor-input-url');
               $(inputBox).parent().find('.Button')
                  .off('click.insertData')
                  .on('click.insertData', function(e) {
                     if (!$(this).hasClass('Cancel')) {
                        var val           = inputBox[0].value;
                        var GuessText     = val.replace(ButtonBar.Const.URL_PREFIX,'').replace('www.','');
                        var CurrentSelect = $(TextArea).hasSelection();

                        CurrentSelectText = (CurrentSelect) 
                           ? CurrentSelect.toString()
                           : GuessText;

                        thisOpts.shortprop = val;
                        thisOpts.replace = CurrentSelectText;

                        $(TextArea).insertRoundTag('url',thisOpts);
                        
                        inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                     }
               });


               break;
               
            case 'image':
               
               var thisOpts = $.extend(bbcodeOpts,{});
               
               // Hooking in to standardized dropdown for submitting links
               var inputBox = $('.editor-input-image');
               $(inputBox).parent().find('.Button')
                  .off('click.insertData')
                  .on('click.insertData', function(e) {
                     if (!$(this).hasClass('Cancel')) {
                        var val          = inputBox[0].value;
                        thisOpts.replace = val; 
                        $(TextArea).insertRoundTag('img',thisOpts);    

                        inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                     }
               });               
               
               break;
            
            case 'alignleft':
               $(TextArea).insertRoundTag('left',bbcodeOpts);
               break;
            case 'aligncenter':
               $(TextArea).insertRoundTag('center',bbcodeOpts);
               break;
            case 'alignright':
               $(TextArea).insertRoundTag('right',bbcodeOpts);
               break;
               
            case 'orderedlist':
               
               var thisOpts = $.extend(bbcodeOpts, {
                  prefix:'',
                  opentag:'[list=1][*] ',
                  closetag:'[/list]',
                  opener:'',
                  closer:'',
                  closeslice: ''
               });
               
               $(TextArea).insertRoundTag('', thisOpts);
               

               break;
               
            case 'unorderedlist':
               
               var thisOpts = $.extend(bbcodeOpts, {
                  prefix:'',
                  opentag:'[list][*] ',
                  closetag:'[/list]',
                  opener:'',
                  closer:'',
                  closeslice: ''

               });
               
               $(TextArea).insertRoundTag('', thisOpts);


               break;
         }
      },
      
      PerformHtml: function(TextArea, Operation) {
         var htmlOpts = {
            opener: '<',
            closer: '>'
         }
         switch (Operation) {
            case 'bold':
               $(TextArea).insertRoundTag('b',htmlOpts, {'class':'Bold'});
               break;

            case 'italic':
               $(TextArea).insertRoundTag('i',htmlOpts, {'class':'Italic'});
               break;
               
            /*
            case 'underline':
               $(TextArea).insertRoundTag('u',htmlOpts, {'class':'Underline'});
               break;
            */
           
            case 'strike':
               $(TextArea).insertRoundTag('del',htmlOpts, {'class':'Delete'});
               break;

            case 'code':
               var multiline = $(TextArea).hasSelection().indexOf('\n') >= 0;
               if (multiline) {
                  var thisOpts = $.extend(htmlOpts, {
                     opentag:'<pre class="CodeBlock"><code>',
                     closetag:'</code></pre>',
                     opener:'',
                     closer:'',
                     closeslice: ''
                  });
                  $(TextArea).insertRoundTag('',thisOpts);
               } else {
                  $(TextArea).insertRoundTag('code',htmlOpts,{'class':'CodeInline'});
               }
               break;

            case 'quote':
               $(TextArea).insertRoundTag('blockquote',htmlOpts, {'class':'Quote'});
               break;

            case 'spoiler':
               $(TextArea).insertRoundTag('div',htmlOpts,{'class':'Spoiler'});
               break;

            case 'url':
               var urlOpts = {};
               var thisOpts = $.extend(htmlOpts, {});

               // Hooking in to standardized dropdown for submitting links
               var inputBox = $('.editor-input-url');
               $(inputBox).parent().find('.Button')
                  .off('click.insertData')
                  .on('click.insertData', function(e) {
                     if (!$(this).hasClass('Cancel')) {
                        var val           = inputBox[0].value;
                        var GuessText     = val.replace(ButtonBar.Const.URL_PREFIX,'').replace('www.','');
                        var CurrentSelect = $(TextArea).hasSelection();

                        CurrentSelectText = (CurrentSelect) 
                           ? CurrentSelect.toString()
                           : GuessText;

                        urlOpts.href      = val;
                        thisOpts.replace  = CurrentSelectText;

                        $(TextArea).insertRoundTag('a',thisOpts,urlOpts);

                        inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                     }
               });
               
               break;
               
            case 'image':
               var urlOpts = {};
               var thisOpts = $.extend(htmlOpts, {
                  closetype: 'short'
               });
               
               // Hooking in to standardized dropdown for submitting links
               var inputBox = $('.editor-input-image');
               $(inputBox).parent().find('.Button')
                  .off('click.insertData')
                  .on('click.insertData', function(e) {
                     if (!$(this).hasClass('Cancel')) {
                        var val     = inputBox[0].value;
                        urlOpts.src = val;
                        $(TextArea).insertRoundTag('img',thisOpts,urlOpts);   

                        inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                     }
               });

               break;
               
            case 'alignleft':
               $(TextArea).insertRoundTag('div',htmlOpts,{'class':'AlignLeft'});
               break;
            case 'aligncenter':
               $(TextArea).insertRoundTag('div',htmlOpts,{'class':'AlignCenter'});
               break;
            case 'alignright':
               $(TextArea).insertRoundTag('div',htmlOpts,{'class':'AlignRight'});
               break;
               
            case 'orderedlist':
               
               var thisOpts = $.extend(htmlOpts, {
                  prefix:'',
                  opentag:'<ol><li>',
                  closetag:'</li></ol>',
                  opener:'',
                  closer:'',
                  closeslice: ''
               });
               
               $(TextArea).insertRoundTag('', thisOpts);

               break;
               
            case 'unorderedlist':
               
               var thisOpts = $.extend(htmlOpts, {
                  prefix:'',
                  opentag:'<ul><li>',
                  closetag:'</li></ul>',
                  opener:'',
                  closer:'',
                  closeslice: ''
               });
               
               $(TextArea).insertRoundTag('', thisOpts);

               break;
         }
      },
      
      PerformMarkdown: function(TextArea, Operation) {
         var markdownOpts = {
            opener: '',
            closer: '',
            closeslice: ''
         }
         switch (Operation) {
            case 'bold':
               $(TextArea).insertRoundTag('**',markdownOpts);
               break;

            case 'italic':
               $(TextArea).insertRoundTag('_',markdownOpts);
               break;
            
            /*
            case 'underline':
               // no known equivalent
               return;
               break;
            */
           
            case 'strike':
               $(TextArea).insertRoundTag('~~',markdownOpts);
               break;

            case 'code':
               var multiline = $(TextArea).hasSelection().indexOf('\n') >= 0;
               if (multiline) {
                  var thisOpts = $.extend(markdownOpts, {
                     prefix:'    ',
                     opentag:'',
                     closetag:'',
                     opener:'',
                     closer:''
                  });
                  $(TextArea).insertRoundTag('',thisOpts);
               } else {
                  $(TextArea).insertRoundTag('`',markdownOpts);
               }
               break;

            case 'quote':
               var thisOpts = $.extend(markdownOpts, {
                  prefix:'> ',
                  opentag:'',
                  closetag:'',
                  opener:'',
                  closer:''
               });
               $(TextArea).insertRoundTag('',thisOpts);
               break;

            case 'spoiler':
               var thisOpts = $.extend(markdownOpts, {
                  prefix:'>! ',
                  opentag:'',
                  closetag:'',
                  opener:'',
                  closer:''
               });
               $(TextArea).insertRoundTag('',thisOpts);
               break;

            case 'url':

               // Hooking in to standardized dropdown for submitting links
               var inputBox = $('.editor-input-url');
               $(inputBox).parent().find('.Button')
                  .off('click.insertData')
                  .on('click.insertData', function(e) {
                     if (!$(this).hasClass('Cancel')) {
                        var val           = inputBox[0].value;
                        var GuessText     = val.replace(ButtonBar.Const.URL_PREFIX,'').replace('www.','');
                        var CurrentSelect = $(TextArea).hasSelection();

                        CurrentSelectText = (CurrentSelect) 
                           ? CurrentSelect.toString()
                           : GuessText;

                        var thisOpts = $.extend(markdownOpts, {
                           prefix: '['+CurrentSelectText+']',
                           opentag:'(',
                           closetag:')',
                           opener:'',
                           closer:'',
                           replace: val
                        });
                        $(TextArea).insertRoundTag('',markdownOpts);

                        inputBox[0].value = ButtonBar.Const.URL_PREFIX;                     
                     }
               });

               break;
               
            case 'image':
               
               var thisOpts = $.extend(markdownOpts, {
                  prefix:'',
                  opentag:'![](',
                  closetag:')',
                  opener:'',
                  closer:''
               });
               
               // Hooking in to standardized dropdown for submitting links
               var inputBox = $('.editor-input-image');
               $(inputBox).parent().find('.Button')
                  .off('click.insertData')
                  .on('click.insertData', function(e) {
                     if (!$(this).hasClass('Cancel')) {
                        var val          = inputBox[0].value;
                        thisOpts.prepend = val;
                        $(TextArea).insertRoundTag('',thisOpts);    
                        
                        inputBox[0].value = ButtonBar.Const.URL_PREFIX;
                     }
               });

               break;
               
            /* 
            // markdown has no alignment 
            case 'alignleft':
               break;
            case 'aligncenter':
               break;
            case 'alignright':
               break;
            */
           
            case 'orderedlist':
               var lines = $(TextArea).hasSelection().split('\n');
               
               
               // TODO modify insertRoundTag to accept array prefixes 
               // so that numbers can increment
               for (var i = 0, l = lines.length; i < l; i++) {
                  //console.log(i+1 +' '+ lines[i]);
               }        
               
               var i = 1;
               
               var thisOpts = $.extend(markdownOpts, {
                  prefix: i+'. ',
                  opentag:'',
                  closetag:'',
                  opener:'',
                  closer:''
               });
               $(TextArea).insertRoundTag('',thisOpts);
               break;
               
            case 'unorderedlist':
               var thisOpts = $.extend(markdownOpts, {
                  prefix:'* ',
                  opentag:'',
                  closetag:'',
                  opener:'',
                  closer:''
               });
               $(TextArea).insertRoundTag('',thisOpts);
               break;
         }
      }
      
   }
});


/*
 * jQuery Hotkeys Plugin
 * Copyright 2010, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * Based upon the plugin by Tzury Bar Yochay:
 * http://github.com/tzuryby/hotkeys
 *
 * Original idea by:
 * Binny V A, http://www.openjs.com/scripts/events/keyboard_shortcuts/
*/

(function(jQuery){
	
	jQuery.hotkeys = {
		version: "0.8",

		specialKeys: {
			8: "backspace", 9: "tab", 13: "return", 16: "shift", 17: "ctrl", 18: "alt", 19: "pause",
			20: "capslock", 27: "esc", 32: "space", 33: "pageup", 34: "pagedown", 35: "end", 36: "home",
			37: "left", 38: "up", 39: "right", 40: "down", 45: "insert", 46: "del", 
			96: "0", 97: "1", 98: "2", 99: "3", 100: "4", 101: "5", 102: "6", 103: "7",
			104: "8", 105: "9", 106: "*", 107: "+", 109: "-", 110: ".", 111 : "/", 
			112: "f1", 113: "f2", 114: "f3", 115: "f4", 116: "f5", 117: "f6", 118: "f7", 119: "f8", 
			120: "f9", 121: "f10", 122: "f11", 123: "f12", 144: "numlock", 145: "scroll", 191: "/", 224: "meta"
		},
	
		shiftNums: {
			"`": "~", "1": "!", "2": "@", "3": "#", "4": "$", "5": "%", "6": "^", "7": "&", 
			"8": "*", "9": "(", "0": ")", "-": "_", "=": "+", ";": ": ", "'": "\"", ",": "<", 
			".": ">",  "/": "?",  "\\": "|"
		}
	};

	function keyHandler( handleObj ) {
		// Only care when a possible input has been specified
		if ( typeof handleObj.data !== "string" ) {
			return;
		}
		
		var origHandler = handleObj.handler,
			keys = handleObj.data.toLowerCase().split(" ");
	
		handleObj.handler = function( event ) {
			// Don't fire in text-accepting inputs that we didn't directly bind to
			if ( this !== event.target && (/textarea|select/i.test( event.target.nodeName ) ||
				 event.target.type === "text") ) {
				return;
			}
			
			// Keypress represents characters, not special keys
			var special = event.type !== "keypress" && jQuery.hotkeys.specialKeys[ event.which ],
				character = String.fromCharCode( event.which ).toLowerCase(),
				key, modif = "", possible = {};

			// check combinations (alt|ctrl|shift+anything)
			if ( event.altKey && special !== "alt" ) {
				modif += "alt+";
			}

			if ( event.ctrlKey && special !== "ctrl" ) {
				modif += "ctrl+";
			}
			
			// TODO: Need to make sure this works consistently across platforms
			if ( event.metaKey && !event.ctrlKey && special !== "meta" ) {
				modif += "meta+";
			}

			if ( event.shiftKey && special !== "shift" ) {
				modif += "shift+";
			}

			if ( special ) {
				possible[ modif + special ] = true;

			} else {
				possible[ modif + character ] = true;
				possible[ modif + jQuery.hotkeys.shiftNums[ character ] ] = true;

				// "$" can be triggered as "Shift+4" or "Shift+$" or just "$"
				if ( modif === "shift+" ) {
					possible[ jQuery.hotkeys.shiftNums[ character ] ] = true;
				}
			}

			for ( var i = 0, l = keys.length; i < l; i++ ) {
				if ( possible[ keys[i] ] ) {
					return origHandler.apply( this, arguments );
				}
			}
		};
	}

	jQuery.each([ "keydown", "keyup", "keypress" ], function() {
		jQuery.event.special[ this ] = { add: keyHandler };
	});

})( jQuery );