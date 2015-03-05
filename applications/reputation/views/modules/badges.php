<?php if (!defined('APPLICATION')) exit(); 
   $Title = ($this->User->UserID == Gdn::Session()->UserID) ? T('MyBadgesModuleTitle', 'My Badges') : T('BadgesModuleTitle', 'Badges');
   $Title = T($Title);
?>
<div id="Badges" class="Box BadgeGrid<?php if (!count($this->Badges)) echo ' NoItems'; ?>">
   <h4><?php echo $Title; ?></h4>
   <div class="PhotoGrid">
   <?php
   if (count($this->Badges) > 20)
      $CssClass = 'ProfilePhoto ProfilePhotoSmall';
   else
      $CssClass = 'ProfilePhoto ProfilePhotoMedium';
   
   foreach ($this->Badges as $Badge) : 
   ?>
      <?php if (GetValue('Photo', $Badge, FALSE)) : ?>
         <?php echo Anchor(
            Img(Gdn_Upload::Url(ChangeBasename(GetValue('Photo', $Badge), '%s')), array('class' => $CssClass)), 
            Url('/badge/'.GetValue('Slug', $Badge), TRUE), 
            array('title' => GetValue('Name', $Badge))
         ); ?>
      <?php endif; ?>
   <?php endforeach; ?>
   
   <?php if (!count($this->Badges)) : ?>
   <span><?php echo T('NoBadgesEarned', 'Any minute now&hellip;'); ?></span>
   <?php endif; ?>
   </div>
</div>