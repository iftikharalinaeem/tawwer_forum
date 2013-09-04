
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

