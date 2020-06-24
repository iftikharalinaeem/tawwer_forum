$.fn.toggleReplyForm = function(open) {
   var $list = $(this).closest('.DataList-Replies');
   var $button = $list.closest('.Item-Body').find('a.Reply');
   
   if (open == null) {
      open = ($list.css('display') == 'none' || $('form', $list).css('display') == 'none');
   }
   
   if (open) {
      // Open the form.
      $('.FormPlaceholder', $list).hide();
      $list.addClass('Open');
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
      
      $list.removeClass('Open');
      $button.removeClass('Open');
   }
};

$(document).on('click', '.FormPlaceholder', function(e) {
   e.preventDefault();
   $(this).toggleReplyForm(true);
});

$(document).on('click', '.Item-ReplyForm .Cancel', function(e) {
   e.preventDefault();
   $(this).toggleReplyForm(false);
});

$(document).on('click', '.ReactButton.Reply', function(e) {
   e.preventDefault();
   $(this).closest('.Item-Body').find('.DataList-Replies').toggleReplyForm();
});

$(document).on('click', '.Option-EditReply', function(e) {
   e.preventDefault();
   
   $container = $(this).closest('.Item-Reply');
   
   // Grab the form.
   $.ajax({
      url: $(this).attr('href'),
      data: { DeliveryType: 'VIEW' },
      success: function(r) {
         var $new = $('<div>'+r+'</div>');
         $container.replaceWith($new);
         $('form', $new).ajaxForm({
            target: $new,
            data: { DeliveryType: 'VIEW' }
         });
      }
   });
});

$(document).on('submit', '.Item-ReplyForm form', function(e) {
   e.preventDefault();
   
   var $form = $(this);
   var $list = $form.closest('.DataList-Replies');
   
   if($(':submit', $form).hasClass('InProgress'))
      return;
   
   $(':submit', $form).addClass('InProgress');
   
   $form.ajaxSubmit({
      success: function(data) {
         if (data) {
            gdn.processTargets(data.Targets);
            $list.toggleReplyForm(false);
         }
      },
      complete: function() {
         $(':submit', $form).removeClass('InProgress');
      }
   });
});