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
         $(but).after('<span class="url-message">kerdoinkers</span>');
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
   
});
