<?php if (!defined('APPLICATION')) exit(); ?>
<style type="text/css">
   .CategoryModerators {
      display: none;
   }
   .CategoryModerators ul {
      margin: 0px !important;
      padding: 3px !important;
   }
   form .CategoryModerators ul li {
      margin: 0px !important;
      margin-right: 4px !important;
   }
   form .CategoryModerators ul li span {
      display: inline;
   }
</style>
<?php
   $CategoryID = $this->data('CategoryID');
   $CategoryModerators = $this->ModList->moderators($CategoryID, FALSE);
   $this->Form->setValue('CategoryModerators', (bool)sizeof($CategoryModerators));
   
   $CategoryModerators = Gdn_DataSet::index($CategoryModerators, 'UserID');
?>
<li>
   <?php echo $this->Form->checkBox('CategoryModerators', 'This category has a moderator list.', ['class' => 'CategoryModeratorsCheck']); ?>
</li>
<li class="CategoryModerators"><?php
   echo $this->Form->textBox('Moderators');
?></li>
<script type="text/javascript">
   $('.CategoryModeratorsCheck').change(function(event){
      var on = $(event.target).prop('checked');
      if (on)
         $('.CategoryModerators').show();
      else
         $('.CategoryModerators').hide();
   });

   $('.CategoryModeratorsCheck').trigger('change');
   
   jQuery(document).ready(function($) {
      var btn = $('.editcategory form :submit');
      var frm = btn.closest('form');

      frm.bind('BeforeCategorySubmit',function(e, frm, btn) {
         var taglist = $(frm).find('input#Form_Moderators');
         taglist.triggerHandler('BeforeSubmit',[frm]);
      });
   });
</script>