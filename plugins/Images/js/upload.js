/*
 * jQuery File Upload Plugin JS Example 6.7
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true, unparam: true, regexp: true */
/*global $, window, document */
fileSize = function (bytes) {
   if (typeof bytes !== 'number') {
         return '';
   }
   if (bytes >= 1000000000) {
         return (bytes / 1000000000).toFixed(2) + ' GB';
   }
   if (bytes >= 1000000) {
         return (bytes / 1000000).toFixed(2) + ' MB';
   }
   return (bytes / 1000).toFixed(2) + ' KB';
};

$(function () {
    'use strict';
    var frmselector = '#UploadForm, .CommentForm form';

    // Initialize the jQuery File Upload widget:
    $(frmselector).fileupload();

    // Enable iframe cross-domain access via redirect option:
    $(frmselector).fileupload(
        'option',
        'redirect',
        gdn.url('library/vendors/jQueryFileUpload/cors/result.html?%s')
    );

   $(frmselector).fileupload('option', {
      autoUpload: true,
      url: gdn.url('vanilla/post/uploadimage/'),
      maxFileSize: 5000000,
      acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
      process: [
            {
               action: 'load',
               fileTypes: /^image\/(gif|jpeg|png)$/,
               maxFileSize: 20000000 // 20MB
            },
            {
               action: 'resize',
               maxWidth: 1440,
               maxHeight: 900
            },
            {
               action: 'save'
            }
      ]
   });
   
   // Upload server status check for browsers with CORS support:
   if ($.support.cors) {
      $.ajax({
            url: gdn.url('vanilla/post/image/'),
            type: 'HEAD'
      }).fail(function () {
            $('<span class="alert alert-error"/>')
               .text('Upload server currently unavailable - ' +
                        new Date())
               .appendTo(frmselector);
      });
   }
   
   // Handle grabbing files from remote urls
   $('.UrlButton').click(function() {
      var but = this;
      gdn.disable(but);
      $.ajax({
            data: { 'inputUrl' : $('.url-input').val() },
            dataType: 'json',
            url: gdn.url('vanilla/post/uploadimage/'),
            type: 'POST'
      }).fail(function () {
         $('.url-message').remove();
         gdn.informMessage('Oops. Something went wrong!');
      }).done(function(data) {
         $('.url-message').remove();
         var func = tmpl('template-download');
         var html = func({
               files: data,
               formatFileSize: fileSize
         });
         $('#filetable > tbody').append(html);
         $('.url-input').val('').focus();
      }).always(function() {
         gdn.enable(but);
      });
      return false;
   });
   
   $('.CommentFormToggle').click(function() {
      $('.CommentForm').show();
      $(this).parents('.CommentForm').hide();
      return false;
   });
   
   
   // Hijack comment form button clicks.
   $('.ImageButton').click(function() {
      console.log('imagebutton');
      var btn = this;
      var parent = $(btn).parents('.NewImageForm');
      var frm = $(parent).find('form');
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW 
      postValues += '&Type=Post';
      var discussionID = $(frm).find('[name$=DiscussionID]');
      var inpCommentID = $(frm).find('input:hidden[name$=CommentID]');
      discussionID = discussionID.length > 0 ? discussionID.val() : 0;
      var comments = $('ul.Comments li.ItemComment');
      var lastComment = $(comments).get(comments.length-1);
      var lastCommentID = $(lastComment).attr('id');
      if (lastCommentID)
         lastCommentID = lastCommentID.indexOf('Discussion_') == 0 ? 0 : lastCommentID.replace('Comment_', '');
      else
         lastCommentID = 0;
         
      postValues += '&Form_LastCommentID=' + lastCommentID;
      var action = $(frm).attr('action');
      if (action.indexOf('?') >= 0)
         action = action.substr(0, action.indexOf('?'));
      if (discussionID > 0) {
         if (action.substr(-1,1) != '/')
            action += '/';
         
         action += discussionID;
      }
      gdn.disable(btn);
      
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(xhr) {
            console.log(xhr);
            gdn.informError(xhr);
         },
         success: function(json) {
            console.log('success');
            json = $.postParseJson(json);
            
            var processedTargets = false;
            // If there are targets, process them
            if (json.Targets && json.Targets.length > 0) {
               for(i = 0; i < json.Targets.length; i++) {
                  if (json.Targets[i].Type != "Ajax") {
                     json.Targets[i].Data = json.Data;
                     processedTargets = true;
                     break;
                   }
               }
               gdn.processTargets(json.Targets);
            }

            var commentID = json.CommentID;
            $(inpCommentID).val(commentID);
            // Remove any old errors from the form
            $(frm).find('div.Errors').remove();
            if (json.FormSaved == false) {
               $(frm).prepend(json.ErrorMessages);
               json.ErrorMessages = null;
            } else {
               // TODO: Clean up the form
               if (processedTargets) {
                  // Don't do anything with the data b/c it's already been handled by processTargets
               } else {
                  gdn.definition('LastCommentID', commentID, true);
                  // If adding a new comment, show all new comments since the page last loaded, including the new one.
                  if (gdn.definition('PrependNewComments') == '1') {
                     $(json.Data).prependTo('ul.Comments');
                     $('ul.Comments li:first').effect("highlight", {}, "slow");
                  } else {
                     $(json.Data).appendTo('ul.Comments');
                     $('ul.Comments li:last').effect("highlight", {}, "slow");
                  }
               }
               // Remove any "More" pager links (because it is typically replaced with the latest comment by this function)
               if (gdn.definition('PrependNewComments') != '1') // If prepending the latest comment, don't remove the pager.
                  $('#PagerMore').remove();
               
            }
            gdn.inform(json);
            return false;
         },
         complete: function(XMLHttpRequest, textStatus) {
            console.log('complete');
            $('#filetable > tbody').html('');
            gdn.enable(btn);
         }
      });
      return false;
   });   
   
});
