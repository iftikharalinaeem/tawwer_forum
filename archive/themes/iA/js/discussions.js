jQuery(document).ready(function($) {
   
   // Hide all comment forms on blur.
   $('div.CommentForm textarea.TextBox').livequery(function() {
      $(this).blur(function() {
         if ($(this).val() == '') {
            $(this).parents('li.Item').find('a.CommentLink').show();
            $(this).parents('div.CommentForm').hide();
         }
      });
   });
   
   // Reveal comment forms on anchor click.
   $('a.CommentLink').live('click', function(){
      var CommentForm = $(this).parents('li.Item').find('div.CommentForm');
      $(CommentForm).show();
      $(this).hide();
      $(CommentForm).find('textarea').focus();
      return false;
   });
   
   // Make all textareas autogrow
   $('textarea.TextBox').livequery(function() {
      $(this).autogrow();
   });
   
   // Change discussion box text when focusing/blurring.
   $('#DiscussionForm textarea.TextBox').livequery(function() {
      var box = this;
      // Hide/show help text
      $(box).focus(function() {
         if ($(this).val() == "What's on your mind?")
            $(this).val('');
      });
      $(box).blur(function() {
         if ($(this).val() == '')
            $(this).val("What's on your mind?");
      });
   });
   
   gdn.matchHeight = function(selectorParent, selectorChild) {
      var childHeight = $(selectorChild).height();
      var parentHeight = $(selectorParent).height();
      var childPadding = $(selectorChild).outerHeight() - childHeight;
      var parentPadding = $(selectorParent).outerHeight() - parentHeight;
      if (parentHeight > childHeight) {
         $(selectorChild).css('min-height', parentHeight+parentPadding-childPadding);
      } else {
         $(selectorParent).css('min-height', childHeight+childPadding-parentPadding);
      }
   }   

   gdn.matchDocumentHeight = function(selector) {
      var height = $(selector).height();
      var padding = $(selector).outerHeight() - height;
      var doc = $(document).height();
      if (doc > height)
         $(selector).css('height', doc - padding);
   }   
   
   // Make sure that all panels stretch to the bottom of the document
   gdn.matchDocumentHeight('#Menu,#Content,#SubContent');
   gdn.matchHeight('#DiscussionForm', '.TopAdvertisement');
   $('ul.Discussions li.Item').each(function() {
      var parent = this;
      var discussionID = $(parent).attr('id').replace('Discussion_', '');
      gdn.matchHeight('#Discussion_'+discussionID, '#CommentsFor_'+discussionID);
   });
   
   $(window).resize(function(){
      // gdn.matchDocumentHeight('#Menu,#Content,#SubContent');
      gdn.matchHeight('#DiscussionForm', '.TopAdvertisement');
      $('ul.Discussions li.Item').each(function() {
         var parent = this;
         var discussionID = $(parent).attr('id').replace('Discussion_', '');
         gdn.matchHeight('#Discussion_'+discussionID, '#CommentsFor_'+discussionID);
      });
   });

   // Hijack discussion form button clicks
   $('#DiscussionForm :submit').click(function() {
      var btn = this;
      var frm = $(btn).parents('form').get(0);
      var textbox = $(frm).find('textarea');
      var postValues = $(frm).serialize();
      postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
      postValues += '&'+btn.name+'='+btn.value;
      // Add a spinner and disable the buttons
      $(frm).find(':submit:last').before('<span class="TinyProgress">&nbsp;</span>');
      $(frm).find(':submit').attr('disabled', 'disabled');      
      $.ajax({
         type: "POST",
         url: $(frm).attr('action'),
         data: postValues,
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $('.Popup').remove();
            $.popup({}, XMLHttpRequest.responseText);
         },
         success: function(json) {
            // Remove any old popups
            $('.Popup').remove();
            // Remove any old errors from the form
            $(frm).find('div.Errors').remove();
            if (json.FormSaved == false) {
               $(frm).prepend(json.StatusMessage);
               json.StatusMessage = null;
            } else {
               // Load the new discussion
               $('ul.Discussions').prepend(json.DiscussionHtml);
               $('ul.Discussions li.Hidden').slideDown('fast');
               $('ul.Discussions li.Hidden').removeClass('Hidden');
               $(textbox).val('');
               $(textbox).blur();
            }
         },
         complete: function(XMLHttpRequest, textStatus) {
            // Remove any spinners, and re-enable buttons.
            $('span.TinyProgress').remove();
            $(frm).find(':submit').removeAttr("disabled");
         }
      });
      return false;
   });

   // Show options on each row (if present)
   $('li.Item').livequery(function() {
      var row = this;
      var opts = $(row).find('ul.Options');
      var btn = $(row).find('a.Delete');
      $(opts).hide();
      $(btn).hide();
      $(row).hover(function() {
         $(opts).show();
         $(btn).show();
         $(row).addClass('Active');
      }, function() {
         if (!$(opts).find('li.Parent').hasClass('Active'))
            $(opts).hide();
            
         $(btn).hide();
         $(row).removeClass('Active');            
      });
   });

   // Set up paging
   if ($.morepager)
      $('.MorePager').livequery(function() {
         $(this).morepager({
            pageContainerSelector: 'ul.Discussions:last, ul.Drafts:last'
         });
      });

});
