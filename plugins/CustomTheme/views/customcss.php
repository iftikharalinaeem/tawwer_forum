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
      // console.log("leaving mouseDown");
   });
   $(document).mouseup(function(e){
      // $('#clickevent').html('in another mouseUp event' + i++);
      $(document).unbind('mousemove');
      $('#framecover').hide();
   });
   pushCss = function() {
      var head = $("#vframe").contents().find("head");
      if (head) {
         $("#vframe").contents().find('#editcss').remove();
         head.append('<style type="text/css" id="editcss">'+$('#Form_CSS').val()+'</style>');
         // console.log('setting container contents');
      }
   }
   $("#Form_CSS").keyup(function() {
      if ($('#livepreview:checked').length > 0)
         pushCss();
   });
   $('#save').click(pushCss);
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
          <input type="Button" id="save" value="Save" />
          <input type="Button" id="apply" value="Apply" />
          </form>
       </div>
    </div>
    <?php echo $this->Form->Close(); ?>
</div>
<div id="framecover"></div>
<div id="main">
   <iframe name="vframe" id="vframe" src="<?php echo Url('/'); ?>" scrolling="auto" frameborder="no" border="0" width="100%" height="100%"></iframe>
</div>