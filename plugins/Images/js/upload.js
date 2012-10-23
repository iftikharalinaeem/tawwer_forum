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

jQuery(document).ready(function($) {
    'use strict';
    var frmselector = '#UploadForm';

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
      filesContainer: $('#filetable'),
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
   })
   .bind('fileuploadsent', function(e, data) {
      $('#filetable').masonry('reload');
   })
   .bind('fileuploadcompleted', function(e, data) {
      data.filesContainer.imagesLoaded(function() { data.filesContainer.masonry('reload'); }); // todo: just the new files.
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
            data: { 'inputUrl' : $('.UrlInput').val() },
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
         
         var $html = $(html);
         
         $html.imagesLoaded(function() {
            $('#filetable').append($html).masonry('reload');
         });
         $('.UrlInput').val('').focus();
      }).always(function() {
         gdn.enable(but);
      });
      return false;
   });
});
