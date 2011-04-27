<?php if (!defined('APPLICATION')) exit(); ?>
<div class="P">
   <?php
   echo $this->Form->Label('Company', 'Company'),
      '<div class="Info">This is the name of the user that is running the competion.</div>',
      $this->Form->TextBox('Company');
   ?>
</div>
<div class="P">
   <table>
      <tr>
         <td>
            <?php
            echo $this->Form->Label('Start Date', 'DateCompetitionStarts')
            ?>
         </td>
         <td>
            <?php
            echo $this->Form->Label('Finish Date', 'DateCompetitionFinishes')
            ?>
         </td>
      </tr>
      <tr>
         <td>
            <?php
            echo $this->Form->Hidden('DateFormat', array('class' => 'DateBox'));
            echo $this->Form->TextBox('DateCompetitionStarts2', array('class' => 'DateBox'));
            ?>
            &#160;
         </td>
         <td>
            <?php
            echo $this->Form->TextBox('DateCompetitionFinishes2', array('class' => 'DateBox'));
            ?>
         </td>
      </tr>
   </table>
</div>
<div class="P">
   <?php
   echo $this->Form->CheckBox('CanDownloadFiles', 'Allow companies to download files for this competition.');
   ?>
</div>