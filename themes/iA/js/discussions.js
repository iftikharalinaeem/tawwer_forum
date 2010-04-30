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
      var childPadding = $(selectorChild).outerHeight() - $(selectorChild).height();
      var parentPadding = $(selectorParent).outerHeight() - $(selectorParent).height();
      if (parentHeight > childHeight) {
         $(selectorChild).css('height', parentHeight+parentPadding-childPadding);
      } else {
         $(selectorParent).css('height', childHeight+childPadding-parentPadding);
      }
      // $(selectorChild).css('top', $(selectorParent).offset.top);
   }   
   
   // Make sure that all panels stretch to the bottom of the document
   gdn.matchHeight(document, '#Menu,#SubContent');
   gdn.matchHeight('#DiscussionForm', '.TopAdvertisement');
   $('ul.Discussions li.Item').each(function() {
      var parent = this;
      var discussionID = $(parent).attr('id').replace('Discussion_', '');
      gdn.matchHeight('#Discussion_'+discussionID, '#CommentsFor_'+discussionID);
   });
   
   $(window).resize(function(){
      gdn.matchHeight(document, '#Menu,#SubContent');
      gdn.matchHeight('#DiscussionForm', '.TopAdvertisement');
      $('ul.Discussions li.Item').each(function() {
         var parent = this;
         var discussionID = $(parent).attr('id').replace('Discussion_', '');
         gdn.matchHeight('#Discussion_'+discussionID, '#CommentsFor_'+discussionID);
      });
   });

   
   // Show drafts delete button on hover
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
