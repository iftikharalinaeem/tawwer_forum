<div class="Hero Hero-WarningLevel">
   <h2>
   <?php
   echo sprintf(T('Warning Level %s'), $this->Data('WarningLevel')).
      ' '.
      '<span class="Gloss">'.
      Bullet(' ').
      sprintf(T('expires %s'), Gdn_Format::Date($this->Data('TimeWarningExpires'), 'html')).
      '</span>';
   ?>
   </h2>
   
   <?php if ($this->Data('Punished')): ?>
   <div class="Message">
      <?php 
      if ($this->UserID == Gdn::Session()->UserID)
         echo T('You are jailed.');
      else
         echo sprintf(T('%s is jailed.'), htmlspecialchars($this->Data('Name')));
      
      ?>
      <ul>
         <li>Can't post discussions.</li>
         <li>Can't post as often.</li>
         <li>Signature hidden.</li>
      </ul>
   </div>
   <?php endif; ?>
</div>