<?php if (!defined('APPLICATION')) exit(); ?>
<script type='text/javascript'>//<![CDATA[ 
jQuery(document).ready(function($) {
   $('#dragbar').mousedown(function(e){
      e.preventDefault();
      // $('#mousestatus').html("mousedown" + i++);
      $(document).mousemove(function(e){
         // $('#position').html(e.pageX +', '+ e.pageY);
         $('#sidebar').css("width",e.pageX+2);
         $('#framecover').show();
         $('#main,#framecover').css("left",e.pageX+2);
      })
   });
   $(document).mouseup(function(e){
      $(document).unbind('mousemove');
      $('#framecover').hide();
   });
   pushCss = function() {
      var head = $("#vframe").contents().find("head");
      if (head) {
         $("#vframe").contents().find('#editcss').remove();
         head.append('<style type="text/css" id="editcss">'+$('#Form_CSS').val()+'</style>');
      }
   }
   saveCss = function(el) {
      // Ajax/Post the form
      var frm = $(el).parents('form');
      var postValues = frm.serialize() + '&DeliveryType=VIEW&DeliveryMethod=JSON';
      if ($(el).attr('id') == 'Form_Apply')
         postValues += '&Form%2FApply=Apply';
      
      var action = frm.attr('action');
      $.ajax({
         type: "POST",
         url: action,
         data: postValues,
         dataType: 'json',
         error: function(xhr) {
            gdn.informError(xhr);
         },
         success: function(json) {
            json = $.postParseJson(json);
            gdn.inform(json);
         }
      });
      return false;
   }
   $("textarea").keyup(function() {
      if ($('#livepreview:checked').length > 0)
         pushCss();
   });
   $('input.Button').click(function() {
      // Push the changes to the iframe
      pushCss();
      // Post/save the changes
      saveCss(this);      
      return false;
   });
   // Bind to iframe's load event and push the css when pages are navigated
   $("#vframe").load(pushCss);
});
//]]>
</script>
<div id="sidebar">
    <div id="dragbar"></div>
    <label class="heading">Edit CSS for <?php echo Wrap(str_replace('http://', '', Url('/', TRUE)), 'strong'); ?>:</label>
    <?php
    echo $this->Form->Open();
    echo $this->Form->TextBox('CSS', array('MultiLine' => TRUE, 'class' => 'TextBox'));
    ?>
    <div id="controls">
       <label for="livepreview"><input type="checkbox" name="livepreview" id="livepreview" checked="checked"> Live Preview Changes</label>
       <div id="buttons">
          <?php
          echo $this->Form->Button('Save');
          echo $this->Form->Button('Apply');
          ?>
       </div>
    </div>
    <?php echo $this->Form->Close(); ?>
</div>
<div id="framecover"></div>
<div id="main">
   <iframe name="vframe" id="vframe" src="<?php echo Url('/'); ?>" scrolling="auto" frameborder="no" border="0" width="100%" height="100%"></iframe>
</div>