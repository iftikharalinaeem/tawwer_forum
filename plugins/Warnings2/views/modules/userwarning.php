<div class="Hero Hero-WarningLevel">
   <h2>
   <?php
   echo sprintf(t('Warning Level %s'), $this->data('WarningLevel')).
      ' '.
      '<span class="Gloss">'.
      bullet(' ').
      sprintf(t('expires %s'), Gdn_Format::date($this->data('TimeWarningExpires'), 'html')).
      '</span>';
   ?>
   </h2>
   
   <?php if ($this->data('Punished')): ?>
   <div class="Message">
      <?php 
      if ($this->UserID == Gdn::session()->UserID)
         echo t('You are jailed.');
      else
         echo sprintf(t('%s is jailed.'), htmlspecialchars($this->data('Name')));
      
      ?>
      <ul>
         <li>Can't post discussions.</li>
         <li>Can't post as often.</li>
         <li>Signature hidden.</li>
      </ul>
   </div>
   <?php endif; ?>
</div>