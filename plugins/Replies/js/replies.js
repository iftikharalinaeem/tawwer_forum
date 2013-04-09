$.fn.toggleReplyForm = function(open) {
   var $list = $(this).closest('.DataList-Replies');
   var $button = $list.closest('.Item-Body').find('a.Reply');
   
   if (open == null) {
      open = ($list.css('display') == 'none' || $('form', $list).css('display') == 'none');
   }
   
   if (open) {
      // Open the form.
      $('.FormPlaceholder', $list).hide();
      $('form', $list).show();
      $list.show();
      
      $button.addClass('Open');
      
      $('textarea', $list).focus();
   } else {
      // Close the form.
      if ($list.children().length == 1) {
         $list.hide();
      } else {
         $('form', $list).hide();
         $('.FormPlaceholder', $list).show();
      }
      
      $button.removeClass('Open');
   }
};

$(document).on('click', '.FormPlaceholder', function(e) {
   e.preventDefault();
   $(this).toggleReplyForm(true);
});

$(document).on('click', '.DataList-Replies .Cancel', function(e) {
   e.preventDefault();
   $(this).toggleReplyForm(false);
});

$(document).on('click', '.ReactButton.Reply', function(e) {
   e.preventDefault();
   $(this).closest('.Item-Body').find('.DataList-Replies').toggleReplyForm();
});

$(document).on('submit', '.Item-ReplyForm form', function(e) {
   e.preventDefault();
   
   var $form = $(this);
   
   if($(':submit', $form).hasClass('InProgress'))
      return;
   
   $(':submit', $form).addClass('InProgress');
   
   $form.ajaxSubmit({
      success: function(data) {
         if (data)
            gdn.processTargets(data.Targets);
      }
   });
});